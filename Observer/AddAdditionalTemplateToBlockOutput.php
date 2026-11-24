<?php declare(strict_types=1);

namespace Yireo\AdditionalBlockTemplate\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\Template;

class AddAdditionalTemplateToBlockOutput implements ObserverInterface
{
    private const POSITIONS = ['before', 'after', 'nest'];
    private const RAW_TEXT_TAGS = ['script', 'style', 'textarea', 'title', 'template'];
    private const TEMPLATE_PATTERN = '#^[A-Za-z0-9]+_[A-Za-z0-9]+::[A-Za-z0-9_\-][A-Za-z0-9_\-/.]*\.phtml$#D';
    private const CLOSING_TAG_PATTERN = '#</([a-z][a-z0-9:-]*)\s*>\s*$#iD';

    /**
     * Render the additional templates of a block around its original output
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $block = $observer->getData('block');
        if (!$block instanceof Template) {
            return;
        }

        $transport = $observer->getData('transport');
        if (!$transport instanceof DataObject) {
            return;
        }

        $additionalTemplates = $this->getAdditionalTemplates($block->getData('additional_templates'));
        if (empty($additionalTemplates)) {
            return;
        }

        $html = (string)$transport->getData('html');
        $isEmpty = trim($html, " \f\n\r\t\v\x00") === '';
        $parts = ['before' => '', 'after' => '', 'nest' => ''];

        try {
            foreach ($additionalTemplates as $additionalTemplateName => $additionalTemplate) {
                if ($isEmpty && !$additionalTemplate['render_when_empty']) {
                    continue;
                }

                $block->setData('additional_template_name', $additionalTemplateName);
                $additionalHtml = (string)$block->fetchView(
                    $block->getTemplateFile($additionalTemplate['template'])
                );

                $parts[$additionalTemplate['position']] .= $additionalHtml;
            }
        } finally {
            $block->unsetData('additional_template_name');
        }

        $transport->setData('html', $parts['before'] . $this->nest($html, $parts['nest']) . $parts['after']);
    }

    /**
     * Normalize the additional templates and skip invalid items
     *
     * @param mixed $additionalTemplates
     * @return array<int|string, array{template: string, position: string, render_when_empty: bool}>
     */
    private function getAdditionalTemplates(mixed $additionalTemplates): array
    {
        if (is_string($additionalTemplates)) {
            $additionalTemplates = [
                'default' => [
                    'template' => $additionalTemplates,
                    'position' => 'after',
                ]
            ];
        }

        if (!is_array($additionalTemplates)) {
            return [];
        }

        $validTemplates = [];
        foreach ($additionalTemplates as $name => $additionalTemplate) {
            if (!is_array($additionalTemplate)) {
                continue;
            }

            $template = $additionalTemplate['template'] ?? null;
            if (!is_string($template) || !$this->isValidTemplate($template)) {
                continue;
            }

            $position = $additionalTemplate['position'] ?? 'after';
            if (!is_string($position) || !in_array($position, self::POSITIONS, true)) {
                continue;
            }

            $validTemplates[$name] = [
                'template' => $template,
                'position' => $position,
                'render_when_empty' => filter_var(
                    $additionalTemplate['render_when_empty'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                ),
            ];
        }

        return $validTemplates;
    }

    /**
     * Check whether a template is a valid module template identifier
     *
     * @param string $template
     * @return bool
     */
    private function isValidTemplate(string $template): bool
    {
        if (str_contains($template, '..')) {
            return false;
        }

        return preg_match(self::TEMPLATE_PATTERN, $template) === 1;
    }

    /**
     * Insert HTML before the final closing tag, or append it when that is not safe
     *
     * @param string $html
     * @param string $additionalHtml
     * @return string
     */
    private function nest(string $html, string $additionalHtml): string
    {
        if ($additionalHtml === '') {
            return $html;
        }

        if (!preg_match(self::CLOSING_TAG_PATTERN, $html, $matches, PREG_OFFSET_CAPTURE)) {
            return $html . $additionalHtml;
        }

        if (in_array(strtolower($matches[1][0]), self::RAW_TEXT_TAGS, true)) {
            return $html . $additionalHtml;
        }

        return substr_replace($html, $additionalHtml, $matches[0][1], 0);
    }
}

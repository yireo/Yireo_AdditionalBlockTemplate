<?php declare(strict_types=1);

namespace Yireo\AdditionalBlockTemplate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Element\Template;

class AddAdditionalTemplateToBlockOutput implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var Template $block */
        $block = $observer->getBlock();
        if (!$block instanceof Template) {
            return;
        }
        
        $additionalTemplates = $block->getData('additional_templates');
        if (empty($additionalTemplates)) {
            return;
        }
        
        if (is_string($additionalTemplates)) {
            $additionalTemplates = [
                [
                    'template' => $additionalTemplates,
                    'position' => 'after',
                ]
            ];
        }
        
        $transport = $observer->getTransport();
        $html = $transport->getHtml();
        
        foreach ($additionalTemplates as $additionalTemplate) {
            $additionalHtml = $block->fetchView($block->getTemplateFile($additionalTemplate['template']));
            if (isset($additionalTemplate['position']) && $additionalTemplate['position'] === 'before') {
                $html = $additionalHtml . $html;
            } else {
                $html = $html . $additionalHtml;
            }
        }
        
        $transport->setHtml($html);
    }
}

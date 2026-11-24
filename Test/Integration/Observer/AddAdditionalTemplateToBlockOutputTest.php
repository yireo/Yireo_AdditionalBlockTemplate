<?php declare(strict_types=1);

namespace Yireo\AdditionalBlockTemplate\Test\Integration\Observer;

use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Yireo\AdditionalBlockTemplate\Observer\AddAdditionalTemplateToBlockOutput;
use Yireo\AdditionalBlockTemplate\Test\Integration\Stub\FixtureTemplateResolver;

/**
 * @magentoAppArea frontend
 */
class AddAdditionalTemplateToBlockOutputTest extends TestCase
{
    public function testObserverIsRegisteredInFrontendArea()
    {
        $eventConfig = Bootstrap::getObjectManager()->get(EventConfig::class);
        $observers = $eventConfig->getObservers('view_block_abstract_to_html_after');

        $this->assertArrayHasKey('Yireo_AdditionalBlockTemplate::addAdditionalTemplateToBlockOutput', $observers);
        $this->assertSame(
            AddAdditionalTemplateToBlockOutput::class,
            ltrim($observers['Yireo_AdditionalBlockTemplate::addAdditionalTemplateToBlockOutput']['instance'], '\\')
        );
    }

    public function testRendersAdditionalTemplatesAroundRealBlock()
    {
        $block = $this->createBlock('Yireo_AdditionalBlockTemplate::fixture/main.phtml', [
            'first' => ['template' => 'Yireo_AdditionalBlockTemplate::fixture/before.phtml', 'position' => 'before'],
            'second' => ['template' => 'Yireo_AdditionalBlockTemplate::fixture/after.phtml', 'position' => 'after'],
            'third' => ['template' => 'Yireo_AdditionalBlockTemplate::fixture/nest.phtml', 'position' => 'nest'],
        ]);

        $html = preg_replace('/\s+/', '', (string)$block->toHtml());

        $this->assertSame(
            '<spanclass="before">first</span><divclass="main">main<spanclass="nest">third</span></div>'
            . '<spanclass="after">second</span>',
            $html
        );
        $this->assertNull($block->getData('additional_template_name'));
    }

    public function testSkipsAdditionalTemplatesForEmptyBlock()
    {
        $block = $this->createBlock('Yireo_AdditionalBlockTemplate::fixture/empty.phtml', [
            'first' => ['template' => 'Yireo_AdditionalBlockTemplate::fixture/after.phtml'],
        ]);

        $this->assertSame('', trim((string)$block->toHtml(), " \f\n\r\t\v\x00"));
    }

    public function testRendersAdditionalTemplatesForEmptyBlockWhenOptedIn()
    {
        $block = $this->createBlock('Yireo_AdditionalBlockTemplate::fixture/empty.phtml', [
            'first' => [
                'template' => 'Yireo_AdditionalBlockTemplate::fixture/after.phtml',
                'render_when_empty' => true,
            ],
        ]);

        $this->assertSame('<spanclass="after">first</span>', preg_replace('/\s+/', '', (string)$block->toHtml()));
    }

    public function testSkipsTemplateWithTraversal()
    {
        $block = $this->createBlock('Yireo_AdditionalBlockTemplate::fixture/main.phtml', [
            'first' => ['template' => 'Yireo_AdditionalBlockTemplate::fixture/../_files/after.phtml'],
        ]);

        $this->assertSame('<divclass="main">main</div>', preg_replace('/\s+/', '', (string)$block->toHtml()));
    }

    private function createBlock(string $template, array $additionalTemplates): Template
    {
        $objectManager = Bootstrap::getObjectManager();
        $context = $objectManager->create(Context::class, [
            'resolver' => $objectManager->create(FixtureTemplateResolver::class),
        ]);

        $block = $objectManager->create(Template::class, ['context' => $context]);
        $block->setTemplate($template);
        $block->setData('additional_templates', $additionalTemplates);

        return $block;
    }
}

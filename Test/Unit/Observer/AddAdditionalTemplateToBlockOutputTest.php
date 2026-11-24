<?php declare(strict_types=1);

namespace Yireo\AdditionalBlockTemplate\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Template;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\AdditionalBlockTemplate\Observer\AddAdditionalTemplateToBlockOutput;

class AddAdditionalTemplateToBlockOutputTest extends TestCase
{
    private array $renderedNames = [];

    public function testIgnoresBlocksThatAreNotTemplates()
    {
        $block = $this->getMockBuilder(AbstractBlock::class)->disableOriginalConstructor()->getMock();
        $this->assertSame('<p>main</p>', $this->render($block, '<p>main</p>'));
    }

    public function testDoesNothingWithoutAdditionalTemplates()
    {
        $this->assertSame('<p>main</p>', $this->render($this->createBlock(null), '<p>main</p>'));
    }

    public function testStringIsAppendedWithDefaultName()
    {
        $html = $this->render($this->createBlock('Foo_Bar::a.phtml'), '<p>main</p>');
        $this->assertSame('<p>main</p>[Foo_Bar::a.phtml]', $html);
        $this->assertSame(['default'], $this->renderedNames);
    }

    public function testPositions()
    {
        $block = $this->createBlock([
            'one' => ['template' => 'Foo_Bar::before.phtml', 'position' => 'before'],
            'two' => ['template' => 'Foo_Bar::after.phtml', 'position' => 'after'],
            'three' => ['template' => 'Foo_Bar::nest.phtml', 'position' => 'nest'],
            'four' => ['template' => 'Foo_Bar::default.phtml'],
        ]);

        $html = $this->render($block, '<div>main</div>');
        $this->assertSame(
            '[Foo_Bar::before.phtml]<div>main[Foo_Bar::nest.phtml]</div>[Foo_Bar::after.phtml][Foo_Bar::default.phtml]',
            $html
        );
        $this->assertSame(['one', 'two', 'three', 'four'], $this->renderedNames);
    }

    public function testPositionsAreRelativeToOriginalOutputInDeclaredOrder()
    {
        $block = $this->createBlock([
            'a' => ['template' => 'Foo_Bar::after.phtml', 'position' => 'after'],
            'b' => ['template' => 'Foo_Bar::nest.phtml', 'position' => 'nest'],
            'c' => ['template' => 'Foo_Bar::before1.phtml', 'position' => 'before'],
            'd' => ['template' => 'Foo_Bar::before2.phtml', 'position' => 'before'],
        ]);

        $this->assertSame(
            '[Foo_Bar::before1.phtml][Foo_Bar::before2.phtml]<div>main[Foo_Bar::nest.phtml]</div>[Foo_Bar::after.phtml]',
            $this->render($block, '<div>main</div>')
        );
    }

    public function testInvalidPositionIsSkipped()
    {
        $block = $this->createBlock(['x' => ['template' => 'Foo_Bar::a.phtml', 'position' => 'nested']]);
        $this->assertSame('<p>main</p>', $this->render($block, '<p>main</p>'));
    }

    /**
     * @dataProvider getInvalidItems
     */
    public function testInvalidItemsAreSkipped(mixed $item)
    {
        $block = $this->createBlock(['x' => $item]);
        $this->assertSame('<p>main</p>', $this->render($block, '<p>main</p>'));
        $this->assertSame([], $this->renderedNames);
    }

    public static function getInvalidItems(): array
    {
        return [
            'string item' => ['Foo_Bar::a.phtml'],
            'missing template' => [['position' => 'after']],
            'empty template' => [['template' => '']],
            'non-string template' => [['template' => ['Foo_Bar::a.phtml']]],
            'traversal' => [['template' => 'Foo_Bar::../../../etc/passwd.phtml']],
            'absolute path' => [['template' => '/etc/passwd.phtml']],
            'leading slash' => [['template' => 'Foo_Bar::/etc/a.phtml']],
            'no module' => [['template' => 'a.phtml']],
            'not phtml' => [['template' => 'Foo_Bar::a.php']],
            'newline suffix' => [['template' => "Foo_Bar::a.phtml\n"]],
            'non-string position' => [['template' => 'Foo_Bar::a.phtml', 'position' => ['before']]],
        ];
    }

    public function testNonArrayDataIsIgnored()
    {
        $this->assertSame('<p>main</p>', $this->render($this->createBlock(42), '<p>main</p>'));
    }

    public function testEmptyOutputSkipsAdditionalTemplates()
    {
        $block = $this->createBlock(['x' => ['template' => 'Foo_Bar::a.phtml']]);
        $this->assertSame(" \n", $this->render($block, " \n"));
        $this->assertSame([], $this->renderedNames);
    }

    public function testEmptyOutputRendersWhenOptedIn()
    {
        $block = $this->createBlock([
            'x' => ['template' => 'Foo_Bar::a.phtml', 'position' => 'nest', 'render_when_empty' => true],
            'y' => ['template' => 'Foo_Bar::b.phtml', 'render_when_empty' => 'false'],
        ]);
        $this->assertSame('[Foo_Bar::a.phtml]', $this->render($block, ''));
    }

    /**
     * @dataProvider getNestCases
     */
    public function testNest(string $html, string $expected)
    {
        $block = $this->createBlock(['x' => ['template' => 'Foo_Bar::n.phtml', 'position' => 'nest']]);
        $this->assertSame($expected, $this->render($block, $html));
    }

    public static function getNestCases(): array
    {
        return [
            'div' => ['<div><p>a</p></div>', '<div><p>a</p>[Foo_Bar::n.phtml]</div>'],
            'trailing whitespace' => ["<div>a</div>\n  ", "<div>a[Foo_Bar::n.phtml]</div>\n  "],
            'whitespace in tag' => ['<div>a</div >', '<div>a[Foo_Bar::n.phtml]</div >'],
            'custom element' => ['<x-foo>a</x-foo>', '<x-foo>a[Foo_Bar::n.phtml]</x-foo>'],
            'trailing script' => [
                '<div>a</div><script>var x = "</div>";</script>',
                '<div>a</div><script>var x = "</div>";</script>[Foo_Bar::n.phtml]',
            ],
            'trailing style' => ['<style>a{}</style>', '<style>a{}</style>[Foo_Bar::n.phtml]'],
            'trailing textarea' => ['<textarea>a</textarea>', '<textarea>a</textarea>[Foo_Bar::n.phtml]'],
            'trailing title' => ['<title>a</title>', '<title>a</title>[Foo_Bar::n.phtml]'],
            'trailing template' => ['<template>a</template>', '<template>a</template>[Foo_Bar::n.phtml]'],
            'uppercase script' => ['<SCRIPT>a</SCRIPT>', '<SCRIPT>a</SCRIPT>[Foo_Bar::n.phtml]'],
            'no closing tag' => ['plain text', 'plain text[Foo_Bar::n.phtml]'],
            'closing tag not at end' => ['<div>a</div> text', '<div>a</div> text[Foo_Bar::n.phtml]'],
            'js string at end' => ["x = '</'", "x = '</'[Foo_Bar::n.phtml]"],
        ];
    }

    public function testTemplateNameIsResetAfterRendering()
    {
        $block = $this->createBlock(['x' => ['template' => 'Foo_Bar::a.phtml']]);
        $this->render($block, '<p>main</p>');
        $this->assertNull($block->getData('additional_template_name'));
    }

    public function testTemplateNameIsResetWhenRenderingFails()
    {
        $block = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchView', 'getTemplateFile'])
            ->getMock();
        $block->setData('additional_templates', 'Foo_Bar::a.phtml');
        $block->method('getTemplateFile')->willReturnArgument(0);
        $block->method('fetchView')->willThrowException(new \RuntimeException('fail'));

        try {
            $this->render($block, '<p>main</p>');
            $this->fail('Expected exception');
        } catch (\RuntimeException $exception) {
            $this->assertSame('fail', $exception->getMessage());
        }

        $this->assertNull($block->getData('additional_template_name'));
    }

    private function render(AbstractBlock $block, string $html): string
    {
        $transport = new DataObject(['html' => $html]);
        $observer = new Observer(['block' => $block, 'transport' => $transport]);
        (new AddAdditionalTemplateToBlockOutput())->execute($observer);

        return $transport->getData('html');
    }

    private function createBlock(mixed $additionalTemplates): Template&MockObject
    {
        $this->renderedNames = [];
        $block = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchView', 'getTemplateFile'])
            ->getMock();

        $block->setData('additional_templates', $additionalTemplates);
        $block->method('getTemplateFile')->willReturnArgument(0);
        $block->method('fetchView')->willReturnCallback(function (string $file) use ($block) {
            $this->renderedNames[] = $block->getData('additional_template_name');
            return '[' . $file . ']';
        });

        return $block;
    }
}

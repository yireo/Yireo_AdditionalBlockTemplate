<?php declare(strict_types=1);

namespace Yireo\AdditionalBlockTemplate\Test\Integration\Stub;

use Magento\Framework\View\Element\Template\File\Resolver;

class FixtureTemplateResolver extends Resolver
{
    private const PREFIX = 'Yireo_AdditionalBlockTemplate::fixture/';

    /**
     * @param string $template
     * @param array $params
     * @return string|bool
     */
    public function getTemplateFileName($template, $params = [])
    {
        if (str_starts_with($template, self::PREFIX)) {
            return dirname(__DIR__) . '/_files/' . substr($template, strlen(self::PREFIX));
        }

        return parent::getTemplateFileName($template, $params);
    }
}

# Yireo AdditionalBlockTemplate

**A Magento 2 module to allow for additional templates to be rendered, besides the original template.**

### Overview
When the Magento frontend renders its storefront, it uses the XML layout to position blocks all around. Each block can be rendered via a template and by default, this means that one block only has one template. Adding a new template right before the original template but with a new `<block/>` definition causes another `$block` object to be created. This module overcomes this shortcoming and simply allows you to add multiple templates to one single `$block` instance.

### Installation
```bash
composer require yireo/magento2-additional-block-template
bin/magento module:enable Yireo_AdditionalBlockTemplate
```

### Usage
Using the XML layout, you can reference a certain block and add your own template in front of the original template (`before`), after the original template (`after`) or right before the final closing tag in the original template (`nest`):
```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceBlock name="category.description">
            <arguments>
                <argument name="additional_templates" xsi:type="array">
                    <item name="example1" xsi:type="array">
                        <item name="template" xsi:type="string">Yireo_Example::example-before.phtml</item>
                        <item name="position" xsi:type="string">before</item>
                    </item>
                    <item name="example2" xsi:type="array">
                        <item name="template" xsi:type="string">Yireo_Example::example-after.phtml</item>
                        <item name="position" xsi:type="string">after</item>
                    </item>
                    <item name="example3" xsi:type="array">
                        <item name="template" xsi:type="string">Yireo_Example::example-nest.phtml</item>
                        <item name="position" xsi:type="string">nest</item>
                    </item>
                </argument>
            </arguments>
        </referenceBlock>
    </body>
</page>
```

Each item name (in this case `example1`, etc) can be retreived in the block template using `$block->getAdditionalTemplateName()`.
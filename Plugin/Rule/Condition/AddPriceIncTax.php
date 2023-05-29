<?php

namespace MageSuite\FreeGift\Plugin\Rule\Condition;

class AddPriceIncTax
{
    public const ATTRIBUTE_CODE = 'price_incl_tax';
    public const ATTRIBUTE_LABEL = 'Price including tax';

    public function afterLoadAttributeOptions(
        \Magento\SalesRule\Model\Rule\Condition\Product $subject,
        $result
    ): \Magento\SalesRule\Model\Rule\Condition\Product {
        $attributeOptions = $subject->getAttributeOption();
        $attributeOptions[self::ATTRIBUTE_CODE] = __(self::ATTRIBUTE_LABEL);
        $subject->setAttributeOption($attributeOptions);

        return $subject;
    }

    public function beforeValidate(
        \Magento\SalesRule\Model\Rule\Condition\Product $subject,
        \Magento\Framework\Model\AbstractModel $model
    ): array {
        /** @var \Magento\Catalog\Model\Product $product */
        /** @var \Magento\Quote\Model\Quote\Item $model */
        $product = $model->getProduct();

        if (!$product) {
            return [$model];
        }

        $product->setData(self::ATTRIBUTE_CODE, $model->getPriceInclTax());

        return [$model];
    }
}

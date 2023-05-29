<?php

namespace MageSuite\FreeGift\Plugin\Rule\Condition;

class RenamePriceCondition
{
    public function afterLoadAttributeOptions(
        \Magento\SalesRule\Model\Rule\Condition\Product $subject,
        $result
    ): \Magento\SalesRule\Model\Rule\Condition\Product {
        $attributeOptions = $subject->getAttributeOption();
        $attributeOptions['quote_item_price'] = __('Price without tax in cart');
        $subject->setAttributeOption($attributeOptions);

        return $subject;
    }
}

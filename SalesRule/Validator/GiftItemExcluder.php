<?php

namespace MageSuite\FreeGift\SalesRule\Validator;

/**
 * Validator for checking if item is valid to be used in SalesRules. Gifts must not be processed.
 */
class GiftItemExcluder
{
    /**
     * Gift items should not be processed by SalesRules
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isValid($item)
    {
        return $item->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU) == null;
    }

    /**
     * @inheritdoc
     */
    public function getMessages()
    {
        return [];
    }
}

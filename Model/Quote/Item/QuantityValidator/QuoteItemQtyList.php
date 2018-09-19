<?php

namespace MageSuite\FreeGift\Model\Quote\Item\QuantityValidator;

class QuoteItemQtyList extends \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList
{
    public function removeQuoteItem($itemId)
    {
        foreach ($this->_checkedQuoteItems as $quoteId => $products) {
            foreach ($products as $productId => $details) {
                if (isset($details['items']) and in_array($itemId, $details['items'])) {
                    unset($this->_checkedQuoteItems[$quoteId][$productId]);
                }
            }
        }
    }
}
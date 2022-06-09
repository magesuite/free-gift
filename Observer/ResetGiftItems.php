<?php

namespace MageSuite\FreeGift\Observer;

use MageSuite\FreeGift\SalesRule\Action\GiftAction;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;

class ResetGiftItems implements ObserverInterface
{
    /**
     * @var \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList
     */
    protected $quoteItemQtyList;

    public function __construct(\Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $quoteItemQtyList)
    {
        $this->quoteItemQtyList = $quoteItemQtyList;
    }

    /**
     * Delete related gift items when quantity of main item changed
     *
     * @event sales_quote_address_collect_totals_before
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        if ($quote->getData('gift_items_reseted')) {
            return;
        }

        $address = $quote->getShippingAddress();

        if ($quote->getAllItems() == null || $address->getAddressType() != Quote\Address::TYPE_SHIPPING)
        {
            return;
        }

        /** @var Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem)
        {
            if($quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU) instanceof Quote\Item\Option) {
                continue;
            }

            $originalQty = (int)$quoteItem->getOrigData('qty');

            if($originalQty <= 0) {
                continue;
            }

            $currentQty = $quoteItem->getQty();

            if($originalQty != $currentQty) {
                $productSku = $quoteItem->getProduct()->getSku();
                $appliedRules = $quoteItem->getAppliedRuleIds();

                if($appliedRules == null) {
                    continue;
                }

                $appliedRules = explode(',', $appliedRules);

                foreach($appliedRules as $ruleId) {
                    foreach($quote->getAllItems() as $toDeleteItem) {
                        if((
                            $toDeleteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU) instanceof Quote\Item\Option
                            and
                            $toDeleteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)->getValue() == $productSku
                        )
                            and
                        (
                            $toDeleteItem->getOptionByCode('rule_id') instanceof Quote\Item\Option
                            and
                            $toDeleteItem->getOptionByCode('rule_id')->getValue() == $ruleId
                        ))
                        {
                            $quote->deleteItem($toDeleteItem);
                            $this->quoteItemQtyList->removeQuoteItem($toDeleteItem->getId());
                        }
                    }
                }

                $quoteItem->setAppliedRuleIds(null);
            }
        }

        $quote->setData('gift_items_reseted', true);
    }
}

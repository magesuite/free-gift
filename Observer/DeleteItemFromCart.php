<?php

namespace MageSuite\FreeGift\Observer;

class DeleteItemFromCart implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Delete all gift items related to deleted product.
     * They will be re-added by SalesRule (If possible).
     * @event sales_quote_address_collect_totals_before
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote\Item $quote */
        $quoteItem = $observer->getEvent()->getData('quote_item');
        $quote = $quoteItem->getQuote();


        if($quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)
            instanceof \Magento\Quote\Model\Quote\Item\Option) {

            if($quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ITEM_OPTION_COUPON_GIFT) == true){
                $checkoutSession = $this->checkoutSession;

                $initialCouponGiftCount = $checkoutSession->getInitialCouponFreeGiftItems();

                $updatedCouponGiftCount = $initialCouponGiftCount - 1;

                if($updatedCouponGiftCount == 0){
                    $checkoutSession->setUpdatedCouponGiftCount(0);
                    $checkoutSession->setCanIncreaseCouponUsage(false);
                    return;
                }

                $checkoutSession->setUpdatedCouponGiftCount($updatedCouponGiftCount);
            }
            return;
        }

        $productSku = $quoteItem->getProduct()->getSku();

        $appliedRules = $quoteItem->getAppliedRuleIds();

        if ($appliedRules == null) {
            return;
        }

        $appliedRules = explode(',', $appliedRules);

        foreach ($appliedRules as $ruleId) {
            foreach ($quote->getAllItems() as $toDeleteItem) {
                if (
                    $this->hasOptionWithValue($toDeleteItem, \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU, $productSku)
                    and
                    $this->hasOptionWithValue($toDeleteItem, 'rule_id', $ruleId)
                ) {
                    $quote->deleteItem($toDeleteItem);

                    if ($this->hasOptionWithValue($toDeleteItem, \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::GIFT_ADDED_ONCE, true)) {
                        $this->removeAppliedRuleForAllItems($quote, $ruleId);
                    }
                }
            }
        }

        $quoteItem->setAppliedRuleIds(null);
    }

    /**
     * Removes applied rule id from all quote items
     * This is done only when "gift added once per cart" is removed
     * Rule id must be removed from other items so it can be recalculated again against remaining cart items
     * @param $quote
     * @param $ruleId
     */
    protected function removeAppliedRuleForAllItems($quote, $ruleId)
    {
        foreach ($quote->getAllItems() as $item) {
            $appliedRuleIds = explode(',', $item->getAppliedRuleIds());

            if (($key = array_search($ruleId, $appliedRuleIds)) !== false) {
                unset($appliedRuleIds[$key]);
            }

            $item->setAppliedRuleIds(implode(',', $appliedRuleIds));
        }
    }

    protected function hasOptionWithValue($quoteItem, $optionIdentifier, $optionValue)
    {
        return (
            $quoteItem->getOptionByCode($optionIdentifier) instanceof \Magento\Quote\Model\Quote\Item\Option
            and
            $quoteItem->getOptionByCode($optionIdentifier)->getValue() == $optionValue
        );
    }
}
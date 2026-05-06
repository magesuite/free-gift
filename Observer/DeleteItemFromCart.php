<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Observer;

class DeleteItemFromCart implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        protected \Magento\Checkout\Model\Session $checkoutSession,
        protected \MageSuite\FreeGift\Service\GiftItem $giftItem
    ) {
    }

    /**
     * Delete all gift items related to the deleted product.
     * They will be re-added by SalesRule (If possible).
     * @event sales_quote_address_collect_totals_before
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        /** @var \Magento\Quote\Model\Quote\Item $quote */
        $quoteItem = $observer->getEvent()->getData('quote_item');
        $quote = $quoteItem->getQuote();

        if ($quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU) instanceof \Magento\Quote\Model\Quote\Item\Option) {
            $this->processRemovedGiftItem($quoteItem);
            return;
        }

        $productSku = $quoteItem->getProduct()->getSku();
        $appliedRules = (string)$quoteItem->getAppliedRuleIds();
        if (trim($appliedRules) === '') {
            $this->removeRelatedGiftsWithRuleIdsFromQuoteItems($quote, $productSku);
            return;
        }

        $this->removeRelatedGiftsByAppliedRules($quote, $productSku, $appliedRules);
        $quoteItem->setAppliedRuleIds(null);
    }

    protected function processRemovedGiftItem(\Magento\Quote\Api\Data\CartItemInterface $quoteItem): void
    {
        if (!$quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ITEM_OPTION_COUPON_GIFT)) {
            return;
        }

        $initialCouponGiftCount = $this->checkoutSession->getInitialCouponFreeGiftItems();
        $updatedCouponGiftCount = $initialCouponGiftCount - 1;

        if ($updatedCouponGiftCount == 0) {
            $this->checkoutSession->setUpdatedCouponGiftCount(0);
            $this->checkoutSession->setCanIncreaseCouponUsage(false);
            return;
        }

        $this->checkoutSession->setUpdatedCouponGiftCount($updatedCouponGiftCount);
    }

    protected function removeRelatedGiftsWithRuleIdsFromQuoteItems(\Magento\Quote\Api\Data\CartInterface $quote, string $productSku): void
    {
        foreach ($quote->getAllItems() as $toDeleteItem) {
            $ruleId = $this->giftItem->getRuleId($toDeleteItem);
            if ($ruleId === null || !$this->giftItem->isRelatedToProductAndRule($toDeleteItem, $productSku, $ruleId)) {
                continue;
            }

            $this->deleteRelatedGiftItem($quote, $toDeleteItem, $ruleId);
        }
    }

    protected function removeRelatedGiftsByAppliedRules(\Magento\Quote\Api\Data\CartInterface $quote, string $productSku, string $appliedRules): void
    {
        foreach (explode(',', $appliedRules) as $appliedRule) {
            $ruleId = (int)trim($appliedRule);
            if ($ruleId <= 0) {
                continue;
            }

            foreach ($quote->getAllItems() as $toDeleteItem) {
                if (!$this->giftItem->isRelatedToProductAndRule($toDeleteItem, $productSku, $ruleId)) {
                    continue;
                }

                $this->deleteRelatedGiftItem($quote, $toDeleteItem, $ruleId);
            }
        }
    }

    protected function deleteRelatedGiftItem(\Magento\Quote\Api\Data\CartInterface $quote, \Magento\Quote\Api\Data\CartItemInterface $quoteItem, int $ruleId): void
    {
        $quote->deleteItem($quoteItem);
        if (!$this->giftItem->isAddedOnce($quoteItem)) {
            return;
        }

        $this->giftItem->removeRuleIdFromAllQuoteItems($quote, $ruleId);
    }
}

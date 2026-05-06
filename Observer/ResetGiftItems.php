<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Observer;

class ResetGiftItems implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        protected \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $quoteItemQtyList,
        protected \MageSuite\FreeGift\Service\GiftItem $giftItem
    ) {
    }

    /**
     * Delete related gift items when the quantity of the main item changed
     */
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');

        if ($quote->getData('gift_items_reseted') || !$this->canResetGiftItems($quote)) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            $this->resetGiftItemsForChangedQuoteItem($quote, $quoteItem);
        }

        $quote->setData('gift_items_reseted', true);
    }

    protected function canResetGiftItems(\Magento\Quote\Model\Quote $quote): bool
    {
        if ($quote->getAllItems() == null) {
            return false;
        }

        $address = $quote->getShippingAddress();

        return $address->getAddressType() == \Magento\Quote\Model\Quote\Address::TYPE_SHIPPING;
    }

    protected function resetGiftItemsForChangedQuoteItem(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Item $quoteItem): void
    {
        $originalProductSkuOption = $quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU);
        if ($originalProductSkuOption instanceof \Magento\Quote\Model\Quote\Item\Option) {
            return;
        }

        $originalQty = (int)$quoteItem->getOrigData('qty');
        if ($originalQty <= 0 || $originalQty == $quoteItem->getQty()) {
            return;
        }

        $appliedRules = $quoteItem->getAppliedRuleIds();
        if ($appliedRules == null) {
            return;
        }

        $productSku = $quoteItem->getProduct()->getSku();
        foreach (explode(',', $appliedRules) as $ruleId) {
            $this->removeRelatedGiftItems($quote, $productSku, (int)trim($ruleId));
        }

        $quoteItem->setAppliedRuleIds(null);
    }

    protected function removeRelatedGiftItems(\Magento\Quote\Model\Quote $quote, string $productSku, int $ruleId): void
    {
        if ($ruleId <= 0) {
            return;
        }

        foreach ($quote->getAllItems() as $toDeleteItem) {
            if (!$this->giftItem->isRelatedToProductAndRule($toDeleteItem, $productSku, $ruleId)) {
                continue;
            }

            $this->removeGiftItem($quote, $toDeleteItem, $ruleId);
        }
    }

    protected function removeGiftItem(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Item $quoteItem, int $ruleId): void
    {
        $quote->deleteItem($quoteItem);
        $this->quoteItemQtyList->removeQuoteItem($quoteItem->getId());

        if (!$this->giftItem->isAddedOnce($quoteItem)) {
            return;
        }

        $this->giftItem->removeRuleIdFromAllQuoteItems($quote, $ruleId);
    }
}

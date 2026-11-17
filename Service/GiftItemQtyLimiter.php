<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Service;

class GiftItemQtyLimiter
{
    public function __construct(
        protected \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        protected \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter,
        protected \MageSuite\FreeGift\Service\GiftItem $giftItem,
        protected \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $quoteItemQtyList
    ) {
    }

    public function limit(\Magento\Quote\Model\Quote $quote): void
    {
        $giftItemsByRuleId = $this->getGiftItemsGroupedByRuleId($quote);

        if ($giftItemsByRuleId === []) {
            return;
        }

        foreach ($this->getRules(array_keys($giftItemsByRuleId)) as $rule) {
            $this->limitGiftItemsOfRule($rule, $giftItemsByRuleId[(int)$rule->getId()]);
        }
    }

    protected function getGiftItemsGroupedByRuleId(\Magento\Quote\Model\Quote $quote): array
    {
        $giftItemsByRuleId = [];

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $ruleId = $this->giftItem->getRuleId($quoteItem);

            if ($ruleId === null || $this->giftItem->getOriginalProductSku($quoteItem) === null) {
                continue;
            }

            $giftItemsByRuleId[$ruleId][] = $quoteItem;
        }

        return $giftItemsByRuleId;
    }

    protected function getRules(array $ruleIds): array
    {
        $ruleCollection = $this->ruleCollectionFactory->create();
        $ruleCollection->addFieldToFilter('rule_id', ['in' => $ruleIds]);
        $ruleCollection->addFieldToFilter('simple_action', ['in' => [\MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION, \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION]]);

        return $ruleCollection->getItems();
    }

    protected function limitGiftItemsOfRule(\Magento\SalesRule\Model\Rule $rule, array $giftItems): void
    {
        $grantedQtyPerSku = $this->getGrantedQtyPerSku($rule);

        foreach ($this->groupGiftItemsBySkuAndOriginalProductSku($giftItems) as $giftItemsGroup) {
            $allowedQty = $this->getAllowedQty($rule, $grantedQtyPerSku, reset($giftItemsGroup));
            $this->limitGiftItemsGroup($rule, $giftItemsGroup, $allowedQty);
        }
    }

    protected function getGrantedQtyPerSku(\Magento\SalesRule\Model\Rule $rule): array
    {
        $grantedQtyPerSku = [];

        foreach ($this->ruleToFreeGiftsConverter->getFreeGifts($rule) as $freeGift) {
            $sku = $this->normalizeSku((string)$freeGift->getSku());
            $grantedQtyPerSku[$sku] = ($grantedQtyPerSku[$sku] ?? 0.0) + (float)$freeGift->getQty();
        }

        return $grantedQtyPerSku;
    }

    protected function groupGiftItemsBySkuAndOriginalProductSku(array $giftItems): array
    {
        $giftItemsGroups = [];

        foreach ($giftItems as $giftItem) {
            $groupKey = sprintf('%s|%s', $this->getGiftSku($giftItem), $this->giftItem->getOriginalProductSku($giftItem));
            $giftItemsGroups[$groupKey][] = $giftItem;
        }

        return $giftItemsGroups;
    }

    protected function getAllowedQty(\Magento\SalesRule\Model\Rule $rule, array $grantedQtyPerSku, \Magento\Quote\Model\Quote\Item $giftItem): float
    {
        $grantedQty = $grantedQtyPerSku[$this->getGiftSku($giftItem)] ?? 0.0;

        if ($rule->getSimpleAction() !== \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION) {
            return $grantedQty;
        }

        return $grantedQty * $this->getOriginalProductQty($giftItem->getQuote(), (string)$this->giftItem->getOriginalProductSku($giftItem));
    }

    protected function getOriginalProductQty(\Magento\Quote\Model\Quote $quote, string $originalProductSku): float
    {
        $originalProductQty = 0.0;

        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($this->giftItem->getOriginalProductSku($quoteItem) !== null || $quoteItem->getProduct()->getSku() !== $originalProductSku) {
                continue;
            }

            $originalProductQty += (float)$quoteItem->getQty();
        }

        return $originalProductQty;
    }

    protected function limitGiftItemsGroup(\Magento\SalesRule\Model\Rule $rule, array $giftItemsGroup, float $allowedQty): void
    {
        $remainingQty = $allowedQty;

        foreach ($giftItemsGroup as $giftItem) {
            $remainingQty = $this->limitGiftItem($rule, $giftItem, $remainingQty);
        }
    }

    protected function limitGiftItem(\Magento\SalesRule\Model\Rule $rule, \Magento\Quote\Model\Quote\Item $giftItem, float $remainingQty): float
    {
        if ($remainingQty <= 0) {
            $this->removeGiftItem($rule, $giftItem);

            return 0.0;
        }

        $giftItemQty = (float)$giftItem->getQty();

        if ($giftItemQty > $remainingQty) {
            $giftItem->setQty($remainingQty);

            return 0.0;
        }

        return $remainingQty - $giftItemQty;
    }

    protected function removeGiftItem(\Magento\SalesRule\Model\Rule $rule, \Magento\Quote\Model\Quote\Item $giftItem): void
    {
        $quote = $giftItem->getQuote();
        $quote->deleteItem($giftItem);
        $this->quoteItemQtyList->removeQuoteItem($giftItem->getId());

        if (!$this->giftItem->isAddedOnce($giftItem)) {
            return;
        }

        $this->giftItem->removeRuleIdFromAllQuoteItems($quote, (int)$rule->getId());
    }

    protected function getGiftSku(\Magento\Quote\Model\Quote\Item $giftItem): string
    {
        return $this->normalizeSku((string)$giftItem->getProduct()->getSku());
    }

    protected function normalizeSku(string $sku): string
    {
        return mb_strtolower(trim($sku));
    }
}

<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Plugin\Magento\Checkout\Model\Cart;

class DisallowChangingQtyOfFreeGift
{
    protected const SUPPORTED_RULE_ACTIONS = [
        \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION,
        \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION,
    ];

    public function __construct(
        protected \Magento\Framework\Message\ManagerInterface $messageManager,
        protected \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        protected \Magento\SalesRule\Model\ResourceModel\Rule $ruleResource,
        protected \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter,
        protected \MageSuite\FreeGift\Service\GiftItem $giftItem
    ) {
    }

    public function beforeUpdateItems(\Magento\Checkout\Model\Cart $subject, array $data): array
    {
        $quote = $subject->getQuote();
        $decidedQty = [];

        foreach ($data as $itemId => $value) {
            $item = $quote->getItemById($itemId);

            if (!$item || !$this->isGiftItem($item)) {
                continue;
            }

            $oldQty = (float)$item->getQty();
            $requestedQty = $value['qty'] ?? null;

            if (!is_numeric($requestedQty) || (float)$requestedQty <= $oldQty) {
                continue;
            }

            $maxQty = $this->getMaxAllowedQty($quote, $item, $decidedQty);

            if ($maxQty !== null && (float)$requestedQty <= $maxQty) {
                $decidedQty[$itemId] = (float)$requestedQty;
                continue;
            }

            $data[$itemId]['qty'] = $oldQty;
            $data[$itemId]['before_suggest_qty'] = $oldQty;
            $decidedQty[$itemId] = $oldQty;

            $this->messageManager->addErrorMessage(__('Quantity of promotional item cannot be increased.'));
        }

        return [$data];
    }

    protected function getMaxAllowedQty(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Item $giftQuoteItem, array $decidedQty): ?float
    {
        $ruleId = $this->giftItem->getRuleId($giftQuoteItem);

        if ($ruleId === null) {
            return null;
        }

        $rule = $this->ruleFactory->create();
        $this->ruleResource->load($rule, $ruleId);

        if (!in_array($rule->getSimpleAction(), self::SUPPORTED_RULE_ACTIONS, true)) {
            return null;
        }

        $ruleQty = $this->getRuleQtyOfGift($rule, $giftQuoteItem);

        if ($ruleQty <= 0) {
            return null;
        }

        if ($rule->getSimpleAction() === \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION) {
            $originalQty = $this->getOriginalProductQty($quote, $giftQuoteItem);

            if ($originalQty === null) {
                return null;
            }

            $ruleQty *= $originalQty;
        }

        return max(0.0, $ruleQty - $this->getQtyOfOtherGiftItems($quote, $giftQuoteItem, $decidedQty));
    }

    protected function getRuleQtyOfGift(\Magento\SalesRule\Model\Rule $rule, \Magento\Quote\Model\Quote\Item $giftQuoteItem): float
    {
        $giftSku = (string)$giftQuoteItem->getProduct()->getSku();
        $ruleQty = 0.0;

        foreach ($this->ruleToFreeGiftsConverter->getFreeGifts($rule) as $freeGift) {
            if ($this->isSameSku((string)$freeGift->getSku(), $giftSku)) {
                $ruleQty += (float)$freeGift->getQty();
            }
        }

        return $ruleQty;
    }

    protected function getQtyOfOtherGiftItems(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Item $giftQuoteItem, array $decidedQty): float
    {
        $ruleId = (int)$this->giftItem->getRuleId($giftQuoteItem);
        $giftSku = (string)$giftQuoteItem->getProduct()->getSku();
        $originalSku = $this->getOriginalProductSku($giftQuoteItem);
        $qty = 0.0;

        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getId() == $giftQuoteItem->getId() || !$this->isSameSku((string)$quoteItem->getProduct()->getSku(), $giftSku)) {
                continue;
            }

            if (!$this->giftItem->isRelatedToProductAndRule($quoteItem, $originalSku, $ruleId)) {
                continue;
            }

            $qty += (float)($decidedQty[$quoteItem->getId()] ?? $quoteItem->getQty());
        }

        return $qty;
    }

    protected function getOriginalProductQty(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Item $giftQuoteItem): ?float
    {
        $originalSku = $this->getOriginalProductSku($giftQuoteItem);

        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getParentItem() || $this->isGiftItem($quoteItem)) {
                continue;
            }

            if ($this->isSameSku((string)$quoteItem->getProduct()->getSku(), $originalSku)) {
                return (float)$quoteItem->getQty();
            }
        }

        return null;
    }

    protected function isGiftItem(\Magento\Quote\Model\Quote\Item $quoteItem): bool
    {
        return $quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU) instanceof \Magento\Quote\Model\Quote\Item\Option;
    }

    protected function getOriginalProductSku(\Magento\Quote\Model\Quote\Item $giftQuoteItem): string
    {
        return (string)$giftQuoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)->getValue();
    }

    protected function isSameSku(string $first, string $second): bool
    {
        return strcasecmp(trim($first), trim($second)) === 0;
    }
}

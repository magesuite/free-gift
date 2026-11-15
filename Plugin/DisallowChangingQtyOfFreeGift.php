<?php

namespace MageSuite\FreeGift\Plugin;

class DisallowChangingQtyOfFreeGift
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    protected \Magento\SalesRule\Model\RuleFactory $ruleFactory;

    protected \Magento\SalesRule\Model\ResourceModel\Rule $ruleResource;

    protected \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter;

    protected \MageSuite\FreeGift\Service\GiftItem $giftItem;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Model\ResourceModel\Rule $ruleResource,
        \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter,
        \MageSuite\FreeGift\Service\GiftItem $giftItem
    ) {
        $this->messageManager = $messageManager;
        $this->ruleFactory = $ruleFactory;
        $this->ruleResource = $ruleResource;
        $this->ruleToFreeGiftsConverter = $ruleToFreeGiftsConverter;
        $this->giftItem = $giftItem;
    }

    /**
     * When qty of free gift is raised above the qty granted by the rule we revert qty to old value
     * @param \Magento\Checkout\Model\Cart $subject
     * @param $data
     * @return array
     */
    public function beforeupdateItems(\Magento\Checkout\Model\Cart $subject, $data)
    {
        $quote = $subject->getQuote();

        $decidedQty = [];

        foreach ($data as $itemId => $value) {
            $item = $quote->getItemById($itemId);

            if(!$item){
                continue;
            }

            if (!$item->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)
                instanceof \Magento\Quote\Model\Quote\Item\Option
            ) {
                continue;
            }

            $oldQty = (float)$item->getQty();
            $requestedQty = $value['qty'] ?? null;

            if (!is_numeric($requestedQty) || (float)$requestedQty <= $oldQty) {
                continue;
            }

            $maxQty = $this->getMaxAllowedQty($quote, $item, $decidedQty);

            if ($maxQty === null || (float)$requestedQty > $maxQty) {
                $data[$itemId]['qty'] = $oldQty;
                $data[$itemId]['before_suggest_qty'] = $oldQty;
                $decidedQty[$itemId] = $oldQty;

                $this->messageManager->addErrorMessage(__('Quantity of promotional item cannot be increased.'));

                continue;
            }

            $decidedQty[$itemId] = (float)$requestedQty;
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

        $simpleAction = $rule->getSimpleAction();

        if (!in_array($simpleAction, [
            \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION,
            \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION
        ], true)) {
            return null;
        }

        $giftSku = $giftQuoteItem->getProduct()->getSku();
        $ruleQty = 0.0;

        foreach ($this->ruleToFreeGiftsConverter->getFreeGifts($rule) as $freeGift) {
            if ($this->isSameSku((string)$freeGift->getSku(), $giftSku)) {
                $ruleQty += (float)$freeGift->getQty();
            }
        }

        if ($ruleQty <= 0) {
            return null;
        }

        if ($simpleAction === \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION) {
            $originalQty = $this->getOriginalProductQty($quote, $giftQuoteItem);

            if ($originalQty === null) {
                return null;
            }

            $ruleQty *= $originalQty;
        }

        return max(0.0, $ruleQty - $this->getQtyOfOtherGiftItems($quote, $giftQuoteItem, $ruleId, $giftSku, $decidedQty));
    }

    protected function getQtyOfOtherGiftItems(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Item $giftQuoteItem,
        int $ruleId,
        string $giftSku,
        array $decidedQty
    ): float {
        $originalSku = (string)$giftQuoteItem
            ->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)
            ->getValue();
        $qty = 0.0;

        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getId() == $giftQuoteItem->getId()
                || !$this->isSameSku((string)$quoteItem->getProduct()->getSku(), $giftSku)
                || !$this->giftItem->isRelatedToProductAndRule($quoteItem, $originalSku, $ruleId)
            ) {
                continue;
            }

            $qty += (float)($decidedQty[$quoteItem->getId()] ?? $quoteItem->getQty());
        }

        return $qty;
    }

    protected function getOriginalProductQty(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Item $giftQuoteItem): ?float
    {
        $originalSku = (string)$giftQuoteItem
            ->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)
            ->getValue();

        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getParentItem()
                || $quoteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)
                instanceof \Magento\Quote\Model\Quote\Item\Option
            ) {
                continue;
            }

            if ($this->isSameSku((string)$quoteItem->getProduct()->getSku(), $originalSku)) {
                return (float)$quoteItem->getQty();
            }
        }

        return null;
    }

    protected function isSameSku(string $first, string $second): bool
    {
        return strcasecmp(trim($first), trim($second)) === 0;
    }
}

<?php

namespace MageSuite\FreeGift\SalesRule\Action;

abstract class AbstractGiftAction
{
    const ORIGINAL_PRODUCT_SKU = 'original_product_sku';
    const ITEM_OPTION_COUPON_GIFT = 'is_gift_from_coupon';
    const RULE_DATA_KEY_SKU = 'gift_skus';
    const RULE_DATA_KEY_SKU_DISCOUNTS = 'gift_skus_discounts';
    const RULE_DATA_KEY_SKU_QTY = 'gift_skus_qty';
    const PRODUCT_TYPE_FREEPRODUCT = 'freeproduct_gift';
    const APPLIED_FREEPRODUCT_RULE_IDS = '_freeproduct_applied_rules';
    const GIFT_ADDED_ONCE = 'gift_added_once';

    protected \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory;
    protected \Psr\Log\LoggerInterface $logger;
    protected \MageSuite\FreeGift\Service\Cart $cartService;
    protected \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter;
    protected \Magento\Checkout\Model\Cart $cart;

    public function __construct(
        \Magento\SalesRule\Model\Rule\Action\Discount\DataFactory $discountDataFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Checkout\Model\Cart $cart,
        \MageSuite\FreeGift\Service\Cart $cartService,
        \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter
    ) {
        $this->discountDataFactory = $discountDataFactory;
        $this->logger = $logger;
        $this->cartService = $cartService;
        $this->ruleToFreeGiftsConverter = $ruleToFreeGiftsConverter;
        $this->cart = $cart;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param float $qty
     * @return bool|\Magento\SalesRule\Model\Rule\Action\Discount\Data
     */
    public function calculate(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $qty,
        bool $process = false
    ) {
        if (!$process) {
            return $this->getDiscountData($item);
        }

        $originalAppliedRuleIds = $item->getAppliedRuleIds();

        if ($item->getAppliedRuleIds() !== null &&
            in_array($rule->getId(), explode(',', $item->getAppliedRuleIds()))
        ) {
            return false;
        }

        $freeGifts = $this->ruleToFreeGiftsConverter->getFreeGifts($rule);

        try {
            foreach ($freeGifts as $gift) {
                $quote = $item->getQuote();
                $itemQty = $gift->getQty();

                if ($this->isMultipliedByProductQty()) {
                    $itemQty = $itemQty*$item->getQty();
                }

                if (!$this->isAppliedForEveryItemInCart() &&
                    $this->ruleWasAlreadyUsed($quote, $rule)
                ) {
                    continue;
                }

                $addToCartRequest = $this->cartService->getAddToCartRequest($gift->getSku(), $itemQty, $gift->getDiscountPercentage());

                if ($addToCartRequest === null) {
                    continue;
                }

                $addToCartRequest['product']->addCustomOption(static::ORIGINAL_PRODUCT_SKU, $item->getProduct()->getSku());

                if (!$this->isAppliedForEveryItemInCart()) {
                    $addToCartRequest['product']->addCustomOption(self::GIFT_ADDED_ONCE, true);
                }

                $addToCartRequest['product']->addCustomOption('rule_id', $rule->getId());
                $quoteItem = $quote->addProduct($addToCartRequest['product'], $addToCartRequest['request']);
                $this->resetShippingAddressesCache($quote);

                if (is_string($quoteItem)) {
                    throw new \Exception($quoteItem);
                }

                $quoteItem->setIsGift(true);

                if (isset($addToCartRequest['request']['custom_price']) &&
                    $addToCartRequest['request']['custom_price'] == 0
                ) {
                    $quoteItem->setCustomPrice(0);
                    $quoteItem->setOriginalCustomPrice(0);
                }
            }

            $item->setAppliedRuleIds($originalAppliedRuleIds);
            $this->addAppliedItemRuleId($rule->getRuleId(), $item);

        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Exception occurred while adding gift product to cart. Rule: %d, Exception: %s', $rule->getId(), $e->getMessage()),
                [__METHOD__]
            );
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function fixQuantity($qty, $rule)
    {
        return $qty;
    }

    /**
     * @param int $ruleId
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return void
     */
    protected function addAppliedItemRuleId(int $ruleId, \Magento\Quote\Model\Quote\Item $item):void
    {
        $appliedRules = $item->getAppliedRuleIds();

        if ($appliedRules == null) {
            $appliedRules = [];
        } else {
            $appliedRules = explode(',', $appliedRules);
        }

        $appliedRules[] = $ruleId;

        $item->setAppliedRuleIds(implode(',', $appliedRules));
    }

    /**
     * No discount is changed by GiftAction, but the existing has to be preserved
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return \Magento\SalesRule\Model\Rule\Action\Discount\Data
     */
    protected function getDiscountData(\Magento\Quote\Model\Quote\Item\AbstractItem $item)
    {
        return $this->discountDataFactory->create([
            'amount' => $item->getDiscountAmount(),
            'baseAmount' => $item->getBaseDiscountAmount(),
            'originalAmount' => $item->getOriginalDiscountAmount(),
            'baseOriginalAmount' => $item->getBaseOriginalDiscountAmount()
        ]);
    }

    protected function ruleWasAlreadyUsed($quote, $rule):bool
    {
        foreach ($quote->getAllItems() as $item) {
            if ($item->getOptionByCode('rule_id') instanceof \Magento\Quote\Model\Quote\Item\Option
                && $item->getOptionByCode('rule_id')->getValue() == $rule->getId()
                && !$item->isDeleted()
            ) {
                return true;
            }
        }

        return false;
    }

    protected function resetShippingAddressesCache($quote): void
    {
        if (!$quote->isVirtual() && $quote->getShippingAddress()) {
            $quote->getShippingAddress()->unsetData('cached_items_all');
        }

        $quote->getBillingAddress()->unsetData('cached_items_all');
    }

    abstract protected function isAppliedForEveryItemInCart();
    abstract protected function isMultipliedByProductQty();

}

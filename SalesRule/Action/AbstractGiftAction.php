<?php

namespace MageSuite\FreeGift\SalesRule\Action;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule\Action\Discount;

use Psr\Log\LoggerInterface;

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

    /**
     * @var Discount\DataFactory
     */
    protected $discountDataFactory;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \MageSuite\FreeGift\Service\Cart
     */
    protected $cartService;
    /**
     * @var \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter
     */
    protected $ruleToFreeGiftsConverter;
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @param Discount\DataFactory $discountDataFactory
     * @param ProductRepositoryInterface $productRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Discount\DataFactory $discountDataFactory,
        LoggerInterface $logger,
        \Magento\Checkout\Model\Cart $cart,
        \MageSuite\FreeGift\Service\Cart $cartService,
        \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter $ruleToFreeGiftsConverter
    )
    {
        $this->discountDataFactory = $discountDataFactory;
        $this->logger = $logger;
        $this->cartService = $cartService;
        $this->ruleToFreeGiftsConverter = $ruleToFreeGiftsConverter;
        $this->cart = $cart;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param AbstractItem $item
     * @param float $qty
     * @return Discount\Data
     */
    public function calculate($rule, $item, $qty, $process = false)
    {
        if(!$process) {
            return $this->getDiscountData($item);
        }

        $originalAppliedRuleIds = $item->getAppliedRuleIds();

        if($item->getAppliedRuleIds() != null and in_array($rule->getId(), explode(',', $item->getAppliedRuleIds()))) {
            return false;
        }

        $freeGifts = $this->ruleToFreeGiftsConverter->getFreeGifts($rule);

        try {
            foreach($freeGifts as $gift) {
                $itemQty = $gift->getQty();

                if($this->isMultipliedByProductQty())  {
                    $itemQty = $itemQty*$item->getQty();
                }

                if(!$this->isAppliedForEveryItemInCart()) {
                    if($this->ruleWasAlreadyUsed($item->getQuote(), $rule)) {
                        continue;
                    }
                }

                $addToCartRequest = $this->cartService->getAddToCartRequest($gift->getSku(), $itemQty, $gift->getDiscountPercentage());

                if($addToCartRequest == null) {
                    continue;
                }

                $addToCartRequest['product']->addCustomOption(static::ORIGINAL_PRODUCT_SKU, $item->getProduct()->getSku());

                if(!$this->isAppliedForEveryItemInCart()) {
                    $addToCartRequest['product']->addCustomOption(self::GIFT_ADDED_ONCE, true);
                }

                $addToCartRequest['product']->addCustomOption('rule_id', $rule->getId());

                $quoteItem = $item->getQuote()->addProduct($addToCartRequest['product'], $addToCartRequest['request']);

                if (is_string($quoteItem)) {
                    throw new \Exception($quoteItem);
                }

                $quoteItem->setIsGift(true);

                if(isset($addToCartRequest['request']['custom_price']) and $addToCartRequest['request']['custom_price'] == 0) {
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
     * @param Address $address
     */
    protected function addAppliedItemRuleId(int $ruleId, \Magento\Quote\Model\Quote\Item $item)
    {
        $appliedRules = $item->getAppliedRuleIds();

        if($appliedRules == null) {
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
     * @param AbstractItem $item
     * @return Discount\Data
     */
    protected function getDiscountData(AbstractItem $item)
    {
        return $this->discountDataFactory->create([
            'amount' => $item->getDiscountAmount(),
            'baseAmount' => $item->getBaseDiscountAmount(),
            'originalAmount' => $item->getOriginalDiscountAmount(),
            'baseOriginalAmount' => $item->getBaseOriginalDiscountAmount()
        ]);
    }

    protected function ruleWasAlreadyUsed($quote, $rule) {
        foreach($quote->getAllItems() as $item) {
            if(
                $item->getOptionByCode('rule_id') instanceof \Magento\Quote\Model\Quote\Item\Option
                and $item->getOptionByCode('rule_id')->getValue() == $rule->getId()
                and !$item->isDeleted()
            ) {
                return true;
            }
        }

        return false;
    }

    abstract protected function isAppliedForEveryItemInCart();
    abstract protected function isMultipliedByProductQty();
}
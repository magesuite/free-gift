<?php

namespace MageSuite\FreeGift\Service;

class CouponRelatedGiftRemover
{
    const OPTION_RULE_ID = 'rule_id';

    /**
     * @var \MageSuite\FreeGift\Model\Command\GetSalesRuleByCouponCode
     */
    protected $getSalesRuleByCouponCode;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    public function __construct(
        \MageSuite\FreeGift\Model\Command\GetSalesRuleByCouponCode $getSalesRuleByCouponCode,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->getSalesRuleByCouponCode = $getSalesRuleByCouponCode;
        $this->productRepository = $productRepository;
    }

    public function execute(\Magento\Quote\Model\Quote $quote)
    {
        $couponRule = $this->getSalesRuleByCouponCode->execute($quote->getCouponCode());

        if ($couponRule) {
            $relatedItemSkus = $this->removeGiftsFromQuote($quote, $couponRule->getId());
            $this->removeRuleFromRelatedItems($quote, $relatedItemSkus, $couponRule->getId());
        }
    }

    protected function removeGiftsFromQuote(\Magento\Quote\Model\Quote $quote, $couponRuleId)
    {
        $parentItemSkus = [];
        foreach ($quote->getAllItems() as $item) {
            $itemRule = $item->getOptionByCode(self::OPTION_RULE_ID);

            if ($itemRule && $itemRule->getValue() == $couponRuleId) {
                $quote->removeItem($item->getId());

                $itemOption = $item->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU);
                $parentItemSkus[] = $itemOption->getValue();
            }
        }

        return $parentItemSkus;
    }

    protected function removeRuleFromRelatedItems(\Magento\Quote\Model\Quote $quote, array $relatedItemSkus, $couponRule)
    {
        foreach ($relatedItemSkus as $itemSku) {
            $product = $this->productRepository->get($itemSku);
            $quoteItem = $quote->getItemByProduct($product);

            $appliedRules = $this->removeRuleIdFromString($couponRule, $quote->getAppliedRuleIds());
            $quoteItem->setAppliedRuleIds($appliedRules);
        }
    }

    protected function removeRuleIdFromString($ruleId, $appliedRules)
    {
        if($appliedRules === null) {
            return '';
        }

        $appliedRules = explode(',', $appliedRules);
        unset($appliedRules[array_search($ruleId, $appliedRules)]);

        return implode(',', $appliedRules);
    }
}

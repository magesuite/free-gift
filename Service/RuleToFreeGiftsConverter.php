<?php

namespace MageSuite\FreeGift\Service;

class RuleToFreeGiftsConverter
{
    const DEFAULT_DISCOUNT = 100;
    const DEFAULT_QTY = 1;

    /**
     * @var \MageSuite\FreeGift\Model\FreeGiftFactory
     */
    protected $freeGiftFactory;

    public function __construct(\MageSuite\FreeGift\Model\FreeGiftFactory $freeGiftFactory)
    {
        $this->freeGiftFactory = $freeGiftFactory;
    }

    /**
     * @param $rule \Magento\SalesRule\Model\Rule
     * @return \MageSuite\FreeGift\Model\FreeGift[]
     */
    public function getFreeGifts($rule) {
        $skus = explode(',', $rule->getData(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU));
        $discounts = explode(',', $rule->getData(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_DISCOUNTS));
        $qtys = explode(',', $rule->getData(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY));

        $freeGifts = [];

        if(empty($skus)) {
            return $freeGifts;
        }

        foreach($skus as $index => $sku) {
            /** @var \MageSuite\FreeGift\Model\FreeGift $freeGift */
            $freeGift = $this->freeGiftFactory->create();

            $freeGift->setSku($sku);
            $freeGift->setDiscountPercentage(isset($discounts[$index]) ? $discounts[$index] : self::DEFAULT_DISCOUNT);
            $freeGift->setQty(isset($qtys[$index]) ? $qtys[$index] : self::DEFAULT_QTY);

            $freeGifts[] = $freeGift;
        }

        return $freeGifts;
    }
}
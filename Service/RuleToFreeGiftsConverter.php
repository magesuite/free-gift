<?php

declare(strict_types=1);
namespace MageSuite\FreeGift\Service;

class RuleToFreeGiftsConverter
{
    protected const DEFAULT_DISCOUNT = 100;
    protected const DEFAULT_QTY = 1;

    protected \MageSuite\FreeGift\Model\FreeGiftFactory $freeGiftFactory;

    public function __construct(\MageSuite\FreeGift\Model\FreeGiftFactory $freeGiftFactory)
    {
        $this->freeGiftFactory = $freeGiftFactory;
    }

    /**
     * @param $rule \Magento\SalesRule\Model\Rule
     * @return \MageSuite\FreeGift\Model\FreeGift[]
     */
    public function getFreeGifts(\Magento\SalesRule\Model\Rule $rule):array {
        $skus = $this->getRuleAttributeData(
            $rule,
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU
        );
        $discounts = $this->getRuleAttributeData(
            $rule,
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_DISCOUNTS
        );
        $qtys = $this->getRuleAttributeData(
            $rule,
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY
        );
        $freeGifts = [];

        if(empty($skus)) {
            return $freeGifts;
        }

        foreach($skus as $index => $sku) {
            /** @var \MageSuite\FreeGift\Model\FreeGift $freeGift */
            $freeGift = $this->freeGiftFactory->create();
            $freeGift->setSku($sku);
            $freeGift->setDiscountPercentage($this->getDiscount($discounts, $index));
            $freeGift->setQty($this->getQty($qtys, $index));
            $freeGifts[] = $freeGift;
        }

        return $freeGifts;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @return array
     */
    protected function getRuleAttributeData(\Magento\SalesRule\Model\Rule $rule, string $attribute):array {
        return explode(',', (string) $rule->getData($attribute)) ?: [];
    }

    /**
     * @param array $discounts
     * @param int $index
     * @return float
     */
    protected function getDiscount(array $discounts, int $index):float {
        if(! isset($discounts[$index]) || $discounts[$index] == "") {
            return self::DEFAULT_DISCOUNT;
        }

        return (float) str_replace(",", ".", $discounts[$index]);
    }

    /**
     * @param array $qtys
     * @param int $index
     * @return float
     */
    protected function getQty(array $qtys, int $index):float {
        if(! isset($qtys[$index]) || $qtys[$index] == "") {
            return self::DEFAULT_QTY;
        }

        return (float) str_replace(",", ".", $qtys[$index]);
    }
}

<?php

namespace MageSuite\FreeGift\Model;

class SalesRuleCalculator extends \Magento\SalesRule\Model\Validator
{
    protected $supportedRules = [
        \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION,
        \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION
    ];

    /**
     * @var bool
     */
    protected $isProcessed = false;

    /**
     * @param $items
     * @param $address
     */
    public function processAllItems($items, $address)
    {
        foreach($items as $item) {
            $this->process($item, $address);
        }
        if (!$this->isProcessed) {
            $this->isProcessed = true;
            $address->getQuote()->collectTotals();
        }
    }

    /**
     * Applying free gift rules
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return $this
     */
    public function process(\Magento\Quote\Model\Quote\Item\AbstractItem $item, $address = null)
    {
        $rules = $this->_getRules($address);

        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($rules as $rule) {
            if (!in_array($rule->getSimpleAction(), $this->supportedRules)) {
                continue;
            }

            if (!$this->canApplyRule($item, $rule, $address)) {
                $this->removeGiftItemsRelatedToItemAndRule($item, $rule);
                continue;
            }

            $this->applyRule($rule, $item);
        }

        return $this;
    }

    protected function canApplyRule($item, $rule, $address)
    {
        if (!$this->canApplyDiscount($item)) {
            return false;
        }

        if (!$this->validatorUtility->canProcessRule($rule, $address)) {
            return false;
        }

        if (!$rule->getActions()->validate($item)) {
            $childItems = $item->getChildren();
            $isContinue = true;
            if (!empty($childItems)) {
                foreach ($childItems as $childItem) {
                    if ($rule->getActions()->validate($childItem)) {
                        $isContinue = false;
                    }
                }
            }
            if ($isContinue) {
                return false;
            }
        }

        return true;
    }

    protected function applyRule($rule, $item)
    {
        $calculatorFactory = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory::class);
        $ruleCalculator = $calculatorFactory->create($rule->getSimpleAction());

        if ($ruleCalculator == null) {
            return false;
        }

        if ($item->getParentItem()) {
            return false;
        }

        return $ruleCalculator->calculate($rule, $item, $item->getTotalQty(), true);
    }

    /**
     * If item does not validate anymore against rule that was applied before we need to remove all
     * gift items that were added in relation to that specific item.
     * This handles case when rule no longer applies for item.
     * @param $item
     * @param $rule
     */
    protected function removeGiftItemsRelatedToItemAndRule($item, $rule)
    {
        $ruleId = $rule->getId();

        if($item->getAppliedRuleIds() === null || !in_array($ruleId, explode(',', $item->getAppliedRuleIds()))) {
            return;
        }

        $quote = $item->getQuote();
        $productSku = $item->getProduct()->getSku();

        foreach ($quote->getAllItems() as $toDeleteItem) {
            if (!$this->isRelatedGiftItem($toDeleteItem, $productSku, $ruleId)) {
                continue;
            }

            $quote->deleteItem($toDeleteItem);
        }
    }

    /**
     * @param $toDeleteItem
     * @param $productSku
     * @param $ruleId
     * @return bool
     */
    protected function isRelatedGiftItem($toDeleteItem, $productSku, $ruleId)
    {
        return
            (
                $toDeleteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU) instanceof \Magento\Quote\Model\Quote\Item\Option
                and
                $toDeleteItem->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)->getValue() == $productSku
            )
            and
            (
                $toDeleteItem->getOptionByCode('rule_id') instanceof \Magento\Quote\Model\Quote\Item\Option
                and
                $toDeleteItem->getOptionByCode('rule_id')->getValue() == $ruleId
            );
    }
}

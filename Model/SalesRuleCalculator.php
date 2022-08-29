<?php

declare(strict_types=1);
namespace MageSuite\FreeGift\Model;

class SalesRuleCalculator extends \Magento\SalesRule\Model\Validator
{
    protected bool $isProcessed = false;
    protected array $supportedRules = [
        \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION,
        \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION
    ];

    /**
     * @param array $items
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return void
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Validate_Exception
     */
    public function processAllItems(
        array $items,
        \Magento\Quote\Api\Data\CartInterface $quote
    ):void {
        foreach ($items as $item) {
            $this->process($item);
        }

        if (!$this->isProcessed) {
            $this->isProcessed = true;
            $quote->collectTotals();
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return void
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Validate_Exception
     */
    public function process(
        \Magento\Quote\Model\Quote\Item\AbstractItem $item
    ) {
        $address = $item->getAddress();
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
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return bool
     * @throws \Zend_Validate_Exception
     */
    protected function canApplyRule(
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Api\Data\AddressInterface $address
    ):bool {
        if (!$this->canApplyDiscount($item)) {
            return false;
        }

        if (!$this->validatorUtility->canProcessRule($rule, $address)) {
            return false;
        }

        if ($rule->getActions()->validate($item)) {
            return true;
        }

        $childItems = $item->getChildren();
        if (empty($childItems)) {
            return false;
        }

        $isContinue = true;
        foreach ($childItems as $childItem) {
            if ($rule->getActions()->validate($childItem)) {
                $isContinue = false;
                break;
            }
        }
        if ($isContinue) {
            return false;
        }

        return true;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    protected function applyRule(
        \Magento\SalesRule\Model\Rule $rule,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item
    ):bool {
        /** @var \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory */
        $calculatorFactory = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory::class);
        /** @var \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction $ruleCalculator */
        $ruleCalculator = $calculatorFactory->create($rule->getSimpleAction());

        if ($ruleCalculator === null) {
            return false;
        }

        if ($item->getParentItem()) {
            return false;
        }

        return $ruleCalculator->calculate($rule, $item, (float) $item->getTotalQty(), true);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param \Magento\SalesRule\Model\Rule $rule
     * @return void
     */
    protected function removeGiftItemsRelatedToItemAndRule(
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        \Magento\SalesRule\Model\Rule $rule
    ):void {
        $ruleId = (int) $rule->getId();
        $appliedRuleIds = $item->getAppliedRuleIds();
        if (empty($appliedRuleIds) || !in_array($ruleId, explode(',', $appliedRuleIds))) {
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
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $toDeleteItem
     * @param string $productSku
     * @param int $ruleId
     * @return bool
     */
    protected function isRelatedGiftItem(
        \Magento\Quote\Model\Quote\Item\AbstractItem $toDeleteItem,
        string $productSku,
        int $ruleId
    ):bool {
        return
            (
                $toDeleteItem->getOptionByCode(
                    \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU
                ) instanceof \Magento\Quote\Model\Quote\Item\Option
                &&
                $toDeleteItem->getOptionByCode(
                    \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU
                )->getValue() == $productSku
            )
            &&
            (
                $toDeleteItem->getOptionByCode('rule_id') instanceof \Magento\Quote\Model\Quote\Item\Option
                &&
                $toDeleteItem->getOptionByCode('rule_id')->getValue() == $ruleId
            );
    }
}

<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Model;

class SalesRuleCalculator extends \Magento\SalesRule\Model\Validator
{
    protected bool $isProcessed = false;
    protected ?\MageSuite\FreeGift\Service\GiftItem $giftItem = null;
    protected array $supportedRules = [
        \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION,
        \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION
    ];

    public function processAllItems(array $items, \Magento\Quote\Api\Data\CartInterface $quote): void
    {
        foreach ($items as $item) {
            $this->process($item);
        }

        if (!$this->isProcessed) {
            $this->isProcessed = true;
            $quote->collectTotals();
        }
    }

    public function process(\Magento\Quote\Model\Quote\Item\AbstractItem $item, ?\Magento\SalesRule\Model\Rule $rule = null): self
    {
        $address = $item->getAddress();
        $rules = $this->_getRules($address);

        $rulesIds = [];

        /** @var \Magento\SalesRule\Model\Rule $rule */
        foreach ($rules as $rule) {
            $rulesIds[] = $rule->getId();

            if (!in_array($rule->getSimpleAction(), $this->supportedRules)) {
                continue;
            }

            if (!$this->canApplyRule($item, $rule, $address)) {
                $this->removeGiftItemsRelatedToItemAndRule($item, $rule);
                continue;
            }

            $this->applyRule($rule, $item);
        }

        if (!$item->getIsGift()) {
            return $this;
        }

        $ruleId = $this->getGiftItem()->getRuleId($item);

        if (!in_array($ruleId, $rulesIds)) {
            $quote = $item->getQuote();
            $quote->deleteItem($item);
        }

        return $this;
    }

    protected function canApplyRule(\Magento\Quote\Model\Quote\Item\AbstractItem $item, \Magento\SalesRule\Model\Rule $rule, \Magento\Quote\Api\Data\AddressInterface $address): bool
    {
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

    protected function applyRule(\Magento\SalesRule\Model\Rule $rule, \Magento\Quote\Model\Quote\Item\AbstractItem $item): bool
    {
        /** @var \Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory $calculatorFactory */
        $calculatorFactory = \Magento\Framework\App\ObjectManager::getInstance()->create(\Magento\SalesRule\Model\Rule\Action\Discount\CalculatorFactory::class); //phpcs:ignore
        /** @var \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction $ruleCalculator */
        $ruleCalculator = $calculatorFactory->create($rule->getSimpleAction());

        if ($ruleCalculator === null) {
            return false;
        }

        if ($item->getParentItem()) {
            return false;
        }

        return $ruleCalculator->calculate($rule, $item, (float)$item->getTotalQty(), true);
    }

    protected function removeGiftItemsRelatedToItemAndRule(\Magento\Quote\Model\Quote\Item\AbstractItem $item, \Magento\SalesRule\Model\Rule $rule): void
    {
        $ruleId = (int)$rule->getId();
        $appliedRuleIds = $item->getAppliedRuleIds();
        if (empty($appliedRuleIds) || !in_array($ruleId, explode(',', $appliedRuleIds))) {
            return;
        }

        $quote = $item->getQuote();
        $productSku = $item->getProduct()->getSku();
        $giftItem = $this->getGiftItem();

        foreach ($quote->getAllItems() as $toDeleteItem) {
            if (!$giftItem->isRelatedToProductAndRule($toDeleteItem, $productSku, $ruleId)) {
                continue;
            }

            $quote->deleteItem($toDeleteItem);
            if ($giftItem->isAddedOnce($toDeleteItem)) {
                $giftItem->removeRuleIdFromAllQuoteItems($quote, $ruleId);
            }
        }
    }

    protected function getGiftItem(): \MageSuite\FreeGift\Service\GiftItem
    {
        if ($this->giftItem === null) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $this->giftItem = $objectManager->get(\MageSuite\FreeGift\Service\GiftItem::class);
        }

        return $this->giftItem;
    }
}

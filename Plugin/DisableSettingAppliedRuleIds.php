<?php

namespace MageSuite\FreeGift\Plugin;

class DisableSettingAppliedRuleIds
{
    /**
     * @var \Magento\SalesRule\Model\Utility
     */
    protected $validatorUtility;

    protected $giftRules = [
        \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION,
        \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION
    ];

    protected $rules;

    public function __construct(\Magento\SalesRule\Model\Utility $validatorUtility)
    {
        $this->validatorUtility = $validatorUtility;
    }

    public function beforeApplyRules(\Magento\SalesRule\Model\RulesApplier $subject, $item, $rules, $skipValidation, $couponCode) {
        $this->rules = $rules;
    }

    public function afterApplyRules(\Magento\SalesRule\Model\RulesApplier $subject, $result) {
        foreach($result as $appliedRuleId) {
            if(!$this->isGiftRule($appliedRuleId)) {
                continue;
            }

            if (($key = array_search($appliedRuleId, $result)) !== false) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    public function aroundSetAppliedRuleIds(\Magento\SalesRule\Model\RulesApplier $subject, callable $proceed, \Magento\Quote\Model\Quote\Item\AbstractItem $item, array $appliedRuleIds) {
        $address = $item->getAddress();
        $quote = $item->getQuote();

        if(!empty($appliedRuleIds)) {
            $item->setAppliedRuleIds($this->validatorUtility->mergeIds($item->getAppliedRuleIds(), $appliedRuleIds));
        }

        $address->setAppliedRuleIds($this->validatorUtility->mergeIds($address->getAppliedRuleIds(), $appliedRuleIds));
        $quote->setAppliedRuleIds($this->validatorUtility->mergeIds($quote->getAppliedRuleIds(), $appliedRuleIds));

        return $subject;
    }

    protected function isGiftRule($appliedRuleId) {
        foreach($this->rules as $rule) {
            if($rule->getId() == $appliedRuleId and in_array($rule->getSimpleAction(), $this->giftRules)) {
                return true;
            }
        }

        return false;
    }
}
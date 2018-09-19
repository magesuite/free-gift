<?php

namespace MageSuite\FreeGift\Plugin\Rule\Condition;

class AddSubtotalInclTax
{
    public function afterLoadAttributeOptions(\Magento\SalesRule\Model\Rule\Condition\Address $subject, $result) {
        $options = $subject->getAttributeOption();

        $options['base_subtotal_total_incl_tax'] = __('Subtotal including tax');

        ksort($options);

        $subject->setAttributeOption($options);

        return $subject;
    }

    public function aroundGetInputType(\Magento\SalesRule\Model\Rule\Condition\Address $subject, callable $proceed) {
        if($subject->getAttribute() == 'base_subtotal_total_incl_tax') {
            return 'numeric';
        }

        return $proceed();
    }

}
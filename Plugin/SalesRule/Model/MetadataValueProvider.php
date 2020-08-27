<?php

namespace MageSuite\FreeGift\Plugin\SalesRule\Model;

use \Magento\SalesRule\Model\Rule\Metadata\ValueProvider as Source;

class MetadataValueProvider
{
    /**
     * Add the Gift action option to SalesRule
     *
     * @see \Magento\SalesRule\Model\Rule\Metadata\ValueProvider::getMetadataValues
     * @plugin after
     * @param Source $subject
     * @param array $resultMetadataValues
     * @return array
     */
    public function afterGetMetadataValues(Source $subject, $resultMetadataValues)
    {
        $resultMetadataValues['actions']['children']['simple_action']['arguments']['data']['config']['options'][] = [
            'label' => __('Add a gift'),
            'value' =>  \MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION
        ];

        $resultMetadataValues['actions']['children']['simple_action']['arguments']['data']['config']['options'][] = [
            'label' => __('Add a gift once'),
            'value' =>  \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION
        ];

        return $resultMetadataValues;
    }
}
<?php

declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$websiteId = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Store\Model\StoreManagerInterface::class)
    ->getWebsite()
    ->getId();

$salesRule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
$salesRule->setData(
    [
        'name' => 'free gift add once with subtotal condition',
        'is_active' => 1,
        'uses_per_customer' => 0,
        'stop_rules_processing' => 0,
        'is_advanced' => 1,
        'simple_action' => \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION,
        'discount_amount' => '100.0000',
        'discount_qty' => '10.0000',
        'discount_step' => 0,
        'apply_to_shipping' => 0,
        'times_used' => 0,
        'is_rss' => 1,
        'simple_free_shipping' => 0,
        'is_hkp_promo' => 0,
        'is_visible_as_cart_bonus' => 0,
        'is_label_visible_by_default' => 0,
        'customer_group_ids' => [\Magento\Customer\Model\GroupManagement::NOT_LOGGED_IN_ID],
        'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
        'conditions' => [
            [
                "type" => Magento\SalesRule\Model\Rule\Condition\Combine::class,
                "attribute" => null,
                "operator" => null,
                "value" => "1",
                "is_value_processed" => null,
                "aggregator" => "all",
                "conditions" => [
                    [
                        "type" => Magento\SalesRule\Model\Rule\Condition\Address::class,
                        "attribute" => "base_subtotal_total_incl_tax",
                        "operator" => ">",
                        "value" => "200",
                        "is_value_processed" => false
                    ]
                ]
            ]
        ],
        'actions' => [
            [
                "type" => Magento\SalesRule\Model\Rule\Condition\Product\Combine::class,
                "attribute" => null,
                "operator" => null,
                "value" => "1",
                "is_value_processed" => null,
                "aggregator" => "all"
            ]
        ],
        'gift_skus' => 'free-gift-product',
        'gift_skus_discounts' => 100,
        'gift_skus_qty' => 1,
        'coupon_code' => 0,
        'website_ids' => [$websiteId]
    ]
);
$salesRule->save();

<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var Magento\Framework\Registry $registry */
$registry = $objectManager->get(\Magento\Framework\Registry::class);

/** @var \Magento\SalesRule\Model\Rule $salesRule */
$salesRule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
$salesRule->setData([
    'name' => 'Free Gift Rule',
    'is_active' => 1,
    'uses_per_customer' => 0,
    'stop_rules_processing' => 0,
    'is_advanced' => 1,
    'simple_action' => 'add_gift_once',
    'discount_amount' => "100.0000",
    'discount_qty' => "10.0000",
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
    'actions' => [
        [
            "type" => \Magento\SalesRule\Model\Rule\Condition\Product\Combine::class,
            "value" => "1",
            "aggregator" => "all"
        ]
    ],
    'gift_skus' => 'free-gift-product',
    'gift_skus_discounts' => 100,
    'gift_skus_qty' => 1,
    'coupon_code' => 0,
    'website_ids' => [
        \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
            \Magento\Store\Model\StoreManagerInterface::class
        )->getWebsite()->getId()
    ]
]);

$salesRule->getConditions()->loadArray([
    "type" => \Magento\SalesRule\Model\Rule\Condition\Combine::class,
    "attribute" => null,
    "operator" => null,
    "value" => "1",
    "is_value_processed" => null,
    "aggregator" => "all",
    "conditions" =>  [
        [
            "type" => Magento\SalesRule\Model\Rule\Condition\Address::class,
            "attribute" => "base_subtotal_total_incl_tax_not_incl_discount",
            "operator" => ">",
            "value" => "100",
            "is_value_processed" => false
        ]
    ],
]);

$salesRule->save();
$registry->unregister('MageSuite/FreeGift/Test/Integration/_files/cart_rule_free_gift.php');
$registry->register('MageSuite/FreeGift/Test/Integration/_files/cart_rule_free_gift.php', $salesRule->getRuleId());

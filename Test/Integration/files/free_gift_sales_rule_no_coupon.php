<?php
declare(strict_types=1);

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$websiteId = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(\Magento\Store\Model\StoreManagerInterface::class)
    ->getWebsite()
    ->getId();

/** @var \Magento\SalesRule\Model\Rule $salesRule */
$salesRule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
$salesRule->setData(
    [
        'name' => 'Free gift without coupon',
        'is_active' => 1,
        'customer_group_ids' => [\Magento\Customer\Model\GroupManagement::NOT_LOGGED_IN_ID],
        'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
        'simple_action' => \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION,
        'discount_amount' => 0,
        'discount_step' => 0,
        'stop_rules_processing' => 0,
        'website_ids' => [$websiteId],
        'gift_skus' => 'free-gift-product',
        'gift_skus_discounts' => 100,
        'gift_skus_qty' => 1
    ]
);

$salesRule->save();

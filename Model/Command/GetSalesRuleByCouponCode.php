<?php

namespace MageSuite\FreeGift\Model\Command;

class GetSalesRuleByCouponCode
{
    /**
     * @var \Magento\SalesRule\Model\Coupon
     */
    protected $coupon;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $salesRuleCollectionFactory;

    public function __construct(
        \Magento\SalesRule\Model\Coupon $coupon,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory
    ) {
        $this->coupon = $coupon;
        $this->salesRuleCollectionFactory = $salesRuleCollectionFactory;
    }

    public function execute($couponCode)
    {
        $coupon = $this->coupon->loadByCode($couponCode);
        $collection = $this->salesRuleCollectionFactory->create();
        $items = $collection->addFieldToFilter('rule_id', ['eq' => $coupon->getRuleId()])->getItems();

        return array_pop($items);
    }
}

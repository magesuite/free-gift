<?php

declare(strict_types=1);
namespace MageSuite\FreeGift\Observer;

class AddGiftsAfterCollectingTotals implements \Magento\Framework\Event\ObserverInterface
{
    protected \MageSuite\FreeGift\Model\SalesRuleCalculator $salesRuleCalculator;
    protected \Magento\Store\Model\StoreManagerInterface $storeManager;

    public function __construct(
        \MageSuite\FreeGift\Model\SalesRuleCalculator $salesRuleCalculator,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->salesRuleCalculator = $salesRuleCalculator;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer):void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        $store = $this->storeManager->getStore($quote->getStoreId());
        $items = $quote->getAllItems();
        $this->salesRuleCalculator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $quote->getCouponCode());
        $this->salesRuleCalculator->processAllItems($items, $quote);
    }
}

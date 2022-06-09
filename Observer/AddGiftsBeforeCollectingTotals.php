<?php

namespace MageSuite\FreeGift\Observer;

class AddGiftsBeforeCollectingTotals implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \MageSuite\FreeGift\Model\SalesRuleCalculator
     */
    private $salesRuleCalculator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \MageSuite\FreeGift\Model\SalesRuleCalculator $salesRuleCalculator
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
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
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        $store = $this->storeManager->getStore($quote->getStoreId());

        $address = $quote->getShippingAddress();

        $items = $quote->getAllItems();
        $address->setData('cached_items_all', $items);

        $this->salesRuleCalculator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $quote->getCouponCode());
        $this->salesRuleCalculator->processAllItems($items, $address);

        $quote->getShippingAddress()->unsetData('cached_items_all');
        $quote->setFreeItemsAreRecalculated(true);
    }
}

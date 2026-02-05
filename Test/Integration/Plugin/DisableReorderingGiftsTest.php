<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Plugin;

class DisableReorderingGiftsTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected ?\Magento\Framework\App\ObjectManager $objectManager;
    protected ?\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory;
    protected ?\Magento\Quote\Model\QuoteManagement $quoteManagement;
    protected ?\Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    protected ?\Magento\Checkout\Model\Session $checkoutSession;
    protected ?\Magento\Customer\Model\Session $customerSession;
    protected ?\Magento\Quote\Api\CartRepositoryInterface $quoteRepository;
    protected ?\Magento\SalesRule\Model\RuleRepository $ruleRepository;

    protected ?\Magento\Quote\Api\Data\CartInterface $quote;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->quoteCollectionFactory = $this->objectManager->get(\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory::class);
        $this->quoteManagement = $this->objectManager->get(\Magento\Quote\Model\QuoteManagement::class);
        $this->orderRepository = $this->objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $this->checkoutSession = $this->objectManager->get(\Magento\Checkout\Model\Session::class);
        $this->customerSession = $this->objectManager->get(\Magento\Customer\Model\Session::class);
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->ruleRepository = $this->objectManager->get(\Magento\SalesRule\Model\RuleRepository::class);
    }

    /**
     * @magentoAppIsolation disabled
     * @magentoDbIsolation enabled
     * @magentoAppArea frontend
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/product.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/product.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/customer.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/quote.php
     * @magentoConfigFixture default payment/checkmo/active 1
     */
    public function testItDoesNotAddFreeGiftToCartDuringReorderingWhenGiftIsNotAvailableAnymore(): void
    {
        $quote = $this->quoteCollectionFactory->create()
            ->addFieldToFilter('reserved_order_id', 10002)
            ->getFirstItem();
        $orderId = $this->quoteManagement->placeOrder($quote->getId());
        $order = $this->orderRepository->get((int) $orderId);

        $order->setStatus('complete');
        $this->orderRepository->save($order);

        $appliedRuleId = null;
        foreach ($order->getItems() as $item) {
            if ($item->getSku() === 'simple_product_for_free_gift') {
                $appliedRuleId = $item->getAppliedRuleIds();
            }
        }

        $rule = $this->ruleRepository->getById($appliedRuleId);

        $rule->setIsActive(false);
        $this->ruleRepository->save($rule);

        $this->customerSession->setCustomerId((string) $order->getCustomerId());

        $this->objectManager->removeSharedInstance(\Magento\Checkout\Model\Session::class, true);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setParam('order_id', $orderId);
        $this->dispatch('sales/order/reorder/');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->quote = $this->checkoutSession->getQuote();

        $quoteId = $this->checkoutSession->getQuoteId();
        $this->assertNotNull($quoteId);
        $quoteItemsCollection = $this->quoteRepository->get((int)$quoteId)->getItemsCollection();
        $quoteItems = $quoteItemsCollection->getItems();

        $this->assertEquals(1, count($quoteItems));
    }
}

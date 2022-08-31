<?php

namespace MageSuite\FreeGift\Test\Integration\Plugin;

class DisableReorderingGiftsTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var \Magento\Quote\Model\QuoteManagement
     */
    protected $quoteManagement;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\SalesRule\Model\RuleRepository
     */
    protected $ruleRepository;

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
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleNoCoupon
     * @magentoDataFixture loadCustomer
     * @magentoDataFixture loadQuote
     * @magentoConfigFixture default payment/checkmo/active 1
     */
    public function testItDoesNotAddFreeGiftToCartDuringReorderingWhenGiftIsNotAvailableAnymore()
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

    public static function loadProduct()
    {
        include __DIR__ . '/../_files/product.php';
    }

    public static function loadFreeGiftProduct()
    {
        include __DIR__ . '/../_files/free_gift_product.php';
    }

    public static function loadFreeGiftSalesRuleNoCoupon()
    {
        include __DIR__ . '/../_files/free_gift_sales_rule_no_coupon.php';
    }

    public static function loadCustomer()
    {
        include __DIR__ . '/../_files/customer.php';
    }

    public static function loadQuote()
    {
        include __DIR__ . '/../_files/quote.php';
    }
}

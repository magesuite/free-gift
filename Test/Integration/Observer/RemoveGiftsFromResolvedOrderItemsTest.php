<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Observer;

#[\Magento\TestFramework\Fixture\AppArea('frontend')]
#[\Magento\TestFramework\Fixture\AppIsolation(true)]
#[\Magento\TestFramework\Fixture\DbIsolation(true)]
class RemoveGiftsFromResolvedOrderItemsTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected const PRODUCT_SKU = 'simple_product_for_free_gift';
    protected const FREE_GIFT_SKU = 'free-gift-product';
    protected const FREE_GIFT_RULE_NAME = 'Free gift without coupon';
    protected const FIXTURE_QUOTE_RESERVED_ORDER_ID = '10002';

    protected ?\Magento\Framework\Module\Manager $moduleManager;
    protected ?\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory;
    protected ?\Magento\Quote\Api\CartManagementInterface $cartManagement;
    protected ?\Magento\Quote\Api\CartRepositoryInterface $cartRepository;
    protected ?\Magento\Sales\Api\OrderRepositoryInterface $orderRepository;
    protected ?\Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepository;
    protected ?\Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder;
    protected ?\Magento\Customer\Model\Session $customerSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->moduleManager = $this->_objectManager->get(\Magento\Framework\Module\Manager::class);
        $this->quoteCollectionFactory = $this->_objectManager->get(\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory::class);
        $this->cartManagement = $this->_objectManager->get(\Magento\Quote\Api\CartManagementInterface::class);
        $this->cartRepository = $this->_objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->orderRepository = $this->_objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $this->ruleRepository = $this->_objectManager->get(\Magento\SalesRule\Api\RuleRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->_objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->customerSession = $this->_objectManager->get(\Magento\Customer\Model\Session::class);
    }

    #[\Magento\TestFramework\Fixture\Config('payment/checkmo/active', 1, 'store', 'default')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/customer.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/quote.php')]
    public function testItDoesNotAddFreeGiftFromPreviousOrderWhenOrderItemsAreAddedToCartFromOrderHistory(): void
    {
        if (!$this->moduleManager->isEnabled('MageSuite_InstantPurchase')) {
            $this->markTestSkipped('Adding order items to cart from order history is provided by MageSuite_InstantPurchase.');
        }

        $order = $this->placeFixtureOrder();
        $this->assertContains(self::FREE_GIFT_SKU, $this->getSkus($order->getAllVisibleItems()));

        $this->deactivateFreeGiftRule();
        $this->addOrderItemsToCartFromOrderHistory($order);

        $cart = $this->cartRepository->getActiveForCustomer((int)$order->getCustomerId());
        $this->assertSame([self::PRODUCT_SKU], $this->getSkus($cart->getAllVisibleItems()));
    }

    protected function placeFixtureOrder(): \Magento\Sales\Model\Order
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->addFieldToFilter('reserved_order_id', self::FIXTURE_QUOTE_RESERVED_ORDER_ID);
        $orderId = $this->cartManagement->placeOrder((int)$quoteCollection->getFirstItem()->getId());

        return $this->orderRepository->get((int)$orderId);
    }

    protected function deactivateFreeGiftRule(): void
    {
        $this->searchCriteriaBuilder->addFilter('name', self::FREE_GIFT_RULE_NAME);

        foreach ($this->ruleRepository->getList($this->searchCriteriaBuilder->create())->getItems() as $rule) {
            $rule->setIsActive(false);
            $this->ruleRepository->save($rule);
        }
    }

    protected function addOrderItemsToCartFromOrderHistory(\Magento\Sales\Model\Order $order): void
    {
        $orderItemIds = array_map(fn(\Magento\Sales\Model\Order\Item $orderItem): int => (int)$orderItem->getItemId(), $order->getAllVisibleItems());

        $this->customerSession->setCustomerId($order->getCustomerId());
        $this->_objectManager->removeSharedInstance(\Magento\Checkout\Model\Session::class, true);

        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setPostValue([
            'reorder_item' => array_fill_keys($orderItemIds, 'on'),
            'qty' => array_fill_keys($orderItemIds, 1),
        ]);
        $this->dispatch('instant_purchase/cart/add');
    }

    protected function getSkus(array $items): array
    {
        return array_values(array_map(fn(\Magento\Framework\DataObject $item): string => (string)$item->getSku(), $items));
    }
}

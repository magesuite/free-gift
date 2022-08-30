<?php

namespace MageSuite\FreeGift\Test\Integration\Observer;

class AddGiftsBeforeCollectingTotalsTest extends \Magento\TestFramework\TestCase\AbstractController
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->cart = $this->objectManager->create(\Magento\Checkout\Model\Cart::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleNoCoupon
     */
    public function testItAddsFreeGiftToCart()
    {
        $product = $this->productRepository->get('simple_product_for_free_gift');

        $parameters = [
            'product' => $product->getId(),
            'qty' => 1
        ];

        $cart = $this->cart;
        $cart->addProduct($product, $parameters);
        $cart->save();

        $cartItems = $cart->getItems()->getItems();

        $this->assertEquals(2, count($cartItems));
        $this->assertEquals('free-gift-product', $cartItems[1]->getSku());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleOneGiftPerProduct
     */
    public function testItAddsOneFreeGiftPerEveryProductInCart()
    {
        $product = $this->productRepository->get('simple_product_for_free_gift');

        $parameters = [
            'product' => $product->getId(),
            'qty' => 3
        ];

        $cart = $this->cart;
        $cart->addProduct($product, $parameters);
        $cart->save();

        $cartItems = $cart->getItems()->getItems();

        $this->assertEquals(2, count($cartItems));
        $this->assertEquals(3.0, $cartItems[0]->getQty());
        $this->assertEquals(3.0, $cartItems[1]->getQty());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleOneGiftPerProduct
     * @magentoDataFixture loadNotLoggedInUserQuote
     */
    public function testItAllowsForDecreasingAmountOfFreeGift()
    {
        $product = $this->productRepository->get('simple_product_for_free_gift');
        $freeProduct = $this->productRepository->get('free-gift-product');

        $cart = $this->cart;
        $quoteId = $cart->getQuote()->getId();

        $quote = $this->quoteRepository->getActive($quoteId);
        $quoteItem = $this->getQuoteItemByProductId($quote, (int) $freeProduct->getId());

        $this->assertEquals(2, count($quote->getAllItems()));
        $this->assertEquals(5.0, $quoteItem->getQty());

        foreach ($quote->getItems() as $item) {
            if ($item->getItemId() === $quoteItem->getId()) {
                $item->setQty(2);
                break;
            }
        }

        $this->quoteRepository->save($quote);
        $quote->collectTotals();

        $quoteItem = $this->getQuoteItemByProductId($quote, (int) $freeProduct->getId());

        $this->assertEquals(2, count($quote->getAllItems()));
        $this->assertEquals(2.0, $quoteItem->getQty());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleNoCoupon
     */
    public function testItRemovesFreeGiftFromCartWhenOtherProductsAreRemovedFromCart()
    {
        $product = $this->productRepository->get('simple_product_for_free_gift');

        $parameters = [
            'product' => $product->getId(),
            'qty' => 1
        ];

        $cart = $this->cart;
        $cart->addProduct($product, $parameters);
        $cart->save();

        $quote = $cart->getQuote();

        $this->assertEquals(2, $quote->getItemsQty());

        foreach ($quote->getAllItems() as $item) {
            if ($item->getSku() === 'simple_product_for_free_gift') {
                $quote->removeItem($item->getId());
                break;
            }
        }

        $quote->save();

        $this->assertEquals(0, count($quote->getAllItems()));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleNoCoupon
     */
    public function testItAllowsForRemovingFreeGiftFromCart()
    {
        $product = $this->productRepository->get('simple_product_for_free_gift');

        $parameters = [
            'product' => $product->getId(),
            'qty' => 1
        ];

        $cart = $this->cart;
        $cart->addProduct($product, $parameters);
        $cart->save();

        $quote = $cart->getQuote();

        $this->assertEquals(2, $quote->getItemsQty());

        foreach ($quote->getAllItems() as $item) {
            if ($item->getSku() === 'free-gift-product') {
                $quote->removeItem($item->getId());
                break;
            }
        }

        $quote->save();

        $this->assertEquals(1, count($quote->getAllItems()));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleNoCoupon
     */
    public function testItAddsTheSameProductAsAGiftButInRegularPrice()
    {
        $product = $this->productRepository->get('simple_product_for_free_gift');
        $giftProduct = $this->productRepository->get('free-gift-product');

        $cart = $this->cart;
        $cart->addProduct(
            $product,
            [
                'product' => $product->getId(),
                'qty' => 1
            ]
        );
        $cart->addProduct(
            $giftProduct,
            [
                'product' => $giftProduct->getId(),
                'qty' => 1
            ]
        );
        $cart->save();

        $quote = $cart->getQuote();

        $this->assertEquals(3, $quote->getItemsQty());

        $giftItemInRegularPrice = null;
        foreach ($quote->getAllItems() as $item) {
            if (($item->getSku() === 'free-gift-product') && !$item->hasData('is_gift')) {
                $giftItemInRegularPrice = $item;
            }
        }

        $this->assertEquals(100.0, $giftItemInRegularPrice->getPrice());
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param int $productId
     * @return \Magento\Quote\Model\Quote\Item|null
     */
    private function getQuoteItemByProductId(\Magento\Quote\Model\Quote $quote, int $productId): ?\Magento\Quote\Model\Quote\Item
    {
        $quoteItem = null;
        foreach ($quote->getItems() as $item) {
            if ($productId == $item->getProductId()) {
                $quoteItem = $item;
            }
        }

        return $quoteItem;
    }

    public static function loadProduct()
    {
        include __DIR__ . '/../files/product.php';
    }

    public static function loadFreeGiftProduct()
    {
        include __DIR__ . '/../files/free_gift_product.php';
    }

    public static function loadFreeGiftSalesRuleNoCoupon()
    {
        include __DIR__ . '/../files/free_gift_sales_rule_no_coupon.php';
    }

    public static function loadFreeGiftSalesRuleOneGiftPerProduct()
    {
        include __DIR__ . '/../files/free_gift_sales_rule_one_gift_per_product.php';
    }

    public static function loadNotLoggedInUserQuote()
    {
        include __DIR__ . '/../files/not_logged_in_user_quote.php';
    }
}

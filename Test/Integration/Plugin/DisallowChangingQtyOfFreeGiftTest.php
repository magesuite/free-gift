<?php

namespace MageSuite\FreeGift\Test\Integration\Plugin;

class DisallowChangingQtyOfFreeGiftTest extends \Magento\TestFramework\TestCase\AbstractController
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->cart = $this->objectManager->get(\Magento\Checkout\Model\Cart::class);
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadProduct
     * @magentoDataFixture loadFreeGiftProduct
     * @magentoDataFixture loadFreeGiftSalesRuleNoCoupon
     */
    public function testItDoesNotIncreaseAmountOfFreeGift()
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

        $freeGiftItemId = null;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getSku() === 'free-gift-product') {
                $freeGiftItemId = $item->getId();
            }
        }

        $updateParameters = [
            'qty' => 5
        ];
        $updateParams = new \Magento\Framework\DataObject($updateParameters);
        $quote->updateItem($freeGiftItemId, $updateParams);

        $quote->save();

        $this->assertEquals(1, $quote->getItemById($freeGiftItemId)->getQty());
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
}

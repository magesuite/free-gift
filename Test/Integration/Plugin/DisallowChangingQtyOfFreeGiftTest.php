<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Plugin;

class DisallowChangingQtyOfFreeGiftTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected ?\Magento\Framework\App\ObjectManager $objectManager;
    protected ?\Magento\Checkout\Model\Cart $cart;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

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
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/product.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon.php
     */
    public function testItDoesNotIncreaseAmountOfFreeGift(): void
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
}

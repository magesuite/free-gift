<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Plugin\Magento\Checkout\Model\Cart;

#[\Magento\TestFramework\Fixture\AppIsolation(true)]
#[\Magento\TestFramework\Fixture\DbIsolation(true)]
class DisallowChangingQtyOfFreeGiftTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected const PRODUCT_SKU = 'simple_product_for_free_gift';
    protected const FREE_GIFT_SKU = 'free-gift-product';

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

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon.php')]
    public function testItDoesNotIncreaseAmountOfFreeGift(): void
    {
        $freeGiftItemId = $this->addMainProductAndGetGiftItemId(1);
        $quote = $this->cart->getQuote();

        $quote->updateItem($freeGiftItemId, new \Magento\Framework\DataObject(['qty' => 5]));
        $quote->save();

        $this->assertEquals(1, $quote->getItemById($freeGiftItemId)->getQty());
    }

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon_qty_10.php')]
    public function testItAllowsRestoringDecreasedQtyUpToRuleQty(): void
    {
        $giftItemId = $this->addMainProductAndGetGiftItemId(1);
        $this->assertEquals(10, $this->getGiftQty($giftItemId));

        $this->updateGiftQty($giftItemId, 8);
        $this->assertEquals(8, $this->getGiftQty($giftItemId));

        $this->updateGiftQty($giftItemId, 10);
        $this->assertEquals(10, $this->getGiftQty($giftItemId));
    }

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon_qty_10.php')]
    public function testItDoesNotAllowQtyAboveRuleQty(): void
    {
        $giftItemId = $this->addMainProductAndGetGiftItemId(1);

        $this->updateGiftQty($giftItemId, 8);
        $this->updateGiftQty($giftItemId, 11);

        $this->assertEquals(8, $this->getGiftQty($giftItemId));
    }

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon_multiplied_qty_2.php')]
    public function testItLimitsQtyToRuleQtyMultipliedByProductQty(): void
    {
        $giftItemId = $this->addMainProductAndGetGiftItemId(3);
        $this->assertEquals(6, $this->getGiftQty($giftItemId));

        $this->updateGiftQty($giftItemId, 4);
        $this->updateGiftQty($giftItemId, 6);
        $this->assertEquals(6, $this->getGiftQty($giftItemId));

        $this->updateGiftQty($giftItemId, 7);
        $this->assertEquals(6, $this->getGiftQty($giftItemId));
    }

    protected function addMainProductAndGetGiftItemId(int $qty): int
    {
        $product = $this->productRepository->get(self::PRODUCT_SKU);

        $this->cart->addProduct($product, ['product' => $product->getId(), 'qty' => $qty]);
        $this->cart->save();

        foreach ($this->cart->getQuote()->getAllItems() as $item) {
            if ($item->getSku() === self::FREE_GIFT_SKU) {
                return (int)$item->getId();
            }
        }

        $this->fail('Free gift was not added to the cart.');
    }

    protected function updateGiftQty(int $giftItemId, int $qty): void
    {
        $this->cart->updateItems([$giftItemId => ['qty' => $qty]]);
        $this->cart->save();
    }

    protected function getGiftQty(int $giftItemId): float
    {
        return (float)$this->cart->getQuote()->getItemById($giftItemId)->getQty();
    }
}

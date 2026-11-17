<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Service;

#[\Magento\TestFramework\Fixture\AppArea('frontend')]
#[\Magento\TestFramework\Fixture\AppIsolation(true)]
#[\Magento\TestFramework\Fixture\DbIsolation(true)]
class GiftItemQtyLimiterTest extends \PHPUnit\Framework\TestCase
{
    protected const string PRODUCT_SKU = 'simple_product_for_free_gift';
    protected const string GIFT_SKU = 'free-gift-product';
    protected const float RAISED_GIFT_QTY = 50.0;

    protected ?\Magento\TestFramework\ObjectManager $objectManager;
    protected ?\Magento\Quote\Api\CartRepositoryInterface $quoteRepository;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Api\CartRepositoryInterface::class);
    }

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_no_coupon.php')]
    public function testItLimitsGiftQtyRaisedThroughCartItemRepository(): void
    {
        $quote = $this->createQuoteWithProduct(1);
        $giftItem = $this->getGiftItem($quote);

        $cartItem = $this->objectManager->create(\Magento\Quote\Api\Data\CartItemInterface::class);
        $cartItem->setItemId($giftItem->getId());
        $cartItem->setQuoteId($quote->getId());
        $cartItem->setQty(self::RAISED_GIFT_QTY);
        $this->objectManager->get(\Magento\Quote\Api\CartItemRepositoryInterface::class)->save($cartItem);

        $this->assertEquals(1.0, $this->getPersistedGiftQty((int)$quote->getId()));
    }

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_one_gift_per_product.php')]
    public function testItLimitsGiftQtyRaisedThroughGraphQlToQtyMultipliedByProductQty(): void
    {
        $quote = $this->createQuoteWithProduct(3);

        $this->updateGiftQtyThroughGraphQl($quote, self::RAISED_GIFT_QTY);

        $this->assertEquals(3.0, $this->getPersistedGiftQty((int)$quote->getId()));
    }

    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php')]
    #[\Magento\TestFramework\Fixture\DataFixture('MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_one_gift_per_product.php')]
    public function testItKeepsGiftQtyLoweredBelowGrantedQty(): void
    {
        $quote = $this->createQuoteWithProduct(3);

        $this->updateGiftQtyThroughGraphQl($quote, 1.0);

        $this->assertEquals(1.0, $this->getPersistedGiftQty((int)$quote->getId()));
    }

    protected function createQuoteWithProduct(int $qty): \Magento\Quote\Model\Quote
    {
        $product = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class)->get(self::PRODUCT_SKU);
        $cart = $this->objectManager->create(\Magento\Checkout\Model\Cart::class);
        $cart->addProduct($product, ['product' => $product->getId(), 'qty' => $qty]);
        $cart->save();

        $quote = $this->quoteRepository->getActive((int)$cart->getQuote()->getId());
        $this->assertNotNull($this->getGiftItem($quote));

        return $quote;
    }

    protected function updateGiftQtyThroughGraphQl(\Magento\Quote\Model\Quote $quote, float $qty): void
    {
        $updateCartItem = $this->objectManager->get(\Magento\QuoteGraphQl\Model\Cart\UpdateCartItem::class);
        $updateCartItem->execute($quote, (int)$this->getGiftItem($quote)->getId(), $qty, []);
        $this->quoteRepository->save($quote);
    }

    protected function getGiftItem(\Magento\Quote\Model\Quote $quote): ?\Magento\Quote\Model\Quote\Item
    {
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getSku() === self::GIFT_SKU) {
                return $quoteItem;
            }
        }

        return null;
    }

    protected function getPersistedGiftQty(int $quoteId): float
    {
        $resourceConnection = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resourceConnection->getConnection();
        $select = $connection->select()
            ->from($resourceConnection->getTableName('quote_item'), ['qty'])
            ->where('quote_id = ?', $quoteId)
            ->where('sku = ?', self::GIFT_SKU);

        return (float)$connection->fetchOne($select);
    }
}

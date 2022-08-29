<?php

declare(strict_types=1);
namespace MageSuite\FreeGift\Test\Integration\Model;

class QuoteTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\TestFramework\ObjectManager $objectManager;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->create(
            \Magento\Catalog\Api\ProductRepositoryInterface::class
        );
    }

    /**
     * @magentoConfigFixture default_store tax/cart_display/subtotal 2
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_sales_rule_default.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_product.php
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/product.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @dataProvider qtyDataProvider
     *
     * @param int $initialQty
     * @param int $updatedQty
     * @param int $expectedSummaryQty
     * @param float $expectedSubtotalAmount
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testRuleFreeGiftQuotItemsUpdateQty(
        int $initialQty,
        int $updatedQty,
        int $expectedSummaryQty,
        float $expectedSubtotalAmount
    ): void {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->removeAllItems();
        $quote->setData('gift_items_reseted', false);
        $product = $this->productRepository->get('simple_product_for_free_gift', false, null, true);
        $item = $this->addProductToQuote($quote, $product, $initialQty);
        $this->updateQuoteItemQty($quote, $item, $product, $updatedQty);
        $quote->collectTotals();
        $quote->save();

        $this->assertEquals($expectedSummaryQty, $quote->getItemsSummaryQty());

        $totals = $quote->getTotals();
        $subtotalAmount = $totals['subtotal']->getValue();
        $this->assertEquals($expectedSubtotalAmount, $subtotalAmount);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $qty
     * @return \Magento\Quote\Model\Quote\Item
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addProductToQuote(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Catalog\Api\Data\ProductInterface $product,
        int $qty
    ):\Magento\Quote\Model\Quote\Item {
        $buyRequest = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Framework\DataObject::class,
            [
                'data' => [
                    'product' => $product->getId(),
                    'qty' => $qty
                ],
            ]
        );

        return $quote->addProduct($product, $buyRequest);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Magento\Quote\Model\Quote\Item $item
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param int $qty
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateQuoteItemQty(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\Quote\Item $item,
        \Magento\Catalog\Api\Data\ProductInterface $product,
        int $qty
    ):void {
        $buyRequest = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Framework\DataObject::class,
            [
                'data' => [
                    'product' => $product->getId(),
                    'qty' => $qty
                ],
            ]
        );
        $quote->updateItem((int)$item->getId(), $buyRequest);
    }

    /**
     * @return \int[][]
     */
    public static function qtyDataProvider():array
    {
        return [
            'Increase Qty' => [1, 2, 4, 300.0],
            'Decrease Qty' => [2, 1, 2, 150.0]
        ];
    }

    /**
     * @param \Magento\Framework\Api\ExtensibleDataInterface $entity
     * @return array
     */
    protected function convertToArray(\Magento\Framework\Api\ExtensibleDataInterface $entity): array
    {
        return $this->objectManager
            ->create(\Magento\Framework\Api\ExtensibleDataObjectConverter::class)
            ->toFlatArray($entity);
    }
}

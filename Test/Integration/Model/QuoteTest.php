<?php
declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Model;

class QuoteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture loadCartRuleFreeGift
     * @magentoDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoDataFixture Magento/Catalog/_files/product_simple_duplicated.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @dataProvider qtyDataProvider
     * @return void
     */
    public function testRuleFreeGiftQuotItemsUpdateQty($initialQty, $updatedQty, $expectedSummaryQty): void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $quote->removeAllItems();
        $quote->setData('gift_items_reseted', false);
        $productRepository = $this->objectManager->create(
            \Magento\Catalog\Api\ProductRepositoryInterface::class
        );
        $product = $productRepository->get('simple', false, null, true);

        $item = $this->addProductToQuote($quote, $product, $initialQty);
        $this->updateQuoteItemQty($quote, $item, $product, $updatedQty);
        $quote->collectTotals();
        $quote->save();

        $this->assertEquals($expectedSummaryQty, $quote->getItemsSummaryQty());
    }

    protected function addProductToQuote($quote, $product, $qty)
    {
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

    protected function updateQuoteItemQty($quote, $item, $product, $qty)
    {
        $buyRequest = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
            \Magento\Framework\DataObject::class,
            [
                'data' => [
                    'product' => $product->getId(),
                    'qty' => $qty
                ],
            ]
        );
        $quote->updateItem($item->getId(), $buyRequest);
    }

    public static function qtyDataProvider()
    {
        return [
            'Increase Qty' => [1, 2, 4],
            'Decrease Qty' => [2, 1, 2]
        ];
    }

    public static function loadCartRuleFreeGift()
    {
        include __DIR__.'/../_files/cart_rule_free_gift.php';
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

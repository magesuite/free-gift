<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\SalesRule\Model\Rule\Condition\Product;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Catalog/_files/product_simple.php
 */
class ValidationTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;
    protected ?\Magento\Catalog\Api\ProductRepositoryInterface $productRepository = null;
    protected ?\Magento\SalesRule\Model\Rule\Condition\Product $condition = null;
    protected ?\Magento\Quote\Model\Quote\Item $item = null;

    protected const PRICE_INCL_TAX_ATTRIBUTE_CODE = 'price_incl_tax';

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->condition = $this->objectManager->get(\Magento\SalesRule\Model\Rule\Condition\Product::class);
        $this->item = $this->objectManager->get(\Magento\Quote\Model\Quote\Item::class);
    }

    public function testItValidatesPriceWithTaxConditionCorrectly(): void
    {
        $product = $this->productRepository->get('simple');
        $this->item->setProduct($product);
        $this->item->setData(\MageSuite\FreeGift\Plugin\Rule\Condition\AddPriceIncTax::ATTRIBUTE_CODE, 10);
        $this->condition->setAttribute(\MageSuite\FreeGift\Plugin\Rule\Condition\AddPriceIncTax::ATTRIBUTE_CODE);
        $this->condition->setOperator('==');
        $this->condition->setValue(10);

        $this->assertTrue($this->condition->validate($this->item));
    }
}

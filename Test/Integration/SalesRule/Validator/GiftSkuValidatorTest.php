<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\SalesRule\Validator;

/**
 * @magentoDbIsolation enabled
 * @magentoAppArea frontend
 * @magentoDataFixture Magento/Catalog/_files/product_simple.php
 */
class GiftSkuValidatorTest extends \PHPUnit\Framework\TestCase
{
    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;
    protected ?\MageSuite\FreeGift\SalesRule\Validator\GiftSku $giftSkuValidator = null;
    protected ?\Magento\SalesRule\Model\RuleFactory $ruleFactory = null;

    public function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\ObjectManager::getInstance();
        $this->giftSkuValidator = $this->objectManager->get(\MageSuite\FreeGift\SalesRule\Validator\GiftSku::class);
        $this->ruleFactory = $this->objectManager->get(\Magento\SalesRule\Model\RuleFactory::class);
    }

    /**
     * @dataProvider getTestData
     */
    public function testItValidatesGiftSkuCorrectly(string $skus, bool $expected): void
    {
        $rule = $this->ruleFactory->create();
        $rule->setGiftSkus($skus);
        $this->assertEquals($expected, $this->giftSkuValidator->isValid($rule));
    }

    public static function getTestData(): array
    {
        return [
            ['sku1,sku2,sku3', false],
            ['simple', true],
            ['simple,complex', false],
        ];
    }
}

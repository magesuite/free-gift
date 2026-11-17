<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Unit\Plugin\Magento\Checkout\Model\Cart;

class DisallowChangingQtyOfFreeGiftTest extends \PHPUnit\Framework\TestCase
{
    protected const RULE_ID = 5;
    protected const GIFT_SKU = 'gift';
    protected const MAIN_SKU = 'main';

    protected \Magento\Framework\Message\ManagerInterface|\PHPUnit\Framework\MockObject\MockObject $messageManager;
    protected \Magento\Checkout\Model\Cart|\PHPUnit\Framework\MockObject\MockObject $cart;
    protected \Magento\Quote\Model\Quote|\PHPUnit\Framework\MockObject\MockObject $quote;
    protected array $rules = [];
    protected \MageSuite\FreeGift\Plugin\Magento\Checkout\Model\Cart\DisallowChangingQtyOfFreeGift $plugin;

    protected function setUp(): void
    {
        $this->messageManager = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->quote = $this->createStub(\Magento\Quote\Model\Quote::class);
        $this->cart = $this->createStub(\Magento\Checkout\Model\Cart::class);
        $this->cart->method('getQuote')->willReturn($this->quote);

        $ruleFactory = $this->createStub(\Magento\SalesRule\Model\RuleFactory::class);
        $ruleFactory->method('create')->willReturnCallback(fn () => $this->getMockBuilder(\Magento\SalesRule\Model\Rule::class)->disableOriginalConstructor()->onlyMethods([])->getMock());

        $ruleResource = $this->createStub(\Magento\SalesRule\Model\ResourceModel\Rule::class);
        $ruleResource->method('load')->willReturnCallback(function ($rule, $ruleId) use ($ruleResource) {
            if (isset($this->rules[$ruleId])) {
                $rule->setData($this->rules[$ruleId]);
            }

            return $ruleResource;
        });

        $freeGiftFactory = $this->createStub(\MageSuite\FreeGift\Model\FreeGiftFactory::class);
        $freeGiftFactory->method('create')->willReturnCallback(fn () => new \MageSuite\FreeGift\Model\FreeGift());

        $this->plugin = new \MageSuite\FreeGift\Plugin\Magento\Checkout\Model\Cart\DisallowChangingQtyOfFreeGift(
            $this->messageManager,
            $ruleFactory,
            $ruleResource,
            new \MageSuite\FreeGift\Service\RuleToFreeGiftsConverter($freeGiftFactory),
            new \MageSuite\FreeGift\Service\GiftItem()
        );
    }

    public function testItAllowsRestoringQtyUpToRuleQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, self::GIFT_SKU, 10);
        $this->givenQuoteItems([$this->gift(20, 8), $this->main(1, 1)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(10, $this->update([20 => ['qty' => 10]])[20]['qty']);
    }

    public function testItRejectsQtyAboveRuleQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, self::GIFT_SKU, 10);
        $this->givenQuoteItems([$this->gift(20, 8), $this->main(1, 1)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $data = $this->update([20 => ['qty' => 11]]);

        $this->assertSame(8.0, $data[20]['qty']);
        $this->assertSame(8.0, $data[20]['before_suggest_qty']);
    }

    public function testItRejectsIncreaseOfGiftAlreadyAtRuleQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, self::GIFT_SKU, 1);
        $this->givenQuoteItems([$this->gift(20, 1), $this->main(1, 1)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame(1.0, $this->update([20 => ['qty' => 5]])[20]['qty']);
    }

    public function testItDoesNotMultiplyRuleQtyForGiftOnceAction(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, self::GIFT_SKU, 2);
        $this->givenQuoteItems([$this->gift(20, 1), $this->main(1, 3)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame(1.0, $this->update([20 => ['qty' => 4]])[20]['qty']);
    }

    public function testItMultipliesRuleQtyByProductQtyForGiftAction(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION, self::GIFT_SKU, 2);
        $this->givenQuoteItems([$this->gift(20, 3), $this->main(1, 3)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(6, $this->update([20 => ['qty' => 6]])[20]['qty']);
    }

    public function testItRejectsQtyAboveMultipliedRuleQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION, self::GIFT_SKU, 2);
        $this->givenQuoteItems([$this->gift(20, 3), $this->main(1, 3)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame(3.0, $this->update([20 => ['qty' => 7]])[20]['qty']);
    }

    public function testItUsesQtyOfTheProductTheGiftBelongsTo(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION, self::GIFT_SKU, 2);
        $this->givenQuoteItems([$this->gift(20, 2), $this->main(1, 1), $this->item(['id' => 2, 'sku' => 'other', 'qty' => 4])]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame(2.0, $this->update([20 => ['qty' => 3]])[20]['qty']);
    }

    public function testItNeverRaisesQtyWhenRuleCannotBeResolved(): void
    {
        $this->givenQuoteItems([$this->gift(20, 1, ['rule_id' => 99]), $this->main(1, 1)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame(1.0, $this->update([20 => ['qty' => 2]])[20]['qty']);
    }

    public function testItAllowsDecreasingQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, self::GIFT_SKU, 10);
        $this->givenQuoteItems([$this->gift(20, 8), $this->main(1, 1)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(3, $this->update([20 => ['qty' => 3]])[20]['qty']);
    }

    public function testItDoesNotTouchRegularItems(): void
    {
        $this->givenQuoteItems([$this->main(1, 1)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(9, $this->update([1 => ['qty' => 9]])[1]['qty']);
    }

    public function testItCapsTotalOfGiftItemsWhenRuleListsSameSkuTwice(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, 'gift,gift', '1,2');
        $this->givenQuoteItems([$this->gift(20, 1), $this->gift(21, 2), $this->main(1, 1)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $data = $this->update([20 => ['qty' => 3], 21 => ['qty' => 2]]);

        $this->assertSame(1.0, $data[20]['qty']);
        $this->assertSame(2, $data[21]['qty']);
    }

    public function testItAllowsSplittingRuleQtyBetweenItemsOfSameSku(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, 'gift,gift', '1,2');
        $this->givenQuoteItems([$this->gift(20, 1), $this->gift(21, 1), $this->main(1, 1)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(2, $this->update([21 => ['qty' => 2]])[21]['qty']);
    }

    public function testItComparesSkusCaseInsensitivelyAfterTrim(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, 'GIFT ', 4);
        $this->givenQuoteItems([$this->gift(20, 1, ['sku' => ' gift']), $this->main(1, 1)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(4, $this->update([20 => ['qty' => 4]])[20]['qty']);
    }

    public function testItKeepsOldBehaviourForUnsupportedRuleAction(): void
    {
        $this->givenRule('by_percent', self::GIFT_SKU, 10);
        $this->givenQuoteItems([$this->gift(20, 1), $this->main(1, 1)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $this->assertSame(1.0, $this->update([20 => ['qty' => 5]])[20]['qty']);
    }

    public function testItIgnoresChildLinesWhenResolvingOriginalProductQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftAction::ACTION, self::GIFT_SKU, 2);
        $parent = $this->main(1, 3);
        $this->givenQuoteItems([$this->gift(20, 3), $this->item(['id' => 2, 'sku' => self::MAIN_SKU, 'qty' => 1], $parent), $parent]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(6, $this->update([20 => ['qty' => 6]])[20]['qty']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('ignoredRequestedQtyProvider')]
    public function testItIgnoresRequestedQtyOfOtherGiftLines(mixed $otherLineQty): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, 'gift,gift', '1,2');
        $this->givenQuoteItems([$this->gift(20, 1), $this->gift(21, 2), $this->main(1, 1)]);

        $data = $this->update([20 => ['qty' => 3], 21 => ['qty' => $otherLineQty]]);

        $this->assertSame(1.0, $data[20]['qty']);
        $this->assertSame($otherLineQty, $data[21]['qty']);
    }

    public static function ignoredRequestedQtyProvider(): array
    {
        return [
            'negative' => [-5],
            'non numeric' => ['abc'],
            'remove' => [0],
        ];
    }

    public function testItDoesNotLetTwoLinesRaisedAtOnceExceedTheGrant(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, 'gift,gift', '2,2');
        $this->givenQuoteItems([$this->gift(20, 1), $this->gift(21, 1), $this->main(1, 1)]);

        $this->messageManager->expects($this->once())->method('addErrorMessage');

        $data = $this->update([20 => ['qty' => 3], 21 => ['qty' => 2]]);

        $this->assertSame(3, $data[20]['qty']);
        $this->assertSame(1.0, $data[21]['qty']);
    }

    public function testItNeverChangesLinesAtOrBelowTheirOldQty(): void
    {
        $this->givenRule(\MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION, self::GIFT_SKU, 1);
        $this->givenQuoteItems([$this->gift(20, 4), $this->main(1, 1)]);

        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertSame(['qty' => 4], $this->update([20 => ['qty' => 4]])[20]);
        $this->assertSame(['qty' => 0], $this->update([20 => ['qty' => 0]])[20]);
    }

    protected function update(array $data): array
    {
        return $this->plugin->beforeUpdateItems($this->cart, $data)[0];
    }

    protected function givenRule(string $action, string $giftSku, float|string $giftQty): void
    {
        $this->rules[self::RULE_ID] = [
            'simple_action' => $action,
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU => $giftSku,
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::RULE_DATA_KEY_SKU_QTY => (string)$giftQty,
        ];
    }

    protected function givenQuoteItems(array $items): void
    {
        $this->quote->method('getAllItems')->willReturn($items);
        $this->quote->method('getItemById')->willReturnCallback(function ($id) use ($items) {
            foreach ($items as $item) {
                if ($item->getId() == $id) {
                    return $item;
                }
            }

            return null;
        });
    }

    protected function main(int $id, float $qty): \Magento\Quote\Model\Quote\Item
    {
        return $this->item(['id' => $id, 'sku' => self::MAIN_SKU, 'qty' => $qty]);
    }

    protected function gift(int $id, float $qty, array $overrides = []): \Magento\Quote\Model\Quote\Item
    {
        $giftData = array_merge(['sku' => self::GIFT_SKU, 'rule_id' => self::RULE_ID], $overrides);
        $options = [
            \MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU => self::MAIN_SKU,
            'rule_id' => $giftData['rule_id'],
        ];

        if (($this->rules[$giftData['rule_id']]['simple_action'] ?? null) === \MageSuite\FreeGift\SalesRule\Action\GiftOnceAction::ACTION) {
            $options[\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::GIFT_ADDED_ONCE] = true;
        }

        return $this->item(['id' => $id, 'sku' => $giftData['sku'], 'qty' => $qty, 'options' => $options]);
    }

    protected function item(array $itemData, ?\Magento\Quote\Model\Quote\Item $parent = null): \Magento\Quote\Model\Quote\Item
    {
        $product = $this->createStub(\Magento\Catalog\Model\Product::class);
        $product->method('getSku')->willReturn($itemData['sku']);

        $item = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getQty', 'getProduct', 'getOptionByCode', 'getParentItem'])
            ->getMock();
        $item->method('getId')->willReturn($itemData['id']);
        $item->method('getQty')->willReturn($itemData['qty']);
        $item->method('getParentItem')->willReturn($parent);
        $item->method('getProduct')->willReturn($product);
        $item->method('getOptionByCode')->willReturnCallback(fn ($code) => $this->createOption($itemData['options'][$code] ?? null));

        return $item;
    }

    protected function createOption(mixed $value): ?\Magento\Quote\Model\Quote\Item\Option
    {
        if ($value === null) {
            return null;
        }

        $option = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item\Option::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValue'])
            ->getMock();
        $option->method('getValue')->willReturn($value);

        return $option;
    }
}

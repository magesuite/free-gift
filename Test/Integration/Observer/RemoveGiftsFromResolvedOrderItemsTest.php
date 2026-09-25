<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Observer;

class RemoveGiftsFromResolvedOrderItemsTest extends \PHPUnit\Framework\TestCase
{
    protected const ORDER_ITEMS_RESOLVED_EVENT = 'magesuite_instant_purchase_order_items_resolved';
    protected const REGULAR_ITEM_ID = 1;
    protected const GIFT_ITEM_ID = 2;

    protected ?\Magento\Framework\ObjectManagerInterface $objectManager = null;
    protected ?\Magento\Framework\Event\ManagerInterface $eventManager = null;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->eventManager = $this->objectManager->get(\Magento\Framework\Event\ManagerInterface::class);
    }

    #[\Magento\TestFramework\Fixture\AppArea('frontend')]
    public function testItRemovesFreeGiftFromOrderItemsResolvedForReorder(): void
    {
        $regularItem = $this->createOrderItem(self::REGULAR_ITEM_ID, ['product' => 10, 'qty' => 1]);
        $giftItem = $this->createOrderItem(self::GIFT_ITEM_ID, ['product' => 20, 'qty' => 1, 'custom_price' => 0]);

        $transport = $this->objectManager->create(\Magento\Framework\DataObject::class, [
            'data' => ['order_items' => [self::REGULAR_ITEM_ID => $regularItem, self::GIFT_ITEM_ID => $giftItem]]
        ]);

        $this->eventManager->dispatch(self::ORDER_ITEMS_RESOLVED_EVENT, ['transport' => $transport]);

        $resolvedItemIds = array_map(fn(\Magento\Sales\Model\Order\Item $orderItem): int => (int)$orderItem->getItemId(), array_values($transport->getData('order_items')));

        $this->assertSame([self::REGULAR_ITEM_ID], $resolvedItemIds);
    }

    protected function createOrderItem(int $itemId, array $buyRequest): \Magento\Sales\Model\Order\Item
    {
        $orderItem = $this->objectManager->create(\Magento\Sales\Model\Order\Item::class);
        $orderItem->setItemId($itemId);
        $orderItem->setProductOptions(['info_buyRequest' => $buyRequest]);

        return $orderItem;
    }
}

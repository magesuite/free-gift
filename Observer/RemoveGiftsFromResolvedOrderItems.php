<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Observer;

class RemoveGiftsFromResolvedOrderItems implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        protected \MageSuite\FreeGift\Service\GiftOrderItem $giftOrderItem
    ) {
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $transport = $observer->getEvent()->getData('transport');
        $orderItems = (array)$transport->getData('order_items');

        $transport->setData('order_items', array_filter($orderItems, fn(\Magento\Sales\Model\Order\Item $orderItem): bool => !$this->giftOrderItem->isGift($orderItem)));
    }
}

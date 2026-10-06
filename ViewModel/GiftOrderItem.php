<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\ViewModel;

class GiftOrderItem implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    public function __construct(
        protected \MageSuite\FreeGift\Service\GiftOrderItem $giftOrderItem
    ) {
    }

    public function isGift(\Magento\Sales\Model\Order\Item $orderItem): bool
    {
        return $this->giftOrderItem->isGift($orderItem);
    }
}

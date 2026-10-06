<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Service;

class GiftOrderItem
{
    public function isGift(\Magento\Sales\Model\Order\Item $orderItem): bool
    {
        $buyRequest = $orderItem->getProductOptionByCode('info_buyRequest');

        return is_array($buyRequest) && isset($buyRequest['custom_price']) && is_numeric($buyRequest['custom_price']);
    }
}

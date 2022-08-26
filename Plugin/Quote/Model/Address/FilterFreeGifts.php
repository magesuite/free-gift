<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Plugin\Quote\Model\Address;

class FilterFreeGifts
{
    public function afterGetAllItems(
        \Magento\Quote\Model\Quote\Address $subject,
        array $result
    ): array {
        $result = array_filter($result, function($item) {
            return (int)$item->getData('is_gift') !== 1;
        }, ARRAY_FILTER_USE_BOTH);

        return $result;
    }
}

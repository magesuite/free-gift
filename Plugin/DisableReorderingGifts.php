<?php

namespace MageSuite\FreeGift\Plugin;

class DisableReorderingGifts
{
    /**
     * We detect gift by custom_price element that is added within cart buyRequest
     * Gift items must not be added to cart when reordering
     * @param \Magento\Checkout\Model\Cart $subject
     * @param callable $proceed
     * @param $orderItem
     * @param null $qtyFlag
     * @return \Magento\Checkout\Model\Cart
     */
    public function aroundAddOrderItem(\Magento\Checkout\Model\Cart $subject, callable $proceed, $orderItem, $qtyFlag = null) {
        $buyRequest = $orderItem->getProductOptionByCode('info_buyRequest');

        if(isset($buyRequest['custom_price']) and is_numeric($buyRequest['custom_price'])) {
            return $subject;
        }

        return $proceed($orderItem, $qtyFlag);
    }
}
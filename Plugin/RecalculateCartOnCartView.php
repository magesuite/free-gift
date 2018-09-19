<?php

namespace MageSuite\FreeGift\Plugin;

class RecalculateCartOnCartView
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    public function __construct(\Magento\Checkout\Model\Cart $cart)
    {
        $this->cart = $cart;
    }

    /**
     * We need to always have recalculated cart items data before viewing cart
     * @param \Magento\Checkout\Controller\Cart\Index $subject
     * @return null
     */
    public function beforeExecute(\Magento\Checkout\Controller\Cart\Index $subject) {
        $this->cart->save();

        return null;
    }
}

<?php

namespace MageSuite\FreeGift\SalesRule\Action;

class GiftOnceAction extends AbstractGiftAction
{
    const ACTION = 'add_gift_once';

    protected function isAppliedForEveryItemInCart()
    {
        return false;
    }

    protected function isMultipliedByProductQty()
    {
        return false;
    }
}

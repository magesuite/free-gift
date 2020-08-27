<?php

namespace MageSuite\FreeGift\SalesRule\Action;

class GiftAction  extends AbstractGiftAction
{
    const ACTION = 'add_gift';

    protected function isAppliedForEveryItemInCart()
    {
        return true;
    }

    protected function isMultipliedByProductQty()
    {
        return true;
    }
}
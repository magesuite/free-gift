<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Observer;

class LimitGiftItemQuantities implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        protected \MageSuite\FreeGift\Service\GiftItemQtyLimiter $giftItemQtyLimiter
    ) {
    }

    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $this->giftItemQtyLimiter->limit($observer->getEvent()->getData('quote'));
    }
}

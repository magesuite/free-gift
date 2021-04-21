<?php

namespace MageSuite\FreeGift\Observer;

class RemoveCouponRelatedGift implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \MageSuite\FreeGift\Service\CouponRelatedGiftRemover
     */
    protected $couponRelatedGiftRemover;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \MageSuite\FreeGift\Service\CouponRelatedGiftRemover $couponRelatedGiftRemover
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->couponRelatedGiftRemover = $couponRelatedGiftRemover;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $this->couponRelatedGiftRemover->execute($quote);
    }
}

<?php

namespace MageSuite\FreeGift\Plugin\Quote\Model\CouponManagement;

class RemoveCouponRelatedGift
{
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \MageSuite\FreeGift\Service\CouponRelatedGiftRemover
     */
    protected $couponRelatedGiftRemover;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \MageSuite\FreeGift\Service\CouponRelatedGiftRemover $couponRelatedGiftRemover
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->couponRelatedGiftRemover = $couponRelatedGiftRemover;
    }

    public function beforeRemove(\Magento\Quote\Model\CouponManagement $subject, $cartId)
    {
        $quote = $this->quoteRepository->get($cartId);
        $this->couponRelatedGiftRemover->execute($quote);
    }
}

<?php

namespace MageSuite\FreeGift\Observer;

class DisableMergingGifts implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    public function __construct(\Magento\Framework\Message\Manager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * When logging in cart of guest and cart assigned to account are merged
     * When both contain gift items it is possible to multiply gift items by continiously adding them as guest
     * and logging it afterward.
     *
     * To disallow such behavior when carts are merged we detect if both of them contain gift items and if so we only
     * use contents of guest cart.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $guestQuote */
        $customerQuote = $observer->getData('quote');

        /** @var \Magento\Quote\Model\Quote $source */
        $guestQuote = $observer->getData('source');

        if(empty($guestQuote->getAllVisibleItems())) {
            return;
        }

        if(!$this->quoteHasGiftItems($customerQuote) or !$this->quoteHasGiftItems($guestQuote)) {
            return;
        }

        /** @var \Magento\Quote\Model\Quote\Item $item */
        $customerQuoteItems = $customerQuote->getAllVisibleItems();

        foreach($customerQuoteItems as $item) {
            $item->isDeleted(true);
        }

        $this->messageManager->addNoticeMessage(__('We were not able to add to cart items that you had saved previously, because both current and previous cart contain gifts.'));
    }

    public function quoteHasGiftItems($quote) {
        $items = $quote->getAllVisibleItems();

        foreach($items as $item) {
            if($item->getIsGift()) {
                return true;
            }
        }

        return false;
    }
}

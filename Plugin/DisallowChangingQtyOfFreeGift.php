<?php

namespace MageSuite\FreeGift\Plugin;

class DisallowChangingQtyOfFreeGift
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    public function __construct(\Magento\Framework\Message\ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * When qty of free gift is changed we revert qty to old value
     * @param \Magento\Checkout\Model\Cart $subject
     * @param $data
     * @return array
     */
    public function beforeupdateItems(\Magento\Checkout\Model\Cart $subject, $data)
    {
        $quote = $subject->getQuote();

        foreach ($data as $itemId => $value) {
            $item = $quote->getItemById($itemId);

            if(!$item){
                continue;
            }

            if (!$item->getOptionByCode(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU)
                instanceof \Magento\Quote\Model\Quote\Item\Option
            ) {
                continue;
            }

            $oldQty = $item->getQty();

            if ($data[$itemId]['qty'] <= $oldQty) {
                continue;
            }

            $data[$itemId]['qty'] = $oldQty;
            $data[$itemId]['before_suggest_qty'] = $oldQty;

            $this->messageManager->addErrorMessage(__('Quantity of promotional item cannot be increased.'));
        }

        return [$data];
    }
}
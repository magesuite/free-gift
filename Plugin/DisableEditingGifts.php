<?php

namespace MageSuite\FreeGift\Plugin;

class DisableEditingGifts
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Controller\ResultFactory $resultFactory
    )
    {
        $this->request = $request;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
        $this->resultFactory = $resultFactory;
    }

    public function aroundExecute(\Magento\Checkout\Controller\Cart\Configure $subject, $proceed)
    {
        $itemId = $this->request->getParam('id');

        $quote = $this->cart->getQuote();

        if (!$quote) {
            return $proceed();
        }

        $quoteItem = $quote->getItemById($itemId);

        if (!$quoteItem) {
            return $proceed();
        }

        $isGift = $quoteItem->getProduct()->getCustomOption(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU);

        if (!$isGift) {
            return $proceed();
        }

        
        $this->messageManager->addError(__("Promotional item cannot be edited"));

        return $this->resultFactory
            ->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT)
            ->setPath('checkout/cart');
    }
}
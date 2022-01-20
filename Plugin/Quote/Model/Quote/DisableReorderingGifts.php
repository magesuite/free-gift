<?php


namespace MageSuite\FreeGift\Plugin\Quote\Model\Quote;

class DisableReorderingGifts
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\OrderFactory $orderFactory
    )
    {
        $this->request = $request;
        $this->orderFactory = $orderFactory;
    }

    /**
     * We detect gift by custom_price element that is added within cart buyRequest
     * Gift items must not be added to cart when reordering
     * @param \Magento\Quote\Model\Quote $subject
     * @param callable $proceed
     * @param $product
     * @param null $request
     * @param string $processMode
     * @return \Magento\Quote\Model\Quote
     */
    public function aroundAddProduct(
        \Magento\Quote\Model\Quote $subject,
        callable $proceed,
        $product,
        $request = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    )
    {
        if ($this->productIsAGiftFromPreviousOrder($request)) {
            return $subject;
        }

        return $proceed($product, $request, $processMode);
    }

    /**
     * @param $request
     * @return bool
     */
    protected function productIsAGiftFromPreviousOrder($request): bool
    {
        if ($this->request->getFullActionName() !== 'sales_order_reorder') {
            return false;
        }

        if (!isset($request['product'])) {
            return false;
        }

        $orderId = $this->request->getParam('order_id');
        $order = $this->orderFactory->create()->load($orderId);
        $orderItems = $order->getItemsCollection();

        $product = $orderItems->getItemsByColumnValue('product_id', $request['product']);

        return
            isset($product[0]['product_options']['info_buyRequest']['custom_price']) &&
            is_numeric($product[0]['product_options']['info_buyRequest']['custom_price']);
    }
}

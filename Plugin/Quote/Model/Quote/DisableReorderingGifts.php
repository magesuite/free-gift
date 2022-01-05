<?php

namespace MageSuite\FreeGift\Plugin\Quote\Model\Quote;

class DisableReorderingGifts
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    public function __construct(\Magento\Framework\App\Request\Http $request) {
        $this->request = $request;
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
    ) {
        if($this->productIsAGiftFromPreviousOrder($request)) {
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
        return
            $this->request->getFullActionName() == 'sales_order_reorder' &&
            isset($request['custom_price']) &&
            is_numeric($request['custom_price']);
    }
}

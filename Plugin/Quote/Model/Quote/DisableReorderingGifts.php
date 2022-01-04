<?php

namespace MageSuite\FreeGift\Plugin\Quote\Model\Quote;

class DisableReorderingGifts
{
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
        if(isset($request['custom_price']) and is_numeric($request['custom_price'])) {
            return $subject;
        }

        return $proceed($product, $request, $processMode);
    }
}

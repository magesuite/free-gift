<?php

namespace MageSuite\FreeGift\Plugin;

class ForceGiftAsSeparateCartItem
{
    /**
     * Free gifts cannot be represented by another full priced product cart item, they has to be added as separate cart item
     * This fixes bug when adding product for full price and at the same time exactly the same product as a gift
     * which caused both to have discounted/zeroed price
     * @param \Magento\Quote\Model\Quote\Item $subject
     * @param callable $proceed
     * @param $product
     * @return bool
     */
    public function aroundRepresentProduct(\Magento\Quote\Model\Quote\Item $subject, callable $proceed, $product) {
        $isGift = $product->getCustomOption(\MageSuite\FreeGift\SalesRule\Action\AbstractGiftAction::ORIGINAL_PRODUCT_SKU);

        if($isGift instanceof \Magento\Catalog\Model\Product\Configuration\Item\Option) {
            return false;
        }

        return $proceed($product);
    }
}

<?php

namespace MageSuite\FreeGift\Plugin\Rule\Condition;

class AddQuoteItemSku
{
    const QUOTE_ITEM_SKU = 'quote_item_sku';
    const SKU = 'sku';

    /**
     * Add SKU in cart as possible option in conditions list
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $subject
     * @param $result
     * @return \Magento\SalesRule\Model\Rule\Condition\Product
     */

    public function afterLoadAttributeOptions(\Magento\SalesRule\Model\Rule\Condition\Product $subject, $result) {
        $attributeOptions = $subject->getAttributeOption();

        $attributeOptions[self::QUOTE_ITEM_SKU] = __('SKU in cart');

        $subject->setAttributeOption($attributeOptions);

        return $subject;
    }

    /**
     * Add quoteItemSku as property of product for later comparisons
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $subject
     * @param \Magento\Framework\Model\AbstractModel $model
     * @return array
     */
    public function beforeValidate(\Magento\SalesRule\Model\Rule\Condition\Product $subject, \Magento\Framework\Model\AbstractModel $model) {
        $product = $model->getProduct();

        if(!$product) {
            return [$model];
        }

        $product->setQuoteItemSku($model->getSku());

        return [$model];
    }

    public function aroundGetValueElementChooserUrl(\Magento\SalesRule\Model\Rule\Condition\Product $subject, callable $proceed) {
        return $this->simulateSkuAttribute($subject, $proceed);
    }

    public function aroundGetValueAfterElementHtml(\Magento\SalesRule\Model\Rule\Condition\Product $subject, callable $proceed) {
        return $this->simulateSkuAttribute($subject, $proceed);
    }

    public function aroundgetExplicitApply(\Magento\SalesRule\Model\Rule\Condition\Product $subject, callable $proceed) {
        return $this->simulateSkuAttribute($subject, $proceed);
    }

    /**
     * In order to have sku chooser we need to simulate that we use sku attribute
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $subject
     * @param callable $proceed
     * @return mixed
     */
    protected function simulateSkuAttribute(\Magento\SalesRule\Model\Rule\Condition\Product $subject, callable $proceed)
    {
        if ($subject->getAttribute() == self::QUOTE_ITEM_SKU) {
            $subject->setAttribute(self::SKU);
            $result = $proceed();
            $subject->setAttribute(self::QUOTE_ITEM_SKU);
            return $result;
        }

        return $proceed();
    }
}

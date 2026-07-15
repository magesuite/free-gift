<?php

namespace MageSuite\FreeGift\Service;

class Cart
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $configurableProduct;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * @var \Magento\Framework\EntityManager\EventManager
     */
    protected $eventManager;

    /**
     * @var \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList
     */
    protected $quoteItemQtyList;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $response;

    /**
     * @var \Magento\Framework\DataObject\Factory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\CatalogInventory\Api\StockStateInterface
     */
    protected $stockState;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \MageSuite\FreeGift\Model\Factory\GetStockItemDataFactory
     */
    protected $getStockItemDataFactory;

    /**
     * @var \MageSuite\FreeGift\Model\Factory\StockResolverFactory
     */
    protected $stockResolverFactory;

    /**
     * @var \MageSuite\FreeGift\Model\Factory\SalesChannelFactory
     */
    protected $salesChannelFactory;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \MageSuite\FreeGift\Model\ConfigProvider
     */
    protected $configProvider;

    /**
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProduct
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @param \Magento\Framework\EntityManager\EventManager $eventManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param \Magento\Framework\DataObject\Factory $dataObjectFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogInventory\Api\StockStateInterface $stockState
     * @param \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $quoteItemQtyList
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \MageSuite\FreeGift\Model\Factory\StockResolverFactory $stockResolverFactory
     * @param \MageSuite\FreeGift\Model\Factory\GetStockItemDataFactory $getStockItemDataFactory
     * @param \MageSuite\FreeGift\Model\Factory\SalesChannelFactory $salesChannelFactory
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \MageSuite\FreeGift\Model\ConfigProvider $configProvider
     */
    public function __construct(
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $configurableProduct,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\EntityManager\EventManager $eventManager,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\ResponseInterface $response,
        \Magento\Framework\DataObject\Factory $dataObjectFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\QuoteItemQtyList $quoteItemQtyList,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MageSuite\FreeGift\Model\Factory\StockResolverFactory $stockResolverFactory,
        \MageSuite\FreeGift\Model\Factory\GetStockItemDataFactory $getStockItemDataFactory,
        \MageSuite\FreeGift\Model\Factory\SalesChannelFactory $salesChannelFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \MageSuite\FreeGift\Model\ConfigProvider $configProvider
    )
    {
        $this->formKey = $formKey;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->configurableProduct = $configurableProduct;
        $this->serializer = $serializer;
        $this->eventManager = $eventManager;
        $this->request = $request;
        $this->response = $response;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->storeManager = $storeManager;
        $this->stockState = $stockState;
        $this->quoteItemQtyList = $quoteItemQtyList;
        $this->scopeConfig = $scopeConfig;
        $this->stockResolverFactory = $stockResolverFactory;
        $this->getStockItemDataFactory = $getStockItemDataFactory;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->stockRegistry = $stockRegistry;
        $this->configProvider = $configProvider;
    }

    public function add($productId, $qty) {
        $storeId = $this->storeManager->getStore()->getId();

        $product = $this->productRepository->getById($productId, false, $storeId, true);

        $parentProductsIds = $this->configurableProduct->getParentIdsByChild($productId);
        $parentProductId = !empty($parentProductsIds) ? $parentProductsIds[0] : null;

        if ($this->configProvider->isMsiEnabled()) {
            $qty = $this->determineQty($qty, $product);
        } else {
            $qty = $this->determineQtyWithoutMsi($qty, $product);
        }

        $addToCartParams = array(
            'form_key' => $this->formKey->getFormKey(),
            'qty' => $qty,
        );

        if (is_numeric($parentProductId)) {
            $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId, true);

            $addToCartParams['selected_configurable_option'] = '';
            $addToCartParams['related_product'] = '';

            $addToCartParams['product'] = $parentProduct->getId();

            $options = [];

            $productOptions = $parentProduct->getTypeInstance()->getConfigurableOptions($parentProduct);

            foreach ($productOptions as $attributeId => $variants) {
                foreach($variants as $variant) {
                    if($variant['sku'] != $product->getSku()) {
                        continue;
                    }

                    $options[$attributeId] = $product->getData($variant['attribute_code']);
                }
            }

            $addToCartParams['super_attribute'] = $options;

            $productToAdd = $parentProduct;

        } else {
            $addToCartParams['product'] = $productId;

            $productToAdd = $product;
        }

        $this->cart->addProduct($productToAdd, $addToCartParams);

        return $productToAdd;
    }

    /**
     * Get and prepare the gift product
     *
     * @param string $sku
     * @return ProductInterface|Product
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAddToCartRequest(string $sku, $qty, $discount = null)
    {
        $storeId = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId();
        $product = $this->productRepository->get($sku, false, $storeId);

        $parentProductsIds = $this->configurableProduct->getParentIdsByChild($product->getId());
        $parentProductId = !empty($parentProductsIds) ? $parentProductsIds[0] : null;

        if ($this->configProvider->isMsiEnabled()) {
            $qty = $this->determineQty($qty, $product);
        } else {
            $qty = $this->determineQtyWithoutMsi($qty, $product);
        }

        if($qty == 0) {
            return null;
        }

        $addToCartParams = ['qty' => $qty];

        if(is_numeric($discount) and $discount > 0) {
            $productPrice = $this->getProductPrice($product);
            $addToCartParams['custom_price'] = ($productPrice-($productPrice*($discount/100)));
        }

        if (is_numeric($parentProductId)) {
            $parentProduct = $this->productRepository->getById($parentProductId, false, $storeId, true);

            $addToCartParams['product'] = $parentProduct->getId();

            $options = [];

            $productOptions = $parentProduct->getTypeInstance()->getConfigurableOptions($parentProduct);

            foreach ($productOptions as $attributeId => $variants) {
                foreach ($variants as $variant) {
                    if ($variant['sku'] != $product->getSku()) {
                        continue;
                    }

                    $options[$attributeId] = $product->getData($variant['attribute_code']);
                }
            }

            $addToCartParams['selected_configurable_option'] = '';
            $addToCartParams['related_product'] = '';
            $addToCartParams['super_attribute'] = $options;

            $productToAdd = $parentProduct;
        } else {
            $addToCartParams['product'] = $product->getId();
            $productToAdd = $product;
        }

        return [
            'product' => $productToAdd,
            'request' => $this->dataObjectFactory->create($addToCartParams)
        ];
    }

    /**
     * Get determineQty with MSI module
     *
     * @param $qty
     * @param $product
     * @return float
     */
    protected function determineQty($requestedQty, $product)
    {
        $salesChannel = $this->salesChannelFactory->create();
        $getStockItemData = $this->getStockItemDataFactory->create();
        $stockResolver = $this->stockResolverFactory->create();

        $availableQuantity = 0.0;

        $websiteCode = $product->getStore()->getWebsite()->getCode();
        $stockId = $stockResolver->execute($salesChannel::TYPE_WEBSITE, $websiteCode)->getStockId();
        $stockItemData =$getStockItemData->execute($product->getSku(), $stockId);

        if (isset($stockItemData['quantity'])) {
            $availableQuantity = $stockItemData['quantity'];
        }

        $qtyAlreadyAddedToCart = 0;

        foreach($this->cart->getQuote()->getAllItems() as $quoteItem) {
            if($quoteItem->isDeleted()) {
                continue;
            }

            if($quoteItem->getProduct()->getId() != $product->getId()) {
                continue;
            }

            $qtyAlreadyAddedToCart += $quoteItem->getQty();
        }

        $availableQuantity = $availableQuantity-$qtyAlreadyAddedToCart;

        if($availableQuantity < 0) {
            return 0;
        }

        return min($requestedQty, $availableQuantity);
    }

    /**
     * Get determineQty without MSI module
     *
     * @param $requestedQty
     * @param $product
     * @return float
     */
    protected function determineQtyWithoutMsi($requestedQty, $product)
    {
        $availableQuantity = 0.0;

        // Get stock item data
        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        if ($stockItem && $stockItem->getIsInStock()) {
            $availableQuantity = $stockItem->getQty();
        }

        $qtyAlreadyAddedToCart = 0;

        // Calculate quantity already added to cart
        foreach ($this->cart->getQuote()->getAllItems() as $quoteItem) {
            if ($quoteItem->isDeleted()) {
                continue;
            }
            if ($quoteItem->getProduct()->getId() != $product->getId()) {
                continue;
            }
            $qtyAlreadyAddedToCart += $quoteItem->getQty();
        }

        $availableQuantity -= $qtyAlreadyAddedToCart;
        if ($availableQuantity < 0) {
            return 0;
        }
        return min($requestedQty, $availableQuantity);
    }

    protected function getProductPrice($product)
    {
        $priceIncludesTax = $this->scopeConfig->getValue('tax/calculation/price_includes_tax');
        $finalPrice = $product->getPriceInfo()->getPrice('final_price');

        return $priceIncludesTax ? $finalPrice->getAmount()->getValue() : $finalPrice->getValue();
    }
}

<?php

/** @var $objectManager \Magento\TestFramework\ObjectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);

/** @var $product \Magento\Catalog\Model\Product */
$product = $productRepository->get('simple_product_for_free_gift');

$requestInfo = new \Magento\Framework\DataObject(['qty' => 5]);

/** @var $cart \Magento\Checkout\Model\Cart */
$cart = $objectManager->create(\Magento\Checkout\Model\Cart::class);
$cart->addProduct($product, $requestInfo);
$cart->save();

$objectManager->removeSharedInstance(\Magento\Checkout\Model\Session::class);

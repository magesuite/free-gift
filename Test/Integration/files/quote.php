<?php

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\ObjectManager::getInstance();

/** @var $customerRepository \Magento\Customer\Api\CustomerRepositoryInterface */
$customerRepository = $objectManager->create(\Magento\Customer\Api\CustomerRepositoryInterface::class);
/** @var \Magento\Customer\Model\Session $customerSession */
$customerSession = $objectManager->create(\Magento\Customer\Model\Session::class);

/** @var \Magento\TestFramework\Store\StoreManager $storeManager */
$storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
/** @var Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface */
$productRepositoryInterface = $objectManager->get('Magento\Catalog\Api\ProductRepositoryInterface');
/** @var Magento\Quote\Model\QuoteRepository $cartRepositoryInterface */
$cartRepositoryInterface = $objectManager->get('Magento\Quote\Api\CartRepositoryInterface');
/** @var Magento\Quote\Model\QuoteManagement $cartManagementInterface */
$cartManagementInterface = $objectManager->get('Magento\Quote\Api\CartManagementInterface');

/** @var Magento\Catalog\Model\Product $product */
$product = $productRepositoryInterface->get('simple_product_for_free_gift');

$store = $storeManager->getStore();

$customer = $customerRepository->get('user@example.com');

$customerSession->setCustomerDataAsLoggedIn($customer);

$orderData = [
    'customer_id' => $customer->getId(),
    'customer_email' => $customer->getEmail(),
    'shipping_address' => [
        'firstname' => 'John',
        'lastname' => 'Doe',
        'street' => 'street',
        'city' => 'LA',
        'country_id' => 'US',
        'region' => 'CA',
        'postcode' => '11123',
        'telephone' => '123456789',
        'save_in_address_book' => 1
    ]
];

$cartId = $cartManagementInterface->createEmptyCart();

/** @var Magento\Quote\Model\Quote $quote */
$quote = $cartRepositoryInterface->get($cartId);
$quote
    ->setReservedOrderId(10002)
    ->setStore($store)
    ->setCurrency()
    ->setCustomerId($orderData['customer_id'])
    ->setCustomerEmail($orderData['customer_email'])
    ->setCustomerIsGuest(false)
    ->addProduct($product, 1);


/** @var Magento\Quote\Model\Quote\Address $billingAddress */
$billingAddress = $objectManager->create('Magento\Quote\Api\Data\AddressInterface', ['data' => $orderData['shipping_address']]);
$billingAddress->setAddressType('billing');

/** @var Magento\Quote\Model\Quote\Address $shippingAddress */
$shippingAddress = $objectManager->create('Magento\Quote\Api\Data\AddressInterface', ['data' => $orderData['shipping_address']]);
$shippingAddress->setAddressType('shipping');
/** @var Magento\Quote\Model\Quote\Address\Rate $shippingRate */
$shippingRate = $objectManager->get('Magento\Quote\Model\Quote\Address\Rate');
$shippingRate->setCode('flatrate_flatrate')->getPrice(0);
$shippingAddress->addShippingRate($shippingRate);

$quote->setBillingAddress($billingAddress);
$quote->setShippingAddress($shippingAddress);
$shippingAddress = $quote->getShippingAddress();
$shippingAddress->setCollectShippingRates(true)
    ->collectShippingRates()
    ->setShippingMethod('flatrate_flatrate');

/** @var Magento\Quote\Model\Quote\Payment $payment */
$payment = $objectManager->create('Magento\Quote\Api\Data\PaymentInterface', ['data' => ['is_available' => true]]);
$payment->setMethod('checkmo');
$quote->setPayment($payment);
$quote->setInventoryProcessed(false);
$quote->collectTotals();
$quote->save();

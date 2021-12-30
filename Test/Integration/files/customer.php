<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Customer\Model\Customer $customer */
$customer = $objectManager->create(\Magento\Customer\Model\Customer::class);

$customer->setWebsiteId(1)
    ->setId(1)
    ->setEmail('user@example.com')
    ->setPassword('password')
    ->setGroupId(1)
    ->setStoreId(1)
    ->setIsActive(1)
    ->setFirstname('John')
    ->setLastname('Doe')
    ->setGender(0);

$customer->isObjectNew(true);

$customer->save();

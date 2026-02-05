<?php

declare(strict_types=1);

namespace MageSuite\FreeGift\Test\Integration\Observer;

class RemoveCouponRelatedGiftTest extends \Magento\TestFramework\TestCase\AbstractController
{
    protected const QUOTE_RESERVED_ID = 'test01';
    protected const FREE_GIFT_SKU = 'free-gift-product';
    protected const COUPON_CODE = 'coupon_code';

    protected ?\Magento\Framework\App\ObjectManager $objectManager;
    protected ?\Magento\Checkout\Model\Session $checkoutSession;
    protected ?\Magento\Quote\Model\QuoteRepository $quoteRepository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->checkoutSession = $this->objectManager->get(\Magento\Checkout\Model\Session::class);
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class);
    }

    /**
     * @magentoDataFixture MageSuite_FreeGift::Test/Integration/_files/free_gift_once_sales_rule_with_coupon.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testFreeGiftItemIsRemovedFromCartAfterRemovingCoupon(): void
    {
        $quote = $this->getQuote();
        // From Magento 2.4.3 following setting being set to true caused incorrect totals calculation in the test
        $quote->setIsMultiShipping(false);
        $quote->save();

        $this->checkoutSession->replaceQuote($quote);

        $this->sendCouponCodeRequest([
            'remove' => 0,
            'coupon_code' => self::COUPON_CODE
        ]);

        $quote = $this->quoteRepository->get($this->checkoutSession->getQuote()->getId());
        self::assertCount(2, $quote->getItems());
        self::assertEquals(self::FREE_GIFT_SKU, $quote->getItems()[1]->getSku());

        $this->sendCouponCodeRequest([
            'remove' => 1,
            'coupon_code' => ''
        ]);

        $quote = $this->quoteRepository->get($this->checkoutSession->getQuote()->getId());
        self::assertCount(1, $quote->getItems());
        self::assertEquals('simple', $quote->getItems()[0]->getSku());
    }

    protected function sendCouponCodeRequest(array $inputData): void
    {
        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setParams($inputData);
        $this->dispatch('checkout/cart/couponPost/');
    }

    protected function getQuote(): \Magento\Quote\Api\Data\CartInterface
    {
        /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory */
        $searchCriteriaBuilderFactory = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilderFactory::class);
        $searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', self::QUOTE_RESERVED_ID)->create();

        $quoteRepository = $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class);
        $quotes = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($quotes);
    }
}

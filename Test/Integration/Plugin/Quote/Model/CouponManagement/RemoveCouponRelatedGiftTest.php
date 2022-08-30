<?php

namespace MageSuite\FreeGift\Test\Integration\Plugin\Quote\Model\CouponManagement;

class RemoveCouponRelatedGiftTest extends \PHPUnit\Framework\TestCase
{
    const QUOTE_RESERVED_ID = 'test01';

    const FREE_GIFT_SKU = 'free-gift-product';

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Quote\Model\CouponManagement
     */
    protected $couponManagement;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class);
        $this->couponManagement = $this->objectManager->get(\Magento\Quote\Model\CouponManagement::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDataFixture freeGiftOnceSalesRuleFixture
     */
    public function testFreeGiftItemIsRemovedFromCartAfterRemovingCouponAndItCanBeAddedAgain()
    {
        $quote = $this->getQuote();
        $this->couponManagement->set($quote->getId(), 'coupon_code');

        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(2, $quote->getItems());
        self::assertEquals(self::FREE_GIFT_SKU, $quote->getItems()[1]->getSku());

        $this->couponManagement->remove($quote->getId());
        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(1, $quote->getItems());
        self::assertEquals('simple', $quote->getItems()[0]->getSku());

        $this->couponManagement->set($quote->getId(), 'coupon_code');
        $quote = $this->quoteRepository->get($quote->getId());
        self::assertCount(2, $quote->getItems());
        self::assertEquals(self::FREE_GIFT_SKU, $quote->getItems()[1]->getSku());
    }

    protected function getQuote()
    {
        /** @var \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory */
        $searchCriteriaBuilderFactory = $this->objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilderFactory::class);
        $searchCriteriaBuilder = $searchCriteriaBuilderFactory->create();
        $searchCriteria = $searchCriteriaBuilder->addFilter('reserved_order_id', self::QUOTE_RESERVED_ID)->create();

        $quoteRepository = $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class);
        $quotes = $quoteRepository->getList($searchCriteria)->getItems();

        return array_pop($quotes);
    }

    public static function  freeGiftOnceSalesRuleFixture()
    {
        require __DIR__ . '/../../../../files/free_gift_once_sales_rule_with_coupon.php';
    }
}

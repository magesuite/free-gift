<?php

namespace MageSuite\FreeGift\Test\Integration\Observer;

class RemoveCouponRelatedGiftTest extends \Magento\TestFramework\TestCase\AbstractController
{
    const QUOTE_RESERVED_ID = 'test01';

    const FREE_GIFT_SKU = 'free-gift-product';

    const COUPON_CODE = 'coupon_code';

    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    protected function setUp()
    {
        parent::setUp();
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->checkoutSession = $this->objectManager->get(\Magento\Checkout\Model\Session::class);
        $this->quoteRepository = $this->objectManager->get(\Magento\Quote\Model\QuoteRepository::class);
    }

    /**
     * @magentoDataFixture freeGiftOnceSalesRuleFixture
     * @magentoDataFixture quoteFixture
     * @magentoAppIsolation enabled
     */
    public function testFreeGiftItemIsRemovedFromCartAfterRemovingCoupon()
    {
        $quote = $this->getQuote();
        $this->checkoutSession->setQuoteId($quote->getId());

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

    protected function sendCouponCodeRequest($inputData)
    {
        $this->getRequest()->setMethod(\Magento\Framework\App\Request\Http::METHOD_POST);
        $this->getRequest()->setParams($inputData);
        $this->dispatch(
            'checkout/cart/couponPost/'
        );
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

    public static function quoteFixture()
    {
        require __DIR__ . '/../../../../../../dev/tests/integration/testsuite/Magento/Sales/_files/quote.php';
    }

    public static function  freeGiftOnceSalesRuleFixture()
    {
        require __DIR__ . '/../files/free_gift_once_sales_rule_with_coupon.php';
    }
}

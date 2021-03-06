<?php

namespace Oyst\OneClick\Model;

abstract class AbstractOystManagement
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /**
     * @var \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var \Oyst\OneClick\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\SalesRule\Model\CouponFactory
     */
    protected $couponFactory;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    protected $imageFactory;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $newsletterSubscriberFactory;

    /**
     * @var \Oyst\OneClick\Helper\Data
     */
    protected $helperData;

    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory,
        \Oyst\OneClick\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\SalesRule\Model\CouponFactory $couponFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Catalog\Helper\ImageFactory $imageFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Newsletter\Model\SubscriberFactory $newsletterSubscriberFactory,
        \Oyst\OneClick\Helper\Data $helperData
    )
    {
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->couponFactory = $couponFactory;
        $this->coreRegistry = $coreRegistry;
        $this->imageFactory = $imageFactory;
        $this->appEmulation = $appEmulation;
        $this->eventManager = $eventManager;
        $this->scopeConfig = $scopeConfig;
        $this->newsletterSubscriberFactory = $newsletterSubscriberFactory;
        $this->helperData = $helperData;
        $this->disableRegionRequired();
    }

    protected function getMagentoCustomer($email)
    {
        try {
            return $this->customerRepository->get($email);
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return $this->customerDataFactory->create();
        }
    }

    protected function addNewsletterSubscriberToCustomer($customer)
    {
        if ($customer->getId()) {
            $newsletterSubscriber = $this->newsletterSubscriberFactory->create()->loadByCustomerId($customer->getId());
            $customer->setData('newsletter_subscriber', $newsletterSubscriber);
        }
    }

    public function getMagentoQuoteByOystId($oystId)
    {
        return $this->quoteCollectionFactory->create()
            ->addFieldToFilter('oyst_id', $oystId)
            ->addFieldToFilter('is_active', 1)
            ->setOrder('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->getFirstItem();
    }

    public function getMagentoProductsById($ids, $storeId)
    {
        $products = $this->productCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $ids])
            ->addAttributeToSelect('*')
            ->setStore($storeId)
            ->addFinalPrice();

        $this->eventManager->dispatch(
            'oyst_oneclick_model_oyst_management_get_magento_products_by_id',
            ['products' => $products, 'store_id' => $storeId]
        );

        foreach ($products as $product) {
            $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $product->setOystImageUrl($this->imageFactory->create()->init($product, 'cart_page_product_thumbnail')->getUrl());
            $this->appEmulation->stopEnvironmentEmulation();
        }

        return $products;
    }

    public function getMagentoCoupon($oystCoupons)
    {
        if (empty($oystCoupons)) {
            return $this->couponFactory->create();
        } else {
            foreach ($oystCoupons as $oystCoupon) {
                $coupon = $this->couponFactory->create()->load($oystCoupon->getCode(), 'code');
                if (!$coupon->getId()) {
                    throw new \Exception('Invalid coupon code : '.$oystCoupon->getCode(), 2);
                }
                return $coupon;
            }
        }
    }

    public function getMagentoOrderByOystId($oystId)
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('oyst_id', $oystId)
            ->setOrder('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->getFirstItem();
    }

    public function getMagentoOrderByQuoteId($quoteId)
    {
        return $this->orderCollectionFactory->create()
            ->addFieldToFilter('quote_id', $quoteId)
            ->setOrder('entity_id', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
            ->getFirstItem();
    }

    protected function disableRegionRequired()
    {
        $this->coreRegistry->register(
            \Oyst\OneClick\Helper\Constants::DISABLE_REGION_REQUIRED_REGISTRY_KEY, true, true
        );
    }

    protected function getAdditionalDataFromOystObject($oystObject, $key = null)
    {
        $additionalData = $oystObject->getAdditionalData();

        if ($key == null) {
            return $additionalData;
        } else {
            $result = null;

            foreach ($additionalData as $keyValuePair) {
                if ($keyValuePair['key'] = $key) {
                    $result = $keyValuePair['value'];
                    break;
                }
            }

            return $result;
        }
    }
}

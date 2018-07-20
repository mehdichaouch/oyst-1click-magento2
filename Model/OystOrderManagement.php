<?php

namespace Oyst\OneClick\Model;

class OystOrderManagement extends AbstractOystManagement implements \Oyst\OneClick\Api\OystOrderManagementInterface
{
    protected $cartManagement;

    protected $oystOrderBuilder;

    protected $orderFactory;

    public function __construct(
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Oyst\OneClick\Model\OystOrder\Builder $oystOrderBuilder,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Quote\Model\ResourceModel\Quote\CollectionFactory $quoteCollectionFactory
    )
    {
        $this->orderFactory = $orderFactory;
        $this->cartManagement = $cartManagement;
        $this->oystOrderBuilder = $oystOrderBuilder;
        parent::__construct($customerRepository, $customerDataFactory, $quoteCollectionFactory);
    }

    public function createOrderFromOystCheckout($oystId)
    {
        $quote = $this->getMagentoQuoteByOystId($oystId);

        if (!$quote->getId()) {
            throw new \Exception('Quote is not available.');
        }

        $this->cartManagement->placeOrder($quote->getId());

        return $this->getOystOrderFromMagentoOrder($oystId);
    }

    public function getOystOrderFromMagentoOrder($oystId)
    {
        $order = $this->orderFactory->create()->load($oystId, 'oyst_id');

        if (!$order->getId()
         || $order->getPayment()->getMethod() != \Oyst\OneClick\Model\Payment\Method\OneClick::PAYMENT_METHOD_OYST_ONECLICK_CODE) {
            throw new \Exception('Order is not available.');
        }

        return $this->oystOrderBuilder->buildOystOrder($order);
    }
}
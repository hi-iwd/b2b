<?php

namespace IWD\B2B\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\CustomerFactory;
use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory;

class CheckB2BCustomerConfirmed implements ObserverInterface
{
    protected $customerFactory;
    
    protected $extensibleDataObjectConverter;
    
    protected $_b2bCustomerFactory;
    
    /**
     * @var \IWD\B2B\Helper\Emails
     */
    protected $_helper;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    public function __construct(
            CustomerFactory $customerFactory,
            \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
            B2BCustomerFactory $b2bCustomerFactory,
            \IWD\B2B\Helper\Emails $helper_emails,
            \Psr\Log\LoggerInterface $logger
    ) {
        $this->customerFactory = $customerFactory;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->_b2bCustomerFactory = $b2bCustomerFactory;
        $this->_helper = $helper_emails;
        $this->logger = $logger;
    }
    
    /**
     * Send Approved email for B2B customer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // Magento\Customer\Api\Data\CustomerInterfaceFactory;
        $customer = $observer->getCustomer();
//        $model = $this->customerFactory->create();
//        $customerOriginal = $model->load($customer->getId());
        $B2BCustomerModel = $this->_b2bCustomerFactory->create();
        $B2BCustomer = $B2BCustomerModel->load($customer->getId(), 'customer_id');
        // convert to array
        /*$customerNewData = $this->extensibleDataObjectConverter->toNestedArray(
                $customer,
                [],
                '\Magento\Customer\Api\Data\CustomerInterface'
        );*/
        
        $isActive = $observer->getBtbActive();
        $isActiveCurreny = $B2BCustomer->getData('btb_active');
        if ($isActiveCurreny == 0 && $isActive == 1) {
            // add b2b record if does not exists
// 16.09 - we do not need it for companies logic            
//            $this->_cerateB2BRecord($customer, $observer);
            
            $this->_helper->approveCustomerEmail($customer);
        }

        return $this;
    }
    
    protected function _cerateB2BRecord($customer, $observer)
    {
        $model = $this->_b2bCustomerFactory->create();
        $customerInfo = $model->load($customer->getId(), 'customer_id');
        
        $exists_customer_id = $customerInfo->getCustomerId();
        if ($exists_customer_id != null && !empty($exists_customer_id)) {
            return true; // record already exists
        }
        
        // need create b2b record
        $customerInfo->setCustomerId($customer->getId());
        
        try {
            $customerInfo->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        
    }
    
}

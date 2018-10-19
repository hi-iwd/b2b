<?php

namespace IWD\B2B\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class CustomersPreDispatchObserver implements ObserverInterface
{    
    /** 
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;
    
    /**
     * @var \IWD\B2B\Helper\Company
     */
    protected $_helper;
    
    /**
     * @param \Magento\Config\Model\ResourceModel\Config $configResource,
     * @param \IWD\B2B\Helper\Company $b2bData
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \IWD\B2B\Helper\Company $b2bComapny
    ) {
        $this->configResource  = $configResource;
        $this->_helper = $b2bComapny;
    }

    /**
     * Assign Company name to existing B2B customers
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getControllerAction()->getRequest();
        $fullAction = $request->getFullActionName();
        
        if(!in_array($fullAction, ['customer_index_index', 'b2b_company_index']))
            return $this;
        
        // check if companies already assigned
        $path = 'b2b/companies_assigned';
        $scope = 'default';
        $scopeId = 0;

        $this->_helper->assignCompanyName();
        
        $this->_helper->cleanCompanyName();
        
        // save flag that companies assigned
        $date = date('Y-m-d H:i:s');
        $this->configResource->saveConfig($path, $date, $scope, $scopeId);

        return $this;
    }
}

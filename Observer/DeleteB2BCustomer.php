<?php

namespace IWD\B2B\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class DeleteB2BCustomer implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->_resource = $resource;
    }

    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;
    
    /**
     * Delete B2B customer when Magento customer is deleted
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $requestInterface = $objectManager->get('Magento\Framework\App\RequestInterface');
        
        $fullAction     = $requestInterface->getFullActionName();

        $customer = $observer->getEvent()->getCustomer();
        $parent_id = $customer->getId();
        
        $request = $requestInterface;
        
        $skip_ids = [];

        $params = $request->getParams();
        
        if ($fullAction == 'customer_index_massDelete') {
            $params = $request->getParams();
            if (isset($params['selected']))
                $skip_ids = $params['selected'];
            
            // check if all were selected
            if (isset($params['excluded'])) {                
                if (isset($params['namespace']) && $params['namespace'] == 'customer_listing') {
                    return $this;
                }
            }
        }

        if ($fullAction == 'b2b_company_massdelete') {
            $params = $request->getParams();
            if (isset($params['entity_id']))
                $skip_ids = $params['entity_id'];
        }
        
        if (!empty($parent_id)) { // remove all b2b sub accounts
            $tableName = $this->_resource->getTableName('iwd_b2b_customer_info');
            
            $collection = $this->_customerFactory->create()->getCollection();
            $collection->getSelect()
                ->join(["b2b_customer" => $tableName], "e.entity_id = b2b_customer.customer_id AND b2b_customer.parent_id =".$parent_id);
            if (!empty($skip_ids)) {
                $collection->getSelect()->where('e.entity_id', ['nin' => $skip_ids]);
            }
            
            foreach ($collection as $cust) {
                $cust_id = $cust->getCustomerId();
                try {
                    $this->_customerRepository->deleteById($cust_id);                    
                } catch (\Exception $exception) {
                }
                
            }
        }

        return $this;
    }
}

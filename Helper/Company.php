<?php
namespace IWD\B2B\Helper;

use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory;
use IWD\B2B\Model\CompanyFactory as B2BCompanyFactory;

class Company extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \IWD\B2B\Model\CustomerFactory
     */
    protected $_b2bCustomerFactory;

    /**
     * @var \IWD\B2B\Model\CompanyFactory
     */
    protected $_b2bCompanyFactory;
    
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            B2BCustomerFactory $b2bCustomerFactory,
            B2BCompanyFactory $b2bCompanyFactory,
            \Magento\Customer\Model\CustomerFactory $customerFactory,
            \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->_b2bCustomerFactory = $b2bCustomerFactory;
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        $this->_customerFactory = $customerFactory;
        $this->_resource = $resource;
    }

    public function copyCompanyDataToCustomer($customer_id, $company_id, $addon = []) {
        $company = $this->getCompanyInfo($company_id);
        
        $customers = [];
        
        if (!empty($customer_id)) {
            $customers[] = $customer_id;
        }
        else { // select all company users
            $B2BCustomers = $this->_b2bCustomerFactory->create()->getCollection();
            $B2BCustomers->addFieldToFilter('company_id', ['eq' => $company_id]);
            
            $customers = $B2BCustomers;
        }
            
        $all_success = true;
        foreach ($customers as $_customer) {
            if (!is_numeric($_customer))
                $_customer = $_customer->getCustomerId();
            
            $_customer = $this->getCustomerInfo($_customer);
            
            $success = $this->copyData($_customer, $company, $addon);
            if (!$success)
                $all_success = false;
        }

        return $all_success;
    }
    
    public function copyData($_customer, $company, $addon = []) {
        
        $companyName = $company->getData('store_name');
        $group_id = $company->getData('group_id');
        $taxvat = $company->getData('ssn');

        if (isset($addon['btb_company']))
            $companyName = $addon['btb_company'];
        if (isset($addon['group_id']))
            $group_id = $addon['group_id'];
        if (isset($addon['taxvat']))
            $taxvat = $addon['taxvat'];
        
        try {
            $status = $_customer->getData('btb_active');
            $status = empty($status)?0:1;
            if (isset($addon['btb_active']))
                $status = $addon['btb_active'];
            $_customer->setData('btb_active', $status);
            $_customer->setData('btb_company', $companyName);
            $_customer->setGroupId($group_id);
            if (!empty($taxvat))
                $_customer->setData('taxvat', $taxvat);
        
            $_customer->save();
        
            return true;
        } catch (\Exception $e) {
            if (isset($addon['log']))
                $this->_logger->critical($e);
        }
        
        return false;
    }
    
    public function getCustomerInfo($customer_id) {
        $_customer = $this->_customerFactory->create();
        $_customer = $_customer->load($customer_id);

        return $_customer;
    }

    public function getCompanyInfo($company_id) {
        $_company = $this->_b2bCompanyFactory->create();
        $_company = $_company->load($company_id);

        return $_company;
    }

    /**
     * Assign company name to b2b customers
     */
    public function assignCompanyName(){
        $tableName = $this->_resource->getTableName('customer_entity');
        
        $customers = $this->_b2bCustomerFactory->create()->getCollection();
        $customers->getSelect()->join(["customer_entity" => $tableName], "customer_entity.entity_id = main_table.customer_id");
        $customers->addFieldToFilter('company_id', ['neq' => 0]);
        $companies = [];
        
        foreach ($customers as $b2b_customer) {
        
            $customer_id = $b2b_customer->getData('customer_id');
        
            $company_id = $b2b_customer->getData('company_id');
            if (empty($company_id))
                continue;
        
            if (!isset($companies[$company_id])) {
                $company = $this->getCompanyInfo($company_id);
                $companies[$company_id] = $company;
            }
            $company = $companies[$company_id];
            
            $company_name = $company->getData('store_name');
            $taxvat = $company->getData('ssn');
            
            $customer = $this->getCustomerInfo($customer_id);
            
            $current_company_name = $customer->getData('btb_company');
            $current_taxvat = $customer->getData('taxvat');
            if (!empty($current_company_name) && (empty($taxvat) || $current_taxvat == $taxvat)) // customer already has company name
                continue;

            $customer->setData('btb_company', $company_name);
            if (!empty($taxvat))
                $customer->setData('taxvat', $taxvat);
            
            $btb_active = $customer->getData('btb_active');
            $btb_active = empty($btb_active)?0:1;
            $customer->setData('btb_active', $btb_active);
            
            try {
                $customer->save();
            } catch (\Exception $e) {
            }
        }
        
    }
    
    /**
     * clean company name from unassigned customers
     */
    public function cleanCompanyName() {
        $table1 = $this->_resource->getTableName('iwd_b2b_customer_info');
        
        $collection = $this->_customerFactory->create()->getCollection();
        $collection->addAttributeToFilter('btb_company', ['neq' => '']);
        $collection->getSelect()->columns(['user_id'=>'e.entity_id']);
        $collection->getSelect()->join([$table1], "e.entity_id = {$table1}.customer_id");
        $collection->getSelect()->where(" {$table1}.company_id = 0 ");
        
        foreach ($collection as $customer) {
            $customer_id = $customer->getData('user_id');
        
            $customer = $this->getCustomerInfo($customer_id);
                    
            $customer->setData('btb_active', null);
            $customer->setData('btb_company', '');
        
            try {
                $customer->save();
            } catch (\Exception $e) {
            }
        }
        
    }
    
    public function unassignUsers($companyId, $companyUserIds = [], $all = false) {
        $table1 = $this->_resource->getTableName('iwd_b2b_customer_info');
        
        $collection = $this->_customerFactory->create()->getCollection();
        if (!$all)
            $collection->addFieldToFilter('entity_id', ['in' => $companyUserIds]);
        $collection->getSelect()->columns(['user_id'=>'e.entity_id']);
        $collection->getSelect()->join([$table1], "e.entity_id = {$table1}.customer_id");
        $collection->getSelect()->where(" {$table1}.company_id = '{$companyId}' ");
        
        $updatedCnt = 0;
        $errorsCnt = 0;
        
        // get company info
        $size = count($collection);
        if ($size) {
        
            foreach ($collection as $customer) {
                $customer_id = $customer->getData('user_id');
        
                $params = ['company_id' => 0];
        
                $b2b_model = $this->_b2bCustomerFactory->create()->load($customer_id, 'customer_id');
        
                // save b2b record
                $b2b_model->addData($params);
                try{
                    $b2b_model->save();
                } catch (\Exception $e) {
                    $errorsCnt++;
                    continue;
                }
        
                // update customer info
                $_customer = $this->_customerFactory->create();
                $_customer = $_customer->load($customer_id);
        
                try {
                    $_customer->setData('btb_active', null);
                    $_customer->setData('btb_company', '');
        
                    $_customer->save();
                    $updatedCnt++;
                } catch (\Exception $e) {
                    $errorsCnt++;
                }
        
            }
        }
        
        return ['updated' => $updatedCnt, 'errors' => $errorsCnt];
    }
    
    /**
     * Add company name to order grid
     */
    public function assignCompanyToOrder($order)
    {
        $customer_id = $order->getCustomerId();
        $increment_id = $order->getIncrementId();
        
        if (empty($customer_id) || empty($increment_id))
            return false;
        
        // get customers company name
        $tableName = $this->_resource->getTableName('iwd_b2b_company');
        
        $companies = $this->_b2bCustomerFactory->create()->getCollection();
        $companies->getSelect()->join(["b2b_company" => $tableName], "b2b_company.company_id = main_table.company_id");
        $companies->addFieldToFilter('customer_id', $customer_id);
        
        $company_name = '';
        foreach ($companies as $company) {
            $company_name = $company->getData('store_name');
            break;
        }
        
        if (!empty($company_name)) {
            $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
            
            $tableName = $this->_resource->getTableName('sales_order_grid');
            
            try {
                // copy status/comments history
                $sql = "UPDATE `{$tableName}` SET `company_name` = '{$company_name}' WHERE `customer_id` = '{$customer_id}' AND `increment_id` = '{$increment_id}' ";
            
                $connection->rawQuery($sql);
            
            } catch (\Exception $e) { }
        }

        return true;
    }
    
    /**
     * get count of approved companies
     */
    public function getCountApprovedCompanies()
    {
        $companies = $this->_b2bCompanyFactory->create()->getCollection();
        $companies = $companies->addFieldToFilter('is_active', ['neq' => 2]);
        
        return count($companies);
    }
}

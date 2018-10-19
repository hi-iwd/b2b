<?php
namespace IWD\B2B\Controller\Adminhtml\Company;

use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory;

class MassAssign extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;
    
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \IWD\B2B\Model\CustomerFactory
     */
    protected $_b2bCustomerFactory;

    /**
     * @var \IWD\B2B\Helper\Company
     */
    protected $_company_helper;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        B2BCustomerFactory $b2b_customerFactory,
        \IWD\B2B\Helper\Company $helper,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->_customerFactory = $customerFactory;
        $this->_b2bCustomerFactory = $b2b_customerFactory;
        $this->_company_helper = $helper;
        $this->_resource = $resource;
    }
    
    public function execute()
    {
        $companyUserIds = $this->getRequest()->getParam('entity_id');
        $companyId = $this->getRequest()->getParam('id');
        
        $table1 = $this->_resource->getTableName('iwd_b2b_customer_info');
        
        $collection = $this->_customerFactory->create()->getCollection();
        $collection->addFieldToFilter('entity_id', ['in' => $companyUserIds]);
        $collection->getSelect()->columns(['user_id'=>'e.entity_id']);
        $collection->getSelect()->joinLeft([$table1], "e.entity_id = {$table1}.customer_id");
        $collection->getSelect()->where(" {$table1}.company_id IS NULL OR {$table1}.company_id = 0 OR {$table1}.company_id = '' ");

        $updatedCnt = 0;
        $errorsCnt = 0;
        
        // get company info
        $size = count($collection);
        if ($size) {
            $company = $this->_company_helper->getCompanyInfo($companyId);
            $companyName = $company->getData('store_name');
            $group_id = $company->getData('group_id');
        
            // get company primary user
            $b2b_primary = $this->_b2bCustomerFactory->create()->getCollection();
            $b2b_primary->addFieldToFilter('company_id', $companyId)
                ->addFieldToFilter('role_id', 0)
                ->addFieldToFilter('parent_id', 0);
            
            $parent_id = 0;
            foreach ($b2b_primary as $prime) {
                $parent_id = $prime->getData('customer_id');
                break;
            }
        
            foreach ($collection as $customer) {
                // check if exists b2b record
                $b2b_customer_id = $customer->getData('customer_id');
                $customer_id = $customer->getData('user_id');

                $b2b_model = $this->_b2bCustomerFactory->create();
                
                $params = ['parent_id' => $parent_id, 'customer_id' => $customer_id, 'company_id' => $companyId];
                
                $role_id = 0;
                
                if (empty($b2b_customer_id)) { // customer does not have b2b record
                    if (!empty($parent_id))
                        $role_id = 1;
                }
                else{
                    $b2b_model = $b2b_model->load($customer_id, 'customer_id');
                    
                    $role_id = $customer->getData('role_id');
                    if (!empty($parent_id)) {
                        if ($role_id == 0)
                            $role_id = 1;
                    }
                    else {
                        $role_id = 0;
                    }
                }
                    
                $params['role_id'] = $role_id;

                // save b2b record
                $b2b_model->addData($params);
                try{
                    $b2b_model->save();
                } catch (\Exception $e) {
                    $errorsCnt++;
                    continue;
                }
                
                // update customer info 
                $sucess = $this->_company_helper->copyCompanyDataToCustomer($customer_id, $companyId);
                $sucess = true;
                if ($sucess)
                    $updatedCnt++;
                else
                    $errorsCnt++;    
            }
        }
                
        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been assigned.', $updatedCnt)
        );
        
        if ($errorsCnt > 0) {
            $this->messageManager->addError(
                __('A total of %1 record(s) have not been assigned.', $errorsCnt)
            );
        }
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        return $resultRedirect->setPath('*/*/edit', ['company_id' => $companyId]);
    }
}


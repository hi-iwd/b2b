<?php

namespace IWD\B2B\Controller\Adminhtml\Company;

use Magento\Backend\App\Action;

/**
 * Class Save
 * @package IWD\B2B\Controller\Adminhtml\Company
 */
class Save extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \IWD\B2B\Model\CompanyFactory
     */
    protected $_b2bCompanyFactory;

    /**
     * @var \IWD\B2B\Model\CustomerFactory
     */
    protected $_B2BCustomerFactory;

    /**
     * @var \IWD\B2B\Helper\Approve
     */
    protected $_b2b_approve_helper;

    /**
     * @var \IWD\B2B\Helper\Company
     */
    protected $_b2b_company_helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\Controller\Result\Redirect
     */
    private $resultRedirect;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \IWD\B2B\Model\CompanyFactory $b2bCompanyFactory
     * @param \IWD\B2B\Model\ResourceModel\Customer\CollectionFactory $B2BCustomerCollectionFactory
     * @param \IWD\B2B\Model\CustomerFactory $B2BCustomerFactory
     * @param \IWD\B2B\Helper\Approve $b2b_approve_helper
     * @param \IWD\B2B\Helper\Company $b2b_company_helper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \IWD\B2B\Model\CompanyFactory $b2bCompanyFactory,
        \IWD\B2B\Model\ResourceModel\Customer\CollectionFactory $B2BCustomerCollectionFactory,
        \IWD\B2B\Model\CustomerFactory $B2BCustomerFactory,
        \IWD\B2B\Helper\Approve $b2b_approve_helper,
        \IWD\B2B\Helper\Company $b2b_company_helper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        $this->_B2BCustomerCollectionFactory = $B2BCustomerCollectionFactory;
        $this->_B2BCustomerFactory = $B2BCustomerFactory;
        $this->_b2b_approve_helper = $b2b_approve_helper;
        $this->_b2b_company_helper = $b2b_company_helper;
        $this->_customerFactory = $customerFactory;
        $this->_resource = $resource;

        parent::__construct($context);

        $this->resultRedirect = $this->resultRedirectFactory->create();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('IWD_B2B::b2b_company');
    }

    /**
     * Save action
     */
    public function execute()
    {
        // check if data sent
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('*/*/');
        }

        $companyId = $this->getRequest()->getParam('company_id');

        $session = $this->_objectManager->get('Magento\Backend\Model\Session');
        $model = $this->_b2bCompanyFactory->create()->load($companyId);
        if (!$model->getId() && $companyId) {
            $this->messageManager->addErrorMessage(__('This company no longer exists.'));
            return $this->resultRedirect->setPath('*/*/');
        }

        $originalData = $model->getData();

        $companyExists = $this->isCompanyNameUnique();
        if ($companyExists) {
            return $this->errorRedirect($companyExists, $originalData, $companyId);
        }

        $currentImage = $model->getImage();
        $certificateImage = $model->getCertificate();

        if(isset($data['credit_limit'])) {
            $data['credit_limit'] = str_replace(',', '', $data['credit_limit']);
        }

        // company status before
        $old_status = $model->getData('is_active');
        $old_group = $model->getData('group_id');
        $old_company_name = $model->getData('store_name');
        $old_taxvat = $model->getData('ssn');

        // save image data and remove from data array
        $imageData = [];
        if (isset($data['image'])) {
            $imageData = $data['image'];
            unset($data['image']);
        }

        $certificateData = [];
        if (isset($data['certificate'])) {
            $certificateData = $data['certificate'];
            unset($data['certificate']);
        }
      
        $model->setData($data);

        $status = (int)$model->getData('is_active');
        $groupID = $model->getData('group_id');
        $company_name = $model->getData('store_name');
        $taxvat = $model->getData('ssn');

        $b2bCustomersIds = [];

        // if status or group was changed - need update customers assigned to company
        $comp_id = $model->getData('company_id');
 
        if (!empty($comp_id)) { // for exsisting company only
            if ($status != $old_status) {
                $B2BCustomers = $this->_B2BCustomerFactory->create()->getCollection();
                $B2BCustomers->addFieldToFilter('company_id', ['eq' => $comp_id]);
                foreach ($B2BCustomers as $b2bCustomer) {
                    $custId = $b2bCustomer->getData('customer_id');

                    $b2bCustomersIds[] = $custId;

                    $customer = $this->_b2b_company_helper->getCustomerInfo($custId);
                    if ($customer && $customer->getId()) {
                        $customer->setData('btb_active', $status);
                        $b2bCustomer->setData('btb_active', $status);

                        // if company status changed to active, and b2b user is primary user
                        // need to try to send Confirmation B2B email
                        if ($status == 1 && $b2bCustomer->getData('role_id') == 0) {
                            $customerData = $this->_b2b_approve_helper->getCustomerData($custId, ['btb_active' => 1]);

                            $this->_eventManager->dispatch(
                                'adminhtml_company_customer_prepare_save',
                                ['customer' => $customerData, 'btb_active' => $status]
                            );
                        }

                        if ($old_company_name != $company_name) {
                            $customer->setData('btb_company', $company_name);
                        }

                        try {
                            //$customer->save();
                            $b2bCustomer->save();
                        } catch (\Exception $e) {

                        }
                    }
                }
            }

            // if some company fields were modified
            if ($groupID != $old_group
                || $old_company_name != $company_name
                || $old_taxvat != $taxvat) {

                // copy company data to customers
                $addon = [
                    'btb_company' => $company_name,
                    'group_id' => $groupID,
                    'taxvat' => $taxvat
                ];
                if ($status == 1) {
                    $addon['btb_active'] = $status;
                }

                $this->_b2b_company_helper->copyCompanyDataToCustomer(false, $comp_id, $addon);
            }
        }

        if ($model->getData('active_limit') == 0) {
            $null = new \Zend_Db_Expr("NULL");
            $model->setData('credit_limit', $null);
            $model->setData('available_credit', $null);
        }

        $this->updateCompanyNameInOrders($b2bCustomersIds, $old_company_name, $company_name);

        // try to save it
        try {
            $imageFile = $this->uploadImage($imageData, $currentImage);
            $model->setImage($imageFile);

            $imageFile = $this->uploadCertificate($certificateData, $certificateImage);
            $model->setCertificate($imageFile);

            // save the data
            $model->save();

            $this->saveCompanyPayments($data, $model->getId());
			     $this->saveCompanyShippings($data, $model->getId());

            // display success message
            $this->messageManager->addSuccessMessage(__('The company has been saved.'));

            // clear previously saved data from session
            $session->setFormData(false);

            // check if 'Save and Continue'
            if ($this->getRequest()->getParam('back')) {
                return $this->resultRedirect->setPath('*/*/edit', ['company_id' => $model->getId()]);
            }
            return $this->resultRedirect->setPath('*/*/');
        } catch (\Exception $e) {
            return $this->errorRedirect($e->getMessage(), $originalData, $companyId);
        }
    }

    /**
     * check if company name is unique
     * @return bool
     */
    private function isCompanyNameUnique()
    {
        $companyExists = false;

        // try {
        //     $collection = $this->_b2bCompanyFactory->create()->getCollection();
        //     $collection->addFieldToFilter('store_name', ['like'=> $data['store_name']]);
        //     $companyId = $this->getRequest()->getParam('company_id');
        //     if (!empty($companyId))
        //         $collection->addFieldToFilter('company_id', ['neq'=> $companyId]);
        //
        //     foreach ($collection as $col) {
        //         $companyExists = __('A company with this name already exists');
        //         break;
        //     }
        // } catch (\Exception $e) {
        //     $companyExists = $e->getMessage();
        // }
        //

        return $companyExists;
    }

    /**
     * try update company name in orders
     *
     * @param $b2bCustomersIds
     * @param $oldCompanyName
     * @param $companyName
     */
    private function updateCompanyNameInOrders($b2bCustomersIds, $oldCompanyName, $companyName)
    {
        if (!empty($b2bCustomersIds)) {
            if ($oldCompanyName != $companyName) {
                $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
                $tableName = $this->_resource->getTableName('sales_order_grid');
                try {
                    // copy status/comments history
                    $sql = "UPDATE `{$tableName}` SET `company_name` = '{$companyName}' WHERE `customer_id` IN ('" . implode("','", $b2bCustomersIds) . "') ";

                    $connection->rawQuery($sql);

                } catch (\Exception $e) {

                }
            }
        }
    }

    /**
     * @param $imageData
     * @param $currentImage
     * @return null
     */
    private function uploadImage($imageData, $currentImage)
    {
        $imageFile = null;
        $imageHelper = $this->_objectManager->get('IWD\B2B\Helper\Images');
        if (isset($imageData['delete'])) {
            if (!empty($currentImage)) {
                $imageHelper->removeImage($currentImage);
                $imageFile = null;
            }
        } else {
            $imageFile = $imageHelper->uploadImage('image', 'company', true);
            if (empty($imageFile)) {
                $imageFile = $currentImage;
            }
        }

        return $imageFile;
    }

    /**
     * @param $certificateData
     * @param $certificateImage
     * @return null
     */
    private function uploadCertificate($certificateData, $certificateImage)
    {
        $imageFile = null;
        $imageHelper = $this->_objectManager->get('IWD\B2B\Helper\Images');

        // upload certificate
        if (isset($certificateData['delete'])) {
            if (!empty($certificateImage)) {
                $imageHelper->removeImage($certificateImage);
                $imageFile = null;
            }
        } else {
            $imageFile = $imageHelper->uploadImage('certificate', 'certificate');
            if (empty($imageFile)) {
                $imageFile = $certificateImage;
            }
        }

        return $imageFile;
    }

    /**
     * @param $data
     * @param $company
     */
    private function saveCompanyPayments($data, $company)
    {
        $resources = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resources->getConnection();

        $table = $resources->getTableName(\IWD\B2B\Model\ResourceModel\CompanyPayment::TBL_NAME);
        $where = ['comp_id = ?' => (int)$company];
        $connection->delete($table, $where);

        $payments = isset($data['payments']) ? $data['payments'] : [];
        if (empty($payments)) {
            $paymentData = ['comp_id' => $company, 'payment_code' => 'disallowall'];
            $connection->insert($table, $paymentData);
        } else {
            $paymentData = [];
            foreach ($payments as $payment_code) {
                $paymentData[] = ['comp_id' => $company, 'payment_code' => $payment_code];
            }
            $connection->insertMultiple($table, $paymentData);
        }
    }
 /**
     * @param $data
     * @param $company
     */
    private function saveCompanyShippings($data, $company)
    {
        $resources = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resources->getConnection();
        
        $table = $resources->getTableName(\IWD\B2B\Model\ResourceModel\CompanyShipping::TBL_NAME);
        $where = ['comp_id = ?' => (int)$company];
        $connection->delete($table, $where);
         // echo 'ddaaaddd='.$company;die;
        $shippings = isset($data['shippings']) ? $data['shippings'] : [];
		//echo '<pre>'; print_r($shippings);die;
        if (empty($shippings)) {
            $shipping_code = ['comp_id' => $company, 'shipping_code' => 'disallowall'];
            $connection->insert($table, $shipping_code);
        } else {
            $shippingData = [];
			//echo '<pre>'; print_r($shippings);die;
            foreach($shippings as $shipping_code) {
                $shippingData[] = ['comp_id' => $company, 'shipping_code' => $shipping_code];
            }
            $connection->insertMultiple($table, $shippingData);
        }
    }

    /**
     * @param $addErrorMessage
     * @param $originalData
     * @param $companyId
     * @return $this
     */
    private function errorRedirect($addErrorMessage, $originalData, $companyId)
    {
        // display error message
        $this->messageManager->addErrorMessage($addErrorMessage);

        // save data in session
        $session = $this->_objectManager->get('Magento\Backend\Model\Session');
        $session->setFormData($originalData);

        // redirect to edit form
        if (!empty($companyId)) {
            return $this->resultRedirect->setPath('*/*/edit', ['company_id' => $companyId]);
        }

        return $this->resultRedirect->setPath('*/*/new');
    }
}

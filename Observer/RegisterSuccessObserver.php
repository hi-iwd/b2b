<?php

namespace IWD\B2B\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\CustomerFactory;
use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory;
use IWD\B2B\Model\CompanyFactory as B2BCompanyFactory;

class RegisterSuccessObserver implements ObserverInterface
{    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    protected $_scopeConfig;
    
    protected $customerFactory;
    
    protected $_b2bCustomerFactory;
    
    protected $_b2bCompanyFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    
    
    /**
     * Filesystem facade
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;
    
    /**
     * File Uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;
    
    
    /**
     * IWD B2B
     *
     * @var \IWD\B2B\Helper\Data
     */
    protected $_helper;
    
    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * RegisterSuccessObserver constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param CustomerFactory $customerFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param \IWD\B2B\Helper\Data $b2bData
     * @param B2BCustomerFactory $b2bCustomerFactory
     * @param B2BCompanyFactory $b2bCompanyFactory
     * @param \Magento\Config\Model\ResourceModel\Config $configResource
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
            \Psr\Log\LoggerInterface $logger,
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            CustomerFactory $customerFactory,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Filesystem $filesystem,
            \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,            
            \IWD\B2B\Helper\Data $b2bData,
            B2BCustomerFactory $b2bCustomerFactory,
            B2BCompanyFactory $b2bCompanyFactory,
            \Magento\Config\Model\ResourceModel\Config $configResource,
            \Magento\Framework\App\RequestInterface $request
    ) {
        $this->logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_helper = $b2bData;
        $this->_b2bCustomerFactory = $b2bCustomerFactory;
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        $this->configResource  = $configResource;
        $this->request = $request;
    }
    
    /**
     * Apply additional information to user
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        // skip if action is not from b2b form
        $is_b2b = $observer->getIsbtb();
        if (!$is_b2b || empty($is_b2b) || $is_b2b != 'yes')
            return $this;
        
        $customer = $observer->getCustomer();
        
        $model = $this->customerFactory->create();
        $_customer = $model->load($customer->getId());
        
        $this->_setCustomerConfirmed($_customer);
        $this->_applyAdditionalData($_customer, $observer);
        
        return $this;
    }
    
    protected function _setCustomerConfirmed($customer)
    {
        if ($customer->isConfirmationRequired()) {
            $customer->setData('confirmation', '');
            try {
                $customer->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
    
    protected function _applyAdditionalData($customer, $observer)
    {
    
        //upload certificate
        $certificate = "";
        //icon
        $certificateFile = $this->request->getFiles('certificate');
        if (isset($certificateFile['tmp_name']) and (file_exists($certificateFile['tmp_name']))) {
            $path = $this->_filesystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath('b2b/certificate/');
            
            try {
                $uploader = $this->_fileUploaderFactory->create(['fileId' => 'certificate']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);

//                $destFile = $path . $_FILES['certificate']['name'];
//                $filename = $uploader->getNewFileName($destFile);
//                $result = $uploader->save($path, $filename);
                                
                $result = $uploader->save($path);

                $fileName = $uploader->getUploadedFileName();
                
                $certificate = 'b2b/certificate/'.$fileName;
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        $request = $observer->getRequest();
        
        //// clear flag
        $path = 'b2b/companies_assigned';
        $scope = 'default';
        $scopeId = 0;
        
        $table = $this->configResource->getMainTable();
        
        $connection = $this->configResource->getConnection();
        $select = $connection->select()
            ->from($table)
            ->where('path = ?',$path)
            ->where('scope = ?',$scope)
            ->where('scope_id = ?',$scopeId);
        $row = $connection->fetchRow($select);
        if ($row) {
            $this->configResource->saveConfig($path, false, $scope, $scopeId);
        }
        //////
        
        $add = $request->getParam('add', []);
        $add['customer_id'] = $customer->getId();
        
        $account = $request->getParam('account');
        $info = $request->getParam('info', []);
        
        // save store name as company name attribute
        $customer->setData('btb_active', '0');//custom activation for b2b
        $company_name = isset($info['store_name'])?$info['store_name']:'';
        $customer->setData('btb_company', $company_name);
        if (isset($info['ssn']) && !empty($info['ssn'])) {
            $customer->setData('taxvat', $info['ssn']);
        }
        try {
            $customer->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        ////
        
        $params = array_merge($add, $account);
        $params = array_merge($params, $info);
    
        $params['certificate'] = $certificate;
        $params['is_active'] = 2;

        $company_params = $params;
        
        $default_group = $this->_helper->getDefaultB2BGroup();
        $company_params['group_id'] = $default_group;
        
        try {
            $model = $this->_b2bCompanyFactory->create();
            $model->addData($company_params);
            $model->save();
            $company = $model->getId();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        $params['company_id'] = $company;
    
        try {
            $model = $this->_b2bCustomerFactory->create();
            $model->addData($params);
            $model->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}

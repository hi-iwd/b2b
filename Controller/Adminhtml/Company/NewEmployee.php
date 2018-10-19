<?php

namespace IWD\B2B\Controller\Adminhtml\Company;

use Magento\Customer\Api\AccountManagementInterface;

use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\InputException;
use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory; // we need to rename class name for b2b, because we also have
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\DataObject;
use Psr\Log\LoggerInterface as PsrLogger;
use IWD\B2B\Model\ResourceModel\Customer\Collection;

class NewEmployee extends \Magento\Backend\App\Action
{
    /** @var AccountManagementInterface */
    protected $accountManagement;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $b2bCustomerCollection;

    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \IWD\B2B\Model\CustomerFactory
     */
    protected $_b2bCustomerFactory;

    protected $inlineTranslation;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerDataFactory;

    /** @var CustomerRepositoryInterface */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Model\Customer\Mapper
     */
    protected $customerMapper;

    /** @var DataObjectHelper */
    protected $dataObjectHelper;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    protected $logger;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    protected $_helper_emails;

    /**
     * @var \IWD\B2B\Helper\Company
     */
    protected $_b2b_company_helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        CustomerInterfaceFactory $customerDataFactory,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        B2BCustomerFactory $b2b_customerFactory, // call DI object
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \IWD\B2B\Helper\Emails $helper_emails,
        \IWD\B2B\Helper\Company $b2b_company_helper,
        PsrLogger $logger,
        Collection $b2bCustomerCollection
    )
    {
        parent::__construct($context);

        $this->storeManager = $storeManager;
        $this->accountManagement = $accountManagement;
        $this->customerFactory = $customerFactory;
        $this->_customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerMapper = $customerMapper;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->_b2bCustomerFactory = $b2b_customerFactory;
        $this->mathRandom = $mathRandom;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->_helper_emails = $helper_emails;
        $this->_b2b_company_helper = $b2b_company_helper;
        $this->b2bCustomerCollection = $b2bCustomerCollection;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('IWD_B2B::b2b_company');
    }

    public function execute()
    {
        $response = new DataObject();

        $results = $this->_createItem();
        if ($results && isset($results['error'])) {
            $response->setData('error', true);
            $response->setData('messages', $results['error']);
        } else {
            $post = $this->_request->getParams();
            if (isset($post['continue_edit'])) {
                $url = $this->_backendUrl->getUrl('customer/index/edit', ['id' => $results, '_secure' => true]);
                $response->setData('location', $url);
            }

            $this->messageManager->addSuccess(__('Success - The user has been added to company.'));
        }

        $this->pushJson($response);
    }

    /**
     * Create B2B customer
     * @return multitype:\Magento\Framework\Phrase |boolean
     */
    private function _createItem()
    {
        $post = $this->_request->getParams();

        $company_id = $this->_session->getCurComp();

        if (empty($company_id)) {
            return ['error' => __('Wrong company.')];
        }

        $company = $this->_b2b_company_helper->getCompanyInfo($company_id);
        if (!$company->getId()) {
            return ['error' => __('Wrong company.')];
        }

        /// get company info and primary user details 
        $companyId = $company_id;
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

        $website_id = false;

        $parent_name = 'Admin';

        if ($parent_id) {
            $parent_customer = $this->_customerRepository->getById($parent_id);
            $website_id = $parent_customer->getWebsiteId();

            $parent_name = $parent_customer->getFirstname() . ' ' . $parent_customer->getLastname();
        }

        if (!$website_id) {
            $b2b_users = $this->_b2bCustomerFactory->create()->getCollection();
            $b2b_users->addFieldToFilter('company_id', $companyId);

            foreach ($b2b_users as $u) {
                $u_id = $u->getData('customer_id');

                $cust = $this->_customerRepository->getById($u_id);
                $website_id = $cust->getWebsiteId();

                if (!empty($website_id))
                    break;
            }
        }

        // get any website id
        if (!$website_id) {
            $sites = $this->storeManager->getWebsites();
            foreach ($sites as $site) {
                $website_id = $site->getId();
                break;
            }
        }

        // Get Website ID
        $websiteId = $website_id;

        // Instantiate object (this is the most important part)
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($post['email']);


        if (!$customer->getId()) {
            $password = $this->_rand_string(8);
            $password = 'abctest123321';
            // Preparing data for new customer
            $customer->setEmail($post['email']);
            $customer->setFirstname($post['firstname']);
            $customer->setLastname($post['lastname']);
            $customer->setPassword($password);
            $customer->setGroupId($group_id);

            // Save data
            try {
                $customer->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return ['error' => __('Can not create customer. ' . $e->getMessage())];
            }

            // Get the customer parent id
            $customer_id = $customer->getId();

            // now need to create record in b2b table and assign parent_id
            $b2b_model = $this->_b2bCustomerFactory->create();

            $params = ['parent_id' => $parent_id, 'customer_id' => $customer_id, 'company_id' => $companyId];

            // save role
            if (isset($post['role_id'])) {
                $params['role_id'] = $post['role_id'];
            }

            $b2b_model->addData($params);

            try {
                $b2b_model->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return ['error' => __('Something was wrong. ' . $e->getMessage())];
            }

            // this will update the btb_active since the system do not understand the b2b_active attributes
            $b2b_status = 1;
            if ($company->getData('is_active') != 1)
                $b2b_status = 0;
            $customerData = ['btb_active' => $b2b_status, 'confirmation' => NULL, 'btb_company' => $companyName];
            $companyUsers = $this->b2bCustomerCollection
                ->addFieldToFilter('customer_id', ['in' => $customer_id])
                ->getItems();
            foreach ($companyUsers as $companyUser) {
                $companyUser->setData('btb_active', $b2b_status);
                $companyUser->save();
            }
            $customerDataObject = $this->customerDataFactory->create();
            $savedCustomerData = $this->_customerRepository->getById($customer_id);
            $customerData = array_merge(
                $this->customerMapper->toFlatArray($savedCustomerData),
                $customerData
            );
            $customerData['id'] = $customer_id;

            $this->dataObjectHelper->populateWithArray(
                $customerDataObject,
                $customerData,
                '\Magento\Customer\Api\Data\CustomerInterface'
            );

            try {
                $this->_customerRepository->save($customerDataObject);
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return ['error' => __('Something was wrong. ' . $e->getMessage())];
            }

            // copy company data
            $success = $this->_b2b_company_helper->copyCompanyDataToCustomer($customer_id, $companyId, ['btb_active' => $b2b_status, 'log' => true]);
            if (!$success) {
                return ['error' => __('Something was wrong')];
            }

            // copy all address of master account
            if ($parent_id) {
                $parent_customer = $this->_customerRepository->getById($parent_id);
                $addresses = $parent_customer->getAddresses();

                foreach ($addresses as $p_address) {
                    $p_address->setId(false);
                    $customerAddress = $p_address;
                    $customerAddress->setCustomerId($customer_id);
                    $customerAddress->setFirstname($post['firstname']);
                    $customerAddress->setLastname($post['lastname']);
                    try {
                        $this->addressRepository->save($customerAddress);
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
            // end copy address

            // load customer
            $multiuser = $this->_customerRepository->getById($customer_id);

            $storeIds = $this->storeManager->getWebsite($websiteId)->getStoreIds();
            reset($storeIds);
            $storeId = current($storeIds);

            $store = $this->storeManager->getStore($storeId);

            $baseurl = $this->storeManager->getStore($storeId)->getBaseUrl();

            // generate reset password token for new user
            $newtoken = $this->generateResetPasswordLinkToken();

            // register token in spec table
            try {
                $this->accountManagement->changeResetPasswordLinkToken($multiuser, $newtoken);
            } catch (InputException $e) {
                $this->logger->critical($e);
            }
            // end token

            $postObject = new DataObject();
            $postObject->setData('store', $store);
            $postObject->setData('storename', $store->getFrontendName());
            $postObject->setData('customerid', $customer->getId());
            $postObject->setData('token', $newtoken);
            $postObject->setData('parent', $parent_name);
            $postObject->setData('email', $customer->getEmail());
            $postObject->setData('intlink', $baseurl);
            $postObject->setData('customer', $customer->getFirstname() . ' ' . $customer->getLastname());

            $customer = $this->_customerRepository->getById($customer->getId());

            $this->inlineTranslation->suspend();
            try {
                $this->_helper_emails->sendEmailTemplate(
                    $customer,
                    'b2b/emails/multiuser_template',
                    'contact/email/sender_email_identity',
                    ['data' => $postObject, 'store' => $store],
                    $storeId
                );

                $this->inlineTranslation->resume();

            } catch (MailException $e) {
                $this->inlineTranslation->resume();
                // If we are not able to send a new account email, this should be ignored
                $this->logger->critical($e);
            }
        } else {
            return ['error' => __('A customer with the same email already exists.')];
        }

        return $customer_id;
    }

    public function generateResetPasswordLinkToken()
    {
        return $this->mathRandom->getUniqueHash();
    }

    private function _rand_string($length)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }

    public function pushJson($response)
    {
        $jsonHelper = $this->_objectManager->get('Magento\Framework\Json\Helper\Data');
        $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
        return;
    }

}

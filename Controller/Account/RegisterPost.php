<?php

namespace IWD\B2B\Controller\Account;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\UrlFactory;
use Magento\Customer\Model\Metadata\FormFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\Registration;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Math\Random;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Psr\Log\LoggerInterface as PsrLogger;
use IWD\B2B\Controller\AbstractController;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\State\InputMismatchException;

class RegisterPost extends AbstractController
{
    /** @var AccountManagementInterface */
    protected $accountManagement;

    /** @var FormFactory */
    protected $formFactory;

    /** @var RegionInterfaceFactory */
    protected $regionDataFactory;

    /** @var AddressInterfaceFactory */
    protected $addressDataFactory;

    /** @var Registration */
    protected $registration;

    /** @var CustomerInterfaceFactory */
    protected $customerDataFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /** @var Escaper */
    protected $escaper;

    /** @var \Magento\Framework\UrlInterface */
    protected $urlModel;

    /** @var \Magento\Backend\Model\UrlInterface */
    protected $backendUrl;

    /** @var DataObjectHelper  */
    protected $dataObjectHelper;

    /**
     * @var Session
     */
    protected $session;

    protected $_helper;

    protected $_helper_emails;

    protected $_addressRepository;

    protected $_userFactory;

    protected $inlineTranslation;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var Encryptor
     */
    private $encryptor;

    protected $logger;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var \IWD\B2B\Model\CompanyFactory
     */
    protected $_b2bCompanyFactory;

    /**
      * @var \Magento\Payment\Helper\Data
      */
     protected $paymentHelper;

     /**
      * @var \IWD\B2B\Model\CompanyPayment
      */
     protected $_b2bCompanyPaymentModel;


    /**
     * @param Context $context
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param AccountManagementInterface $accountManagement
     * @param UrlFactory $urlFactory
     * @param FormFactory $formFactory
     * @param RegionInterfaceFactory $regionDataFactory
     * @param AddressInterfaceFactory $addressDataFactory
     * @param CustomerInterfaceFactory $customerDataFactory
     * @param Registration $registration
     * @param Escaper $escaper
     * @param DataObjectHelper $dataObjectHelper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $accountManagement,
        CustomerRepositoryInterface $customerRepository,
        CustomerViewHelper $customerViewHelper,
        UrlFactory $urlFactory,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        FormFactory $formFactory,
        RegionInterfaceFactory $regionDataFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        AddressInterfaceFactory $addressDataFactory,
        CustomerInterfaceFactory $customerDataFactory,
        Registration $registration,
        Escaper $escaper,
        \Magento\User\Model\UserFactory $userFactory,
        DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        Random $mathRandom,
        Encryptor $encryptor,
        PsrLogger $logger,
        \IWD\B2B\Helper\Data $helper,
        \IWD\B2B\Helper\Emails $helper_emails,
        \Magento\Framework\Registry $registry,
        \IWD\B2B\Model\CompanyFactory $b2bCompanyFactory,
        \Magento\Payment\Helper\Data $paymentHelper,
        \IWD\B2B\Model\CompanyPayment $b2bCompanyPaymentModel
    ) {
        $this->session = $customerSession;
        $this->storeManager = $storeManager;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
        $this->customerViewHelper = $customerViewHelper;
        $this->formFactory = $formFactory;
        $this->regionDataFactory = $regionDataFactory;
        $this->_addressRepository = $addressRepository;
        $this->addressDataFactory = $addressDataFactory;
        $this->customerDataFactory = $customerDataFactory;
        $this->registration = $registration;
        $this->escaper = $escaper;
        $this->urlModel = $urlFactory->create();
        $this->backendUrl = $backendUrl;
        $this->_userFactory = $userFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->mathRandom = $mathRandom;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->_helper = $helper;
        $this->_helper_emails = $helper_emails;
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        $this->paymentHelper = $paymentHelper;
        $this->_b2bCompanyPaymentModel = $b2bCompanyPaymentModel;

        parent::__construct($context, $registry);
    }

    /**
     * Add address to customer during create account
     *
     * @return AddressInterface|null
     */
    protected function extractAddress($params, $_customer = null)
    {
        $addressForm = $this->formFactory->create('customer_address', 'customer_register_address');
        $allowedAttributes = $addressForm->getAllowedAttributes();

        $addressData = [];

        $regionDataObject = $this->regionDataFactory->create();
        foreach ($allowedAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $value = isset($params[$attributeCode])?$params[$attributeCode]:null;
            if ($value === null) {
                continue;
            }
            switch ($attributeCode) {
                case 'region_id':
                    $regionDataObject->setRegionId($value);
                    break;
                case 'region':
                    $regionDataObject->setRegion($value);
                    break;
                default:
                    $addressData[$attributeCode] = $value;
            }
        }
        $addressDataObject = $this->addressDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
                $addressDataObject,
                $addressData,
                '\Magento\Customer\Api\Data\AddressInterface'
        );
        $addressDataObject->setRegion($regionDataObject);

        if ($_customer)
            $addressDataObject->setCustomerId($_customer->getId());

        if (isset($params['default_billing']))
            $addressDataObject->setIsDefaultBilling($params['default_billing']);
        if (isset($params['default_shipping']))
            $addressDataObject->setIsDefaultShipping($params['default_shipping']);

        return $addressDataObject;
    }


    public function extractCustomer($params)
    {
        $customerForm = $this->formFactory->create('customer', 'customer_account_create');
        $allowedAttributes = $customerForm->getAllowedAttributes();

        $customerData = [];

        foreach ($allowedAttributes as $attribute) {
            $attributeCode = $attribute->getAttributeCode();
            $value = isset($params[$attributeCode])?$params[$attributeCode]:null;
            if ($value === null) {
                continue;
            }
            $customerData[$attributeCode] = $value;
        }

        $customerDataObject = $this->customerDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
                $customerDataObject,
                $customerData,
                '\Magento\Customer\Api\Data\CustomerInterface'
        );
        $store = $this->storeManager->getStore();

        $default_group = $this->_helper->getDefaultB2BGroup();
        $customerDataObject->setGroupId($default_group);

        $customerDataObject->setWebsiteId($store->getWebsiteId());
        $customerDataObject->setStoreId($store->getId());

        return $customerDataObject;
    }

    /**
     * Create customer account action
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->session->isLoggedIn() || !$this->registration->isAllowed()) {
            $resultRedirect->setPath('*/*/');
            return $resultRedirect;
        }

        if (!$this->getRequest()->isPost()) {
            $url = $this->urlModel->getUrl('*/*/register', ['_secure' => true]);
            $resultRedirect->setUrl($this->_redirect->error($url));
            return $resultRedirect;
        }


        $shipping = $this->getRequest()->getParam('shipping', []);
        $billing = $this->getRequest()->getParam('billing', []);
        $address = $this-> getRequest()->getParam('address', []);
        $account = $this->getRequest()->getParam('account', []);
        $info = $this->getRequest()->getParam('info', []);
        $add = $this->getRequest()->getParam('add', []);

        $this->session->regenerateId();

        // check if company name is unique
        $company_exists = false;
        try {
            $collection = $this->_b2bCompanyFactory->create()->getCollection();
            $collection->addFieldToFilter('store_name', ['like'=> $info['store_name']]);

            foreach ($collection as $col) {
                $company_exists = true;
                break;
            }
        } catch (\Exception $e) {
            $this->_helper_messages->addError($this->escaper->escapeHtml($e->getMessage()));
        }

        ///
        // if ($company_exists) {
        //     $msg = __('A company with this name already exists');
        //     $this->_helper_messages->addError($msg);
        // } else {
        try {
            $account_address = $this->extractAddress($account);
            $addresses = $account_address === null ? [] : [$account_address];

            $customer = $this->extractCustomer($account);

            $password = $account['password'];
            $confirmation = $password;

            $customer = $this->createAccount($customer, $password);

            //////////  create addresses
            if ($billing['same_as_shipping']==1) {
                $setAsBilling = 1;
            } else {
                $setAsBilling = 0;
            }

            // prepare shipping address data
            $shipping_params = array_merge($shipping, $account);
            $shipping_params['default_shipping'] = true;
            if ($setAsBilling)
                $shipping_params['default_billing'] = true;

            $ship_address = $this->extractAddress($shipping_params, $customer);

            ///// try to add address
            $this->_addressRepository->save($ship_address);

            if (!$setAsBilling) {
                // prepare billing address data
                $billing_params = array_merge($billing, $account);
                $billing_params['default_shipping'] = false;
                $billing_params['default_billing'] = true;

                $bill_address = $this->extractAddress($billing_params, $customer);

                ///// try to add address
                $this->_addressRepository->save($bill_address);
            }
            ///////////////////////

            $this->_eventManager->dispatch(
                'customer_register_success',
                ['account_controller' => $this, 'customer' => $customer, 'request' => $this->getRequest(), 'isbtb' => 'yes']
            );

            // Select All Payment Methods by Default
            ////////////////////////////////////////
            $compCollection = $this->_b2bCompanyFactory->create()->getCollection();
            $compCollection = $compCollection->addFieldToFilter('email', ['eq' => $account['email']]);
            $comp_id = $compCollection->getColumnValues('company_id');
            $comp_id = $comp_id[0];

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $model = $objectManager->create('\IWD\B2B\Model\CompanyPayment');
            foreach ($this->paymentHelper->getPaymentMethods() as $code => $data) {
                $title = '';
                if (isset($data['title'])) {
                    $title = $data['title'];
                }
                if (!empty($title)) {
                    $active = $this->paymentHelper->getMethodInstance($code)->isActive(null);
                    if ($active) {
                        $model->setData('comp_id', $comp_id);
                        $model->setData('payment_code', $code);
                        $model->save();
                        $model->unsetData();
                    }
                }
            }
            ////////////////////////////////////////

            // send emails
            $url = $this->_welcomeCustomer($customer, $this->getRequest());

            $success_message = $this->_helper->getText('register_form/success');

            $this->_helper_messages->addSuccess($success_message);

            // @codingStandardsIgnoreEnd
            $url = $this->urlModel->getUrl('*/*/register', ['_secure' => true]);
            $resultRedirect->setUrl($this->_redirect->success($url));

            return $resultRedirect;

        } catch (StateException $e) {

            $message = $this->escaper->escapeHtml($e->getMessage());

            $this->_helper_messages->addError($message);

        } catch (InputException $e) {

            $this->_helper_messages->addError($this->escaper->escapeHtml($e->getMessage()));
            foreach ($e->getErrors() as $error) {
                $this->_helper_messages->addError($this->escaper->escapeHtml($error->getMessage()));
            }

        } catch (InputMismatchException $e) {
            $this->_helper_messages->addError($this->escaper->escapeHtml($e->getMessage()));
        } catch (\Exception $e) {
            $this->_helper_messages->addError($this->escaper->escapeHtml($e->getMessage()));
//            $this->_helper_messages->addException($e, __('We can\'t save the customer.'));
        }
    // }

        $this->session->setCustomerFormData($this->getRequest()->getPostValue());
        $defaultUrl = $this->urlModel->getUrl('*/*/register', ['_secure' => true]);
        $resultRedirect->setUrl($this->_redirect->error($defaultUrl));
        return $resultRedirect;
    }


    /**
     * {@inheritdoc}
     */
    public function createAccount(CustomerInterface $customer, $password = null)
    {
        if ($password !== null) {
            $hash = $this->encryptor->getHash($password, true);
        } else {
            $hash = null;
        }
        return $this->createAccountWithPasswordHash($customer, $hash);
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function createAccountWithPasswordHash(CustomerInterface $customer, $hash)
    {
        // This logic allows an existing customer to be added to a different store.  No new account is created.
        // The plan is to move this logic into a new method called something like 'registerAccountWithStore'
        if ($customer->getId()) {
            $customer = $this->customerRepository->get($customer->getEmail());
            $websiteId = $customer->getWebsiteId();

            if ($this->accountManagement->isCustomerInStore($websiteId, $customer->getStoreId())) {
                throw new InputException(__('This customer already exists in this store.'));
            }
            // Existing password hash will be used from secured customer data registry when saving customer
        }
        // Make sure we have a storeId to associate this customer with.
        if (!$customer->getStoreId()) {
            if ($customer->getWebsiteId()) {
                $storeId = $this->storeManager->getWebsite($customer->getWebsiteId())->getDefaultStore()->getId();
            } else {
                $storeId = $this->storeManager->getStore()->getId();
            }

            $customer->setStoreId($storeId);
        }

        // Update 'created_in' value with actual store name
        if ($customer->getId() === null) {
            $storeName = $this->storeManager->getStore($customer->getStoreId())
            ->getName();
            $customer->setCreatedIn($storeName);
        }

        $customer->setAddresses(null);
        try {
            // If customer exists existing hash will be used by Repository
            $customer = $this->customerRepository->save($customer, $hash);
        } catch (AlreadyExistsException $e) {
            throw new \Exception(
                __("An account with this email address already exists. Please either login to your account or complete the registration form with a different email address.")
            );
        } catch (LocalizedException $e) {
            throw $e;
        }

        $customer = $this->customerRepository->getById($customer->getId());
        $newLinkToken = $this->mathRandom->getUniqueHash();
        $this->accountManagement->changeResetPasswordLinkToken($customer, $newLinkToken);

        return $customer;
    }

    protected function _welcomeCustomer($_customer, $request) {

        $this->_notifyAdmin($_customer, $request);

        // load customer
        $customer = $this->customerRepository->getById($_customer->getId());

        $storeId = $this->storeManager->getStore()->getId();
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer);
        }
        $store = $this->storeManager->getStore($storeId);


        $info = $request->getParam('info');
        $postObject = new DataObject();
        $postObject->setData('storename', $store->getName());
        $postObject->setData('customer', $_customer->getFirstname().' '.$_customer->getLastname());

        $this->inlineTranslation->suspend();
        try {
            $this->_helper_emails->sendEmailTemplate(
                    $customer,
                    'b2b/emails/customer_recieved_template',
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

    }

    protected function _notifyAdmin($_customer, $request) {

        // load customer
        $customer = $this->customerRepository->getById($_customer->getId());

        $storeId = $this->storeManager->getStore()->getId();
        if (!$storeId) {
            $storeId = $this->getWebsiteStoreId($customer);
        }
        $store = $this->storeManager->getStore($storeId);

        $emails = $this->_getAdminsEmails();

        $id = $this->getRequest()->getParam('company_id');
        $model = $this->_b2bCompanyFactory->create()->load($id);

        $url  = $this->backendUrl->getUrl('b2b/company/index/edit', ['id' => $model->getId(), '_secure' => true]);

        $info = $request->getParam('info');

        $postObject = new DataObject();
        $postObject->setData('url', $url);
        $postObject->setData('storename', $info['store_name']);

        foreach ($emails as $email) {

            $this->inlineTranslation->suspend();
            try {
                $this->_helper_emails->sendEmailTemplate(
                            $email,
                            'b2b/emails/recieved_template',
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
        }

    }

    /**
     * Get either first store ID from a set website or the provided as default
     *
     * @param CustomerInterface $customer
     * @param int|string|null $defaultStoreId
     * @return int
     */
    protected function getWebsiteStoreId($customer, $defaultStoreId = null)
    {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            reset($storeIds);
            $defaultStoreId = current($storeIds);
        }
        return $defaultStoreId;
    }

    protected function _getAdminsEmails() {
        $emails = [];

        $ids = $this->_helper->getConfig('b2b/emails/admins');
        $ids = explode(',', $ids);
        foreach ($ids as $id) {
            if (!empty($id)) {

                $user = $this->_userFactory->create();
                $user->load($id);
                if ($user->getId()) {
                    $emails[] = $user->getEmail();
                }
            }
        }

        return $emails;
    }

}

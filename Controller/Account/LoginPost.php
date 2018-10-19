<?php

namespace IWD\B2B\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Exception\EmailNotConfirmedException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\DataObject;
use IWD\B2B\Controller\AbstractAccount;

class LoginPost extends AbstractAccount
{
    /** @var AccountManagementInterface */
    protected $customerAccountManagement;

    /**
     * @var Session
     */
    protected $session;

    protected $_helper;
    
    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerUrl $customerHelperData
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        CustomerUrl $customerHelperData,
        \IWD\B2B\Helper\Data $helper
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerUrl = $customerHelperData;
        $this->_helper = $helper;
        parent::__construct($context);
    }

    /**
     * Login post action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        $jsonHelper = $this->_objectManager->get('Magento\Framework\Json\Helper\Data');
        
        $response = new DataObject();
        
        $redirectUrl = $this->session->getBeforeAuthUrl(true);
        if (empty($redirectUrl))
            $redirectUrl = $this->_helper->getRedirectAfterLoginUrl();
        
        if ($this->session->isLoggedIn()) {
            $err = $this->_helper->checkB2BCustomerAccess();
            if ($err && is_array($err)) { // has no access
                // continue with login form
            }
            else{
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($redirectUrl);
                return $resultRedirect;
            }
        }

        $error_message = false;

        if ($this->getRequest()->isPost()) {
            $login = $this->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {

                try {
                    $customer = $this->customerAccountManagement->authenticate($login['username'], $login['password']);

                    $this->session->setCustomerDataAsLoggedIn($customer);
                    $this->session->regenerateId();
                    
                    $err = $this->_helper->checkB2BCustomerAccess();
                    if ($err && is_array($err)) {
                        $this->session->logout();
                        $this->session->setCustomerId(false);
                        
                        if (isset($err['error']))
                            $error_message = $err['error'];
                        else
                            $error_message = __('You do not have access to this page');
                    }
                    else {
                        $response->setData('linkAfterLogin', $redirectUrl);
                        //TODO ADD LOGIC TO CLEAR QUOTE AFTER LOGIN
                        $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
                        return;
                    }
                    
                } catch (EmailNotConfirmedException $e) {
                    $value = $this->customerUrl->getEmailConfirmationUrl($login['username']);
                    $message = __(
                        'This account is not confirmed.' .
                        ' <a href="%1">Click here</a> to resend confirmation email.',
                        $value
                    );

                    $this->session->setUsername($login['username']);
                    
                    $store_name = $this->helper->getStoreName();
                    $msg = __('Thank you for registering with %s.', $store_name);
                    
                    $response->setData('message', $msg);
                    $response->setData('linkAfterLogin', $redirectUrl);
                    
                    $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
                    return;
                    
                } catch (AuthenticationException $e) {
                    $this->session->setUsername($login['username']);
                    
                    $error_message = __('Invalid login or password.');
                    
                } catch (\Exception $e) {

                    $this->session->setUsername($login['username']);
                    
                    $error_message = __('Invalid login or password.');
                }
                
            } else {
                $error_message = __('Login and password are required.');
            }
        }
        else {
            $error_message = __('Login and password are required.');
        }

        if ($error_message) {
            $response->setData('error', true);
            $response->setData('message', $error_message);
            $response->setData('error_message', $error_message);
        }
        
        $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
        return;
    }
}

<?php
namespace IWD\B2B\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\AccountManagement;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\DataObject;
use IWD\B2B\Controller\AbstractAccount;

class ForgotPasswordPost extends AbstractAccount
{
    /** @var AccountManagementInterface */
    protected $customerAccountManagement;

    /** @var Escaper */
    protected $escaper;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper $escaper
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper
    ) {
        $this->session = $customerSession;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->escaper = $escaper;
        parent::__construct($context);
    }

    /**
     * Forgot customer password action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $jsonHelper = $this->_objectManager->get('Magento\Framework\Json\Helper\Data');
        
        $response = new DataObject();
        
        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!\Zend_Validate::is($email, 'EmailAddress')) {
                $this->session->setForgottenEmail($email);
                
                $response->setData('error', true);
                $response->setData('message', __('Please correct the email address.'));
                $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
                return;
            }
            
            try {
                $this->customerAccountManagement->initiatePasswordReset(
                    $email,
                    AccountManagement::EMAIL_RESET
                );
            } catch (NoSuchEntityException $e) {
                // Do nothing, we don't want anyone to use this action to determine which email accounts are registered.
            } catch (SecurityViolationException $exception) {
                $err = $exception->getMessage();
                $response->setData('error', true);
                $response->setData('message', $err);
                $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
                return;
            } catch (\Exception $exception) {
                $err = __('We are unable to send the password reset email.');
                $response->setData('error', true);
                $response->setData('message', $err);
                $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
                return;
            }
            $msg = $this->getSuccessMessage($email);
            $response->setData('link', $msg);            
        } else {
            $response->setData('error', true);
            $response->setData('message', __('Please enter your email.'));
        }
         
        $this->getResponse()->setBody($jsonHelper->jsonEncode($response));
        return;
    }

    /**
     * Retrieve success message
     *
     * @param string $email
     * @return \Magento\Framework\Phrase
     */
    protected function getSuccessMessage($email)
    {
        return __(
            'If there is an account associated with %1 you will receive an email with a link to reset your password.',
            $this->escaper->escapeHtml($email)
        );
    }
}

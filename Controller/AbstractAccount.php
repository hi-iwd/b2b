<?php

namespace IWD\B2B\Controller;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Controller\ResultFactory;

abstract class AbstractAccount extends \Magento\Customer\Controller\AbstractAccount
{
    protected $_b2b_helper;
    
    protected $_b2b_storeManager;
    
    protected $_helper_messages;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \IWD\B2B\Helper\Data $helper
     */
    public function __construct(
            \Magento\Framework\App\Action\Context $context
    ) {
        parent::__construct($context);
        
        $manager = $this->_objectManager;
        $this->_b2b_helper  = $manager->create('IWD\B2B\Helper\Data');
        $this->_helper_messages  = $manager->create('IWD\B2B\Helper\Messages');
        $this->_b2b_storeManager = $manager->create('\Magento\Store\Model\StoreManagerInterface');
    }
    
    
    /**
     * Dispatch request
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */    
    public function dispatch(RequestInterface $request)
    {        
        $moduleName = $request->getModuleName();
        $controller = $request->getControllerName();
        $action     = $request->getActionName();
        $fullAction = $request->getFullActionName();

        $helper = $this->_b2b_helper;
        
        $access_denied = false;
        $error_msg = false;

        if (!$helper->isEnable()) {
            $access_denied = true;
        }
        else {
            $allow_actions = [
                'b2b_account_login',
                'b2b_account_loginPost',
                'b2b_account_ForgotPasswordPost',
                'b2b_account_register',
                'b2b_account_registerPost',
                'b2b_account_successPage','b2b_account_notactive'
            ];            
            if (in_array($fullAction, $allow_actions)) {
                return parent::dispatch($request);
            }
        }
        
        if (!$helper->isLoggedIn()) {
            $access_denied = true;
        }
        else {
            $check_access = $helper->checkB2BCustomerAccess();
            if ($check_access && is_array($check_access)) {
                $access_denied = true;
                
                if (isset($check_access['error']))
                    $error_msg = $check_access['error'];
                else
                    $error_msg = __('You do not have access to this page');
            }
        }
        
        if ($access_denied) {
            $_secure = $this->_b2b_helper->isSecure();
            
            $base_url = $this->_b2b_helper->getConfig('b2b/access_login_form/redirect');
            if (empty($base_url)) {
                $base_url = $this->_b2b_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, $_secure);
                $base_url = $base_url.'b2b/account/login';
            }
            $fl = substr($base_url, 0, 1);
            if ($fl == '/') {
                if (strlen($base_url) > 1) {
                    $base_url = substr($base_url, 1);
                }
            }
            
            if ($this->getRequest()->isAjax()) {
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                if (!empty($error_msg))
                    $resultJson->setData(['location' => $base_url, 'error' => 1, 'message' => $error_msg]);
                else
                    $resultJson->setData(['location' => $base_url]);
                
                return $resultJson;
            }
            else {
                if (empty($error_msg)) {
                    $check_access = $helper->checkB2BCustomerAccess();
                    if ($check_access && is_array($check_access)) {
                        if (isset($check_access['error']))
                            $error_msg = $check_access['error'];
                        else
                            $error_msg = __('You do not have access to this page');
                    }                    
                }
                
                if (!empty($error_msg)){
                    $this->messageManager->addError($error_msg);
                    $this->_helper_messages->addError($error_msg);
                }
                return $this->_redirect($base_url);
            }
        }
        
        return parent::dispatch($request);
    }
    
}

<?php

namespace IWD\B2B\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class CheckB2BObserver
 * @package IWD\B2B\Observer
 */
class CheckB2BObserver implements ObserverInterface
{
    /**
     * \Magento\Framework\App\ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * Product SKU
     * @var string
     */
    private $extensionCode = 'M2_B2B-Community';

    /**
     * @var \Magento\Framework\Url
     */
    private $url;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CustomerModelSession
     */
    private $customerSession;

    /**
     * @var
     */
    private $request;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Store\App\Response\Redirect
     */
    private $responseRedirect;

    /**
     * IWD B2B
     *
     * @var \IWD\B2B\Helper\Data
     */
    private $helper;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\Framework\Url $url,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CustomerModelSession $customerSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\App\Response\Redirect $responseRedirect,
        \IWD\B2B\Helper\Data $b2bData,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectManager
    ) {
        $this->url = $url;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->helper = $b2bData;
        $this->productMetadata = $productMetadata;
        $this->responseRedirect = $responseRedirect;
        $this->objectManager = $objectManager;

        $this->setExtensionCode();
    }

    /**
     * Apply customized static files to frontend
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->checkB2B($observer);
    }

    /**
     * @return string
     */
    public function getMagentoVersion()
    {
        $version = $this->productMetadata->getVersion();
        return $version;
    }

    /**
     * @return string
     */
    public function getMagentoEdition()
    {
        $edition = $this->productMetadata->getEdition();
        return $edition;
    }

    private function setExtensionCode()
    {
        $me = $this->getMagentoEdition();
        if ($me == 'Enterprise') {
            $this->extensionCode = 'M2_B2B-Enterprise';
        }
    }

    private function _getSession()
    {
        return $this->customerSession;
    }

    public function checkForgotPasswordRedirect($observer)
    {
        $request = $this->request;

        $fullAction = $request->getFullActionName();

        $current_url = $this->url->getCurrentUrl();

        $params = $request->getParams();

        $ref = $this->responseRedirect->getRefererUrl();

        // if we post new password, need to save user id for next step
        if ($fullAction == 'customer_account_resetpasswordpost') {
            // save user id
            $user_id = isset($params['id']) ? $params['id'] : false;
            if ($user_id) {
                $this->customerSession->setRPUI($user_id);
            } else {
                $this->customerSession->unsRPUI();
            }
        }

        // if is magento login page, need to check if previous page was reset password
        if ($fullAction == 'customer_account_login') {
            // check if prev page was reset pass

            if (preg_match('/customer\/account\/createpassword/', $ref)) {
                $uid = $this->customerSession->getRPUI();

                if ($uid) {
                    // check if b2b user
                    $model = $this->helper->isB2BUser($uid);
                    if ($model) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function checkOrderViewRedirect($observer)
    {
        if ($this->helper->isLoggedIn()) {
            return false;
        }

        $request = $this->request;

        $fullAction = $request->getFullActionName();

        $currentUrl = $this->url->getCurrentUrl();

        $params = $request->getParams();

        // if we post new password, need to save user id for next step
        if ($fullAction == 'sales_order_view') {
            $order_id = isset($params['order_id']) ? $params['order_id'] : false;
            if ($order_id) { // check if this is b2b order
                $is_b2b = $this->helper->isB2BOrder($order_id);
                if ($is_b2b) {
                    $this->_getSession()->setBeforeAuthUrl($currentUrl);
                    return true;
                }
            }
        }

        return false;
    }

    public function checkB2B($observer, $mode = false)
    {
        if (!$mode) {
            $controller_object = $observer->getControllerAction();
            $request = $observer->getControllerAction()->getRequest();
            $this->request = $request;

            $redirect_to_login = $this->checkForgotPasswordRedirect($observer);

            $params = $request->getParams();

            $aac = $request->getParam('aac', false);
            if ($aac !== false) {
                return;
            }

            $moduleName = $request->getModuleName();
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            $fullAction = $request->getFullActionName();

            $current_url = $this->url->getCurrentUrl();
            $url = $this->scopeConfig->getValue('b2b/access_login_form/redirect', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $session = $this->_getSession();
        }

        $store = $this->storeManager->getStore();
        $store_id = $store->getStoreId();
        $base_url = $store->getBaseUrl();

        if (!$mode) {
            if (!$this->helper->isEnable()) {
                if ($moduleName == 'b2b') {
                    // need redirect to homepage
                    if ($controller_object->getRequest()->isAjax()) {
                    } else {
                        $controller_object->getResponse()->setRedirect($base_url);
                    }
                    return;
                }

                return $this;
            }
        }
        $is_allowed = true;

        if ($mode) {
            return $is_allowed;
        }

        if ($moduleName == 'b2b' && $fullAction != 'b2b_account_notactive') {
            if (!$is_allowed) {
                // need redirect to homepage
                if ($controller_object->getRequest()->isAjax()) {

                } else {
                    $url = $this->url->getUrl('b2b/account/notactive');
                    $controller_object->getResponse()->setRedirect($url);
                }
                return;
            }
        }
        /////

        // check if standard login registration are disabled
        if ($fullAction == 'customer_account_login') {
            if ($is_allowed) { // allow rewrite login for active license
                $disabled = $this->helper->standardLoginDisabled();
                if ($disabled) {
                    $url = $this->url->getUrl('b2b/account/login');
                    $controller_object->getResponse()->setRedirect($url);
                    return $this;
                }
            }
        }

        if ($fullAction == 'customer_account_create') {
            if ($is_allowed) { // allow rewrite registration for active license
                $disabled = $this->helper->standardLoginDisabled();
                if ($disabled) {
                    $url = $this->url->getUrl('b2b/account/register');
                    $controller_object->getResponse()->setRedirect($url);
                    return $this;
                }
            }
        }
        /////

        // check if need redirect to b2b login to review order
        if (!$redirect_to_login) {
            $redirect_to_login = $this->checkOrderViewRedirect($observer);
        }
        ///

        // if need redirect b2b user from reset password to b2b login page
        if ($redirect_to_login) {
            $url = $this->url->getUrl('b2b/account/login');
            $controller_object->getResponse()->setRedirect($url);

            return $this;
        }
        /////

        $is_logged = false;
        if (!$session->isLoggedIn()) {
            // check url is in restrict
            $is_in_restrict = false;

            $restrict = $this->scopeConfig->getValue('b2b/access_login_form/restrict_access', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if (!$restrict) {
                $is_in_restrict = false;
            } else {
                $restrictArea = $this->scopeConfig->getValue('b2b/access_login_form/restrict_segment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
                $restrictArea = explode(',', $restrictArea);

                $end_ckeck = false;

                $_exc = ['b2b_account_register', 'b2b_account_notactive'];
                if (in_array($fullAction, $_exc)) {
                    $is_in_restrict = false;
                    $end_ckeck = true;
                }

                if (!$end_ckeck) {
                    if ($moduleName == 'cms') {
                        if (in_array($fullAction, $restrictArea)) {
                            $is_in_restrict = true;
                            $end_ckeck = true;
                        }
                    }
                }

                if (!$end_ckeck) {
                    if ($moduleName == 'cms') {
                        if ($fullAction == 'cms_page_view' && in_array('static', $restrictArea)) {
                            $is_in_restrict = true;
                            $end_ckeck = true;
                        }
                    }
                }

                if (!$end_ckeck) {
                    if ($moduleName == 'catalog') {
                        if ($fullAction == 'catalog_category_view' && in_array('category', $restrictArea)) {
                            $is_in_restrict = true;
                            $end_ckeck = true;
                        }
                    }
                }

                if (!$end_ckeck) {
                    if ($moduleName == 'catalog') {
                        if ($fullAction == 'catalog_product_view' && in_array('product', $restrictArea)) {
                            $is_in_restrict = true;
                            $end_ckeck = true;
                        }
                    }
                }

                if (!$end_ckeck) {
                    // additional rules for some pages
                    if ($moduleName == 'catalogsearch' && in_array('category', $restrictArea)) {
                        $is_in_restrict = true;
                        $end_ckeck = true;
                    }
                }

                if (!$end_ckeck) {
                    if (in_array($fullAction, ['search_term_popular', 'contact_index_index', 'sales_guest_form'])) {
                        $is_in_restrict = true;
                        $end_ckeck = true;
                    }
                }
                /////

                if (!$end_ckeck) {
                    $is_in_restrict = false;
                }
            }
            //

            if ($is_in_restrict) {
                $url = $this->url->getUrl('b2b/account/login');
                $controller_object->getResponse()->setRedirect($url);

                return $this;
            }
        } else {
            $is_logged = true;
        }

        // check urls only
        $url1 = $url;
        $url1 = str_replace('https://', '', $url1);
        $url1 = str_replace('http://', '', $url1);
        $url1 = str_replace('www.', '', $url1);

        $url2 = $current_url;
        $url2 = str_replace('https://', '', $url2);
        $url2 = str_replace('http://', '', $url2);
        $url2 = str_replace('www.', '', $url2);

        $fc = substr($url, 0, 1);
        if ($fc == '/') {
            $p = strpos($url2, '/');
            if ($p !== false) {
                $url2 = substr($url2, $p);
            }
        }

        if ($moduleName != 'b2b' && $url1 == $url2) {
            return $this;
        }
        ///

        $allow_actions = [
            'b2b_account_login',
            'b2b_account_loginPost',
            'b2b_account_ForgotPasswordPost',
            'b2b_account_register',
            'b2b_account_registerPost',
            'b2b_account_successPage',
            'b2b_cart_index',
            'b2b_account_notactive'
        ];

        if ($moduleName != 'b2b' || (!$is_logged && in_array($fullAction, $allow_actions))) {
            return $this;
        }

        $_customer = $session->getCustomer();

        $err = $this->helper->checkB2BCustomerAccess();
        if ($err && is_array($err)) {
            if ($is_logged && in_array($fullAction, ['b2b_account_loginPost'])) {
                return $this;
            }
            if ($is_logged && in_array($fullAction, ['b2b_account_login'])) {
                $url = $base_url; // logged in user without access to b2b should be redirected to homepage
            }

            if (isset($err['error'])) {
                $error_msg = $err['error'];
            } else {
                $error_msg = __('You do not have access to this page');
            }

            $this->messageManager->addNoticeMessage($error_msg);

            $helperMessages = $this->objectManager->create('IWD\B2B\Helper\Messages');
            $helperMessages->addNotice($error_msg);
            $controller_object->getResponse()->setRedirect($url);
            return $this;
        }

        return $this;
    }
}

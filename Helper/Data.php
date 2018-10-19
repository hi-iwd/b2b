<?php

namespace IWD\B2B\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\DataObject;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Helper\Data as ChData;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\App\Helper\AbstractHelper;
use IWD\B2B\Model\ResourceModel\Message\CollectionFactory as B2BMessageFactory;
use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory;
use IWD\B2B\Model\CompanyFactory as B2BCompanyFactory;

/**
 * Class Data
 * @package IWD\B2B\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_ENABLE = 'b2b/default/status';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerModelSession
     */
    protected $customerSession;

    /**
     * @var GroupManagementInterface
     */
    protected $customerGroupManagement;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var B2BMessageFactory
     */
    protected $_b2bMessageFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Msrp\Model\Config
     */
    protected $msrp_config;

    /**
     * @var \Magento\Msrp\Model\Quote\Msrp
     */
    protected $msrp;

    /**
     * @var CustomerCart
     */
    protected $cart;

    /**
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var B2BCustomerFactory
     */
    protected $_b2bCustomerFactory;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \IWD\B2B\Model\CompanyFactory
     */
    protected $_b2bCompanyFactory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param CustomerModelSession $customerSession
     * @param GroupManagementInterface $customerGroupManagement
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param CollectionFactory $productCollectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Msrp\Model\Config $msrp_config
     * @param \Magento\Msrp\Model\Quote\Msrp $msrp
     * @param CustomerCart $cart
     * @param B2BMessageFactory $b2bMessageFactory
     * @param \Magento\Framework\Registry $registry
     * @param ChData $ch_helper
     * @param B2BCustomerFactory $b2bCustomerFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param B2BCompanyFactory $b2bCompanyFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CustomerModelSession $customerSession,
        GroupManagementInterface $customerGroupManagement,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        CollectionFactory $productCollectionFactory,
        ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Msrp\Model\Config $msrp_config,
        \Magento\Msrp\Model\Quote\Msrp $msrp,
        CustomerCart $cart,
        B2BMessageFactory $b2bMessageFactory,
        \Magento\Framework\Registry $registry,
        ChData $ch_helper,
        B2BCustomerFactory $b2bCustomerFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\Order\Config $orderConfig,
        OrderRepositoryInterface $orderRepository,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        B2BCompanyFactory $b2bCompanyFactory,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->customerGroupManagement = $customerGroupManagement;
        $this->stockRegistry = $stockRegistry;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->productRepository = $productRepository;
        $this->msrp_config = $msrp_config;
        $this->msrp = $msrp;
        $this->cart = $cart;
        $this->_b2bMessageFactory = $b2bMessageFactory;
        $this->ch_helper = $ch_helper;
        $this->_catalogConfig = $catalogConfig;
        $this->_coreRegistry = $registry;
        $this->_b2bCustomerFactory = $b2bCustomerFactory;
        $this->_customerFactory = $customerFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->orderConfig = $orderConfig;
        $this->orderRepository = $orderRepository;
        $this->_backendUrl = $backendUrl;
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        $this->_resource = $resource;
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct($id)
    {
        $storeId = $this->_storeManager->getStore()->getStoreId();
        $product = $this->productRepository->getById($id, false, $storeId);
        return $product;
    }

    /**
     * @param $id
     * @return bool
     * @throws NoSuchEntityException
     */
    public function productCustomOptions($id)
    {
        $has_custom_options = false;
        $pr = $this->getProduct($id);
        if ($pr->hasOptions()) {
            $options = $pr->getOptions();
            if ($options) {
                foreach ($options as $option) {
                    if ($option instanceof \Magento\Catalog\Api\Data\ProductCustomOptionInterface) {
                        $option = $option->getData();
                        if (!isset($option['is_delete']) || $option['is_delete'] != '1') {
                            $has_custom_options = true;
                            break;
                        }
                    }
                }
            }
        }

        return $has_custom_options;
    }

    /**
     * @param $arr
     * @return DataObject
     */
    public function arrayToObject($arr)
    {
        $obj = new DataObject();
        $obj->addData($arr);
        return $obj;
    }

    /**
     * @return bool
     */
    public function isEnable()
    {
        $status = $this->scopeConfig->getValue(self::XML_PATH_ENABLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return (bool)$status;
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCurrentCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * @param bool $_customer
     * @return array|bool
     */
    public function checkB2BCustomerAccess($_customer = false)
    {
        $subuser_notice = $this->getConfig('b2b/access_login_form/dismessage');
        $noaccess_notice = $this->getConfig('b2b/access_login_form/message');
        $default_notice = __('You do not have access to this page');
        if (empty($subuser_notice)) {
            $subuser_notice = $default_notice;
        }
        if (empty($noaccess_notice)) {
            $noaccess_notice = $default_notice;
        }

        if (!$_customer) {
            $_customer = $this->customerSession->getCustomer();
        }

        if ($_customer && !empty($_customer->getId())) {
            $customer_id = $_customer->getId();
            // check if this is sub-user
            $model = $this->_b2bCustomerFactory->create();
            $model = $model->load($customer_id, 'customer_id');

            if ($model->getData('btb_active') == 0) {
                if ($model && !empty($model->getId())) { // is b2b user
                    // check role
                    if ($model->getRoleId() != 0) {
                        $notice = $subuser_notice;
                    } else {
                        $notice = $noaccess_notice;
                    }
                } else {
                    // not b2b
                    $notice = $noaccess_notice;
                }
                return ['error' => $notice];
            }
        } else {
            // no customer
            $notice = $noaccess_notice;
            return ['error' => $notice];
        }

        // check if in accesible customer group
        $group = $_customer->getGroupId();
        $access = $this->getConfig('b2b/access_login_form/group');
        $access = explode(',', $access);

        if (!in_array($group, $access)) {
            $notice = $noaccess_notice;
            return ['error' => $notice];
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getTableViewAllTitle()
    {
        return $this->getConfig('b2b/b2btables/view_all_products_table/title');
    }

    /**
     * @return mixed
     */
    public function getTableViewAllDesc()
    {
        return $this->getConfig('b2b/b2btables/view_all_products_table/desc');
    }

    /**
     * @return mixed
     */
    public function getTableViewAllTableWidth()
    {
        return $this->getConfig('b2b/b2btables/view_all_products_table/table_width_products');
    }

    /**
     * @return mixed
     */
    public function getPrevOrderTitle()
    {
        return $this->getConfig('b2b/b2btables/prev_ordered_table/title');
    }

    /**
     * @return mixed
     */
    public function getPrevOrderDesc()
    {
        return $this->getConfig('b2b/b2btables/prev_ordered_table/desc');
    }

    /**
     * @return mixed
     */
    public function getPrevOrderTableWidth()
    {
        return $this->getConfig('b2b/b2btables/prev_ordered_table/table_width_reorder');
    }

    /**
     * @param bool $get_default_system_group
     * @return bool|int|null
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultB2BGroup($get_default_system_group = true)
    {
        $groups = $this->getConfig('b2b/access_login_form/group');
        $groups = explode(',', $groups);
        if ($groups && isset($groups[0]) && !empty($groups[0])) {
            return $groups[0];
        }

        if (!$get_default_system_group) {
            return false;
        }

        $store = $this->_storeManager->getStore();
        return $this->customerGroupManagement->getDefaultGroup($store->getId())->getId();
    }

    /**
     * @return mixed
     */
    public function getStoreName()
    {
        $storeId = $this->_storeManager->getStore()->getStoreId();
        return $this->_storeManager->getStore($storeId)->getFrontendName();
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getText($path)
    {
        return $this->getConfig('b2b/' . $path);
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getConfig($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $file
     * @return string
     */
    public function getMediaUrl($file = '')
    {
        return $this->_storeManager->getStore()->getBaseUrl(DirectoryList::MEDIA) . $file;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return $this->_getRequest()->isSecure() ? true : false;
    }

    /**
     * @return string
     */
    public function getUsersGridUrl()
    {
        return $this->_backendUrl->getUrl('b2b/company/users', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getOrdersGridUrl()
    {
        return $this->_backendUrl->getUrl('b2b/company/orders', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getProductsGridUrl()
    {
        return $this->_backendUrl->getUrl('b2b/files/products', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getAddressFormUrl()
    {
        return $this->_backendUrl->getUrl('b2b/company/address');
    }

    /**
     * @return string
     */
    public function getDashboardUrl()
    {
        $_secure = $this->isSecure();
        $storeId = $this->_storeManager->getStore()->getStoreId();

        $url = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, $_secure);
        return $url . 'customer/account';
    }

    /**
     * @return mixed
     */
    public function getHomepageUrl()
    {
        $_secure = $this->isSecure();
        $storeId = $this->_storeManager->getStore()->getStoreId();

        $url = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, $_secure);
        return $url;
    }

    /**
     * @return string
     */
    public function getCheckoutUrl()
    {
        $_secure = $this->isSecure();
        $storeId = $this->_storeManager->getStore()->getStoreId();

        $url = $this->_storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, $_secure);
        return $url . 'checkout';
    }

    /**
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->_urlBuilder->getUrl('customer/account/logout');
    }

    /**
     * @param bool $addon
     * @return string
     */
    public function getDownloadCenterUrl($addon = false)
    {
        $_secure = $this->isSecure();
        $url = $this->_urlBuilder->getUrl('b2b/download', ['_secure' => $_secure]);

        if ($addon) {
            $url .= '#' . $addon;
        }

        return $url;
    }

    /**
     * @param bool $type
     * @param bool $secure
     * @return mixed
     */
    public function getBaseUrl($type = false, $secure = false)
    {
        if (!$type) {
            $type = \Magento\Framework\UrlInterface::URL_TYPE_LINK;
        }
        return $this->_storeManager->getStore()->getBaseUrl($type, $secure);
    }

    /**
     * @return mixed|string
     */
    public function getRedirectAfterLoginUrl()
    {
        $home_page = $this->getHomepageUrl();
        $url = $home_page;

        $page = $this->getConfig('b2b/access_login_form/redirect_after_login');
        switch ($page) {
            case 'home':
                $url = $this->getHomepageUrl();
                break;
            case 'dashboard':
                $url = $this->getDashboardUrl();
                break;
            case 'custom':
                $custom_url = $this->getConfig('b2b/access_login_form/redirect_after_login_custom');
                if (!empty($custom_url)) {
                    $last = substr($home_page, -1);
                    if ($last == '/') {
                        $len = strlen($home_page);
                        $home_page = substr($home_page, 0, $len - 1);
                    }
                    $p = substr($custom_url, 0, 1);
                    if ($p == '/')
                        $url = $home_page . $custom_url;
                    else {
                        $p = substr($custom_url, 0, 4);
                        if ($p != 'http') {
                            $url = $home_page . '/' . $custom_url;
                        } else {
                            $url = $custom_url;
                        }
                    }
                }
                break;
        }

        return $url;
    }

    /**
     * @return mixed
     */
    public function standardLoginDisabled()
    {
        return $this->getConfig('b2b/access_login_form/disable_standard_login_registration');
    }

    /**
     * @param $id
     * @return bool|mixed
     */
    public function getProductFullInfo($id)
    {
        $attributes = $this->_catalogConfig->getProductAttributes();
        $attributes[count($attributes) + 1] = 'qty';

        $collection = $this->_productCollectionFactory->create();

        try {
            $collection->addAttributeToSelect($attributes)
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->addIdFilter($id)->load();
        } catch (NoSuchEntityException $e) {
            return false;
        }

        foreach ($collection as $pr) {
            return $pr;
        }

        return false;
    }

    /**
     * @param $productId
     * @param bool $full
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface|mixed
     */
    public function getProductInfo($productId, $full = false)
    {
        if ($productId) {
            try {
                $product = $this->getProductFullInfo($productId);
            } catch (NoSuchEntityException $e) {
                return false;
            }

            if ($product) {
                return $product;
            }
            // if not found, try get product by other method

            /// old logic, does not work correctly, because return wrong sku fro config product
            $storeId = $this->_storeManager->getStore()->getStoreId();
            try {
                $product = $this->productRepository->getById($productId, false, $storeId);

                if ($full) {
                    if ($product) {
                        $product_data = $product->getData();

                        $addon_info = $this->getProductFullInfo($productId);
                        if ($addon_info) {
                            $data = $addon_info->getData();
                            foreach ($data as $key => $value) {
                                if (!isset($product_data[$key])) {
                                    $product->setData($key, $value);
                                }
                            }
                        }
                    }
                }

                return $product;
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * @param $product
     * @return bool
     */
    public function isBackOrder($product)
    {
        $inventory = $this->getProductStockItem($product);
        $inventory->getBackorders();
        $data = $inventory->getData();

        return (
            isset($data['qty'])
            && $data['qty'] != ''
            && $data['qty'] <= 0
            && $data['is_in_stock']
        );
    }

    public function showAvailability($product)
    {
        if ($this->isBackOrder($product)) {
            return __('Backorder');
        }

        $inventory = $this->getProductStockItem($product);
        $in_stock = $inventory->getisInStock();
        $data = $inventory->getData();
        $qty = isset($data['qty']) ? $data['qty'] : '';
        if ($qty != '') {
            return ($data['qty'] <= 0 || !$in_stock)
                ? __('Out of Stock')
                : round($qty, 2);
        }

        return ($in_stock) ? __('In Stock') : __('Out of Stock');
    }

    /**
     * @param $product
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    public function getProductStockItem($product)
    {
        $productId = $product->getId();
        $stock = $this->stockRegistry->getStockItem($productId, $product->getStore()->getWebsiteId());
        return $stock;
    }

    /**
     * @return CustomerCart
     */
    protected function _getCart()
    {
        return $this->cart;
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    /**
     * @return bool
     */
    public function canApplyMsrp()
    {
        $quote = $this->_getQuote();
        if (!$this->msrp->getCanApplyMsrp($quote->getId()) && $this->msrp_config->isEnabled()) {
            $quote->collectTotals();
        }
        return $this->msrp->getCanApplyMsrp($quote->getId());
    }

    /**
     * @return bool
     */
    public function getCustomerMessage()
    {
        $customer = $this->customerSession->getCustomer();

        $collection = $this->_b2bMessageFactory->create();
        $item = $collection->addFieldToFilter('group_id', ['eq' => $customer->getGroupId()])
            ->addFieldToFilter('is_active', ['eq' => 1])->getFirstItem();

        $message = $item->getMessage();
        if ($message == null) {
            return false;
        }

        return $message;
    }

    /**
     * @param bool $check_enabled_only
     * @return bool
     */
    public function helperIsAvailableFooter($check_enabled_only = false)
    {
        if (!$this->isEnable()) {
            return false;
        }

        if ($check_enabled_only) {
            return true;
        }

        if (!$this->isLoggedIn()) {
            return false;
        }

        $error = $this->checkB2BCustomerAccess();
        if ($error && is_array($error)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool|string
     */
    public function getTotal()
    {
        $price = $this->_getQuote()->getGrandTotal();
        if ($price > 0) {
            return $this->ch_helper->formatPrice($price);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getOrderAllUrl()
    {
        $_secure = $this->isSecure();
        return $this->_urlBuilder->getUrl('b2b/order/products', ['_secure' => $_secure]);
    }

    /**
     * @return string
     */
    public function getReorderUrl()
    {
        $_secure = $this->isSecure();
        return $this->_urlBuilder->getUrl('b2b/order/reorder', ['_secure' => $_secure]);
    }

    /**
     * @return mixed
     */
    public function getPrevParams()
    {
        return $this->customerSession->getPrevParams();
    }

    /**
     * @param $customer_id
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getB2bCustomer($customer_id)
    {
        $tableName = $this->_resource->getTableName('iwd_b2b_customer_info');

        $customers = $this->_customerFactory->create()->getCollection();
        $customers->getSelect()->join(["b2b_customer" => $tableName], "e.entity_id = b2b_customer.customer_id AND b2b_customer.customer_id =" . $customer_id);

        return $customers;
    }

    /**
     * @param $size
     * @return string
     */
    public function formatFileSize($size)
    {
        $mb = 1024 * 1024;
        $size_str = '';
        if ($size >= 1024) {
            $size1 = round($size / $mb, 2); // Kb
            if ($size1 == 0) {
                $size_str = round($size / 1024, 2) . ' KB';
            } else {
                $size_str = $size1 . ' MB'; // MB
            }
        } else {
            $size1 = round($size / 1024, 2); // Kb
            if ($size1 == 0) {
                $size_str = round($size / 1, 2) . ' B'; // MB
            } else {
                $size_str = $size1 . ' KB';
            }
        }
        return $size_str;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getRegistryData($key)
    {
        return $this->_coreRegistry->registry($key);
    }

    /**
     * @param $customer_id
     * @return $this|\Magento\Customer\Model\Customer
     */
    public function getCustomerInfo($customer_id)
    {
        $_customer = $this->_customerFactory->create();
        return $_customer->load($customer_id);
    }

    public function isB2BUser($customer_id = false)
    {
        if (empty($customer_id)) {
            $_customer = $this->customerSession->getCustomer();
            $customer_id = $_customer->getId();
        }

        $model = $this->_b2bCustomerFactory->create();
        $model = $model->load($customer_id, 'customer_id');

        if (!$model || $model->getId() == null) {
            return false;
        }

        // set company name to model
        $company_id = $model->getData('company_id');
        if (!empty($company_id)) {
            $_company = $this->_b2bCompanyFactory->create();
            $_company = $_company->load($company_id);
            
            if ($_company && $_company->getId()) {
                $model->setData('store_name', $_company->getStoreName());
            }
        }
        
        return $model;
    }

    /**
     * @param bool $customer_id
     * @return bool
     */
    public function getPrimaryUserInfo($customer_id = false)
    {
        $model = $this->isB2BUser($customer_id);
        if (!$model) {
            return false;
        }

        $parent_id = $model->getParentId();

        if (empty($parent_id)) {
            // primary contact has access to all
            return $model;
        }

        return $this->isB2BUser($parent_id);
    }

    /**
     * check if current user is not b2b or b2b module is not enabled for this store, or user is not assigned to this b2b group
     * @param bool $customer_id
     * @return bool
     */
    public function isNotB2B($customer_id = false)
    {
        // check if is b2b user
        $model = $this->isB2BUser($customer_id);
        if (!$model || is_null($model->getId())) {
            return true; // non b2b user
        }

        // check b2b active for current store
        if (!$this->isEnable()) {
            return true; // allow access if b2b s disabled
        }

        $_customer = false;
        if ($customer_id) {
            $_customer = $this->getCustomerInfo($customer_id);
        }

        $error = $this->checkB2BCustomerAccess($_customer);
        if ($error && is_array($error)) {
            return true; // allow access for b2b user if user is not active for b2b or is in non b2b group
        }

        return false;
    }

    /**
     * get current user company id
     * @return bool
     */
    public function getCurrentCompanyId()
    {
        $b2b_model = $this->isB2BUser();
        if (!$b2b_model) {
            return false;
        }

        return $b2b_model->getData('company_id');
    }

    /**
     * get administrators in company
     * @param bool $company_id
     * @return array|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getAdministrators($company_id = false)
    {
        if (empty($company_id)) {
            $company_id = $this->getCurrentCompanyId();
        }
        if (empty($company_id)) {
            return [];
        }

        $tableName = $this->_resource->getTableName('iwd_b2b_customer_info');

        $collection = $this->_customerFactory->create()->getCollection();
        $collection->getSelect()->join(
            ["b2b_customer" => $tableName],
            " e.entity_id = b2b_customer.customer_id "
        );
        $collection->getSelect()->where(" b2b_customer.company_id = '{$company_id}' ");
        $collection->getSelect()->where(" b2b_customer.role_id IN (0,1) ");

        return $collection;
    }

    /**
     * get all user ids of this customer's company
     * @param bool $company_id
     * @return array
     */
    public function getAllB2Busers($company_id = false)
    {
        if (empty($company_id)) {
            $company_id = $this->getCurrentCompanyId();
        }
        if (empty($company_id)) {
            return [];
        }

        $tableName = $this->_resource->getTableName('iwd_b2b_customer_info');

        $collection = $this->_customerFactory->create()->getCollection();
        $collection->getSelect()->join(
            ["b2b_customer" => $tableName],
            "e.entity_id = b2b_customer.customer_id"
        );
        $collection->getSelect()->where(" b2b_customer.company_id = '{$company_id}' ");

        $ids = [];
        foreach ($collection as $user) {
            $ids[] = $user->getCustomerId();
        }

        return $ids;
    }

    /**
     * check if b2b user can view order
     * @param \Magento\Sales\Model\Order $order
     * @return boolean
     */
    public function checkCanViewOrder(\Magento\Sales\Model\Order $order)
    {
        $customerId = $this->customerSession->getCustomerId();
        $availableStatuses = $this->orderConfig->getVisibleOnFrontStatuses();
        if ($order->getId()
            && $order->getCustomerId()
            && $order->getCustomerId() == $customerId
            && in_array($order->getStatus(), $availableStatuses, true)
        ) {
            return true;
        }

        // one more check for b2b user (check if current b2b user has access to this order, which may be order of other b2b sub-user)
        $not_b2b = $this->isNotB2B($customerId);
        if (!$not_b2b) { // is b2b user
            $all_b2b_users = $this->getAllB2Busers();
            $order_customer_id = $order->getCustomerId();

            if ($order->getId() && $order->getCustomerId()
                && in_array($order_customer_id, $all_b2b_users)
                && in_array($order->getStatus(), $availableStatuses, true)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $id
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrderInfo($id)
    {
        try {
            $order = $this->orderRepository->get($id);
            return $order;
        } catch (NoSuchEntityException $e) {
        } catch (InputException $e) {
        }

        return false;
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function isB2BOrder($order_id)
    {
        $is_b2b = false;

        $order = $this->getOrderInfo($order_id);
        if ($order) {
            $customer_id = $order->getCustomerId();
            if (!empty($customer_id)) {
                // check if is b2b user
                $model = $this->isB2BUser($customer_id);
                if ($model) {
                    $is_b2b = true;
                }
            }
        }

        return $is_b2b;
    }
}

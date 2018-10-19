<?php
namespace IWD\B2B\Helper;

// credit limit
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Model\CustomerFactory;
use IWD\B2B\Model\CustomerFactory as B2BCustomerFactory; // we need to rename class name for b2b, because we also have
use IWD\B2B\Model\CompanyFactory as B2BCompanyFactory;

class Credit extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \IWD\B2B\Helper\Data
     */
    protected $_helper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $_orderCollectionFactory;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @var \IWD\B2B\Model\CustomerFactory
     */
    protected $_b2bCustomerFactory;
    
    /**
     * @var \IWD\B2B\Model\CompanyFactory
     */
    protected $_b2bCompanyFactory;
    
    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_viewDesign;
    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \IWD\B2B\Helper\Data $helper,
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
            \Magento\Customer\Model\Session $customerSession,
            B2BCustomerFactory $b2b_customerFactory, 
            B2BCompanyFactory $b2b_companyFactory,
            \Magento\Framework\View\DesignInterface $viewDesign
    ) {
        parent::__construct($context);
        
        $this->_helper = $helper;
        
        $this->_orderCollectionFactory = $orderCollectionFactory;
        
        $this->customerSession = $customerSession;
        
        $this->_b2bCustomerFactory = $b2b_customerFactory;
        $this->_b2bCompanyFactory = $b2b_companyFactory;
        
        $this->_viewDesign = $viewDesign;
    }

    /**
     * check if payment method is offline
     * @param unknown $methodInstance
     */
    public function isOfflinePayment($methodInstance) {
    
        // check if user can use offline payemnts
        if ($methodInstance instanceof \Magento\OfflinePayments\Model\Checkmo
        || $methodInstance instanceof \Magento\OfflinePayments\Model\Banktransfer
        || $methodInstance instanceof \Magento\OfflinePayments\Model\Cashondelivery
        || $methodInstance instanceof \Magento\OfflinePayments\Model\Purchaseorder
        ) {
            return true;
        }
        return false;
    }
    
    /**
     * check if payment method allowed base on b2b user credit limit
     * @param unknown $methodInstance
     */
    public function creditPaymentAllowed($methodInstance) {
        $show_payment = 1;
        
        // check if user can use offline payemnts
        if ($this->isOfflinePayment($methodInstance)) {
            // check credit limit
            $show_payment = $this->checkCredit(false);
        }
        return $show_payment;
    }
    
    public function checkoutGuestLimitMessage(){
        $use_guest_limit = false;
        $is_logged = $this->_helper->isLoggedIn();
        if($is_logged) // not guest
            return false;
        
        $guest_checkout_limit = $this->_helper->getConfig('b2b/guest_checkout/use_quest_limit');
        if(!$guest_checkout_limit) // limit disabled
            return true; // return true because it is guest
        
        $show_guest_checkout_limit = $this->_helper->getConfig('b2b/guest_checkout/use_quest_limit_message');
        if(!$show_guest_checkout_limit)
            return true; // return true because it is guest
            
        $notice = $this->_helper->getConfig('b2b/guest_checkout/guest_limit_notification');
        if(empty($notice))
            return true; // return true because it is guest

        return ['message' => $notice];
    }
    
    public function checkCredit($return_message = true) {
        $quoteTotal = 0;
        
        $area = $this->_viewDesign->getArea();
        if ($area != 'adminhtml') { // do not apply rules for admin portal
            
            $is_not_b2b = $this->_helper->isNotB2B();
            if ($is_not_b2b) // check if not b2b
                return true;
            else { // check if user is not requester
                $model = $this->_helper->isB2BUser();
                $role = $model->getRoleId();
                if (!$model || $role == 2)
                    return true;
            }
            
            $quote = $this->_helper->_getQuote();
            // We will add the total amount of the current order to the total due
            $quoteTotal = $quote ? $quote->getGrandTotal() : 0;
        }
        else{
            return true;
        }
        
        $company_id = $model->getData('company_id');
        if(empty($company_id)){ 
            return true;
        }
        
        $available = $this->getAvailableLimit($company_id);
        if (is_null($available)) {
            return true;
        } else {
            if ($available > 0) {
                $diff = $available-$quoteTotal;
                if ($diff >= 0)
                    return true;
            }
            
            /// limit reached
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $currencysymbol = $objectManager->get('Magento\Directory\Model\Currency');
            $currency_symbol = $currencysymbol->getCurrencySymbol();
            
            if ($return_message) {
                $notice = $this->_helper->getConfig('b2b/credit_limit_notification/credit_notification');
                $credit_message = $notice." <strong>".$currency_symbol.number_format($available,2)."</strong>";
                return $credit_message;
            }
            return false;
        }
    }
    
    public function getAvailableLimit($company_id = false) {
        if (!$company_id){
            $company_id = $this->getRequest()->getParam('company_id');
        }
        
        if(empty($company_id))
            return null;
        
        $limit = $this->getCreditLimit($company_id);
        if (is_null($limit))
            return null;
    
        $orderTotal = $this->getOrdersTotal($company_id);
    
        $available = $limit-$orderTotal;
        if ($available < 0)
            $available = 0;
    
        return $available;
    }
    
    public function getOrdersTotal($company_id) {
        $collection = $this->_orderCollectionFactory->create();
        $collectionTemp = $this->_orderCollectionFactory->create();
        
        $B2BCustomer = $this->_b2bCustomerFactory->create()->getCollection();
        $B2BCustomer->addFieldToFilter('company_id', ['eq' => $company_id]);
    
        foreach ($B2BCustomer as $value) {
            $collectionTemp->addFieldToFilter('customer_id', ['neq' => $value->getData('customer_id')]);
        }
    
        foreach ($collectionTemp as $value) {
            $collection->addFieldToFilter('customer_id', ['neq' => $value->getData('customer_id')]);
        }
    
        $orders = $collection->addAttributeToSelect('base_grand_total')->addAttributeToSelect('base_total_paid')
            ->addFieldToFilter('status', ['like' => '%pending%'])
            ->addFieldToFilter('status', ['neq' => 'btb_pending_approval']);
        //            ->addFieldToFilter('status', ['neq' => 'complete']);
    
        // Check through all the orders and add up the total due of all the orders
        $orders = $orders->toArray();
        $totalDue = 0;
        foreach ($orders['items'] AS $order) {
            $orderDue = $order['base_grand_total']-$order['base_total_paid'];
            $totalDue += $orderDue;
        }
    
        return $totalDue;
    }
    
    public function getCreditLimit($company_id = false) {
        $company = $this->_b2bCompanyFactory->create();
        $company = $company->load($company_id, 'company_id');
        if (!$company)
            return null;
    
        $limit = $company->getCreditLimit();
        if (!is_null($limit)) {
            $limit = (double)$limit;
        }
        return $limit;
    }
    
}
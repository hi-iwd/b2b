<?php
namespace IWD\B2B\Helper;

use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Sales\Model\Order;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer;

class Emails extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $logger;
    
    protected $inlineTranslation;
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    
    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;
    
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    
    /**
     * @var \IWD\B2B\Helper\Data
     */
    protected $_helper;
    
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    
    /**
     * @var Renderer
     */
    protected $addressRenderer;
    
    protected $customerSession;
    
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            TransportBuilder $transportBuilder,
            CustomerViewHelper $customerViewHelper,
            \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
            \Magento\Customer\Model\CustomerFactory $customerFactory,
            \IWD\B2B\Helper\Data $helper,
            CustomerModelSession $customerSession,
            PaymentHelper $paymentHelper,
            Renderer $addressRenderer
    ) {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->customerViewHelper = $customerViewHelper;
        $this->inlineTranslation = $inlineTranslation;
        $this->_customerFactory = $customerFactory;
        $this->_helper = $helper;
        $this->paymentHelper = $paymentHelper;
        $this->addressRenderer = $addressRenderer;
        $this->customerSession = $customerSession;
    }

    public function getConfig($path) {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    /**
     * Send corresponding email template
     *
     * @param CustomerInterface $customer
     * @param string $template configuration path of email template
     * @param string $sender configuration path of email identity
     * @param array $templateParams
     * @param int|null $storeId
     * @return $this
     */
    public function sendEmailTemplate($customer, $template, $sender, $templateParams = [], $storeId = null)
    {
        $templateId = $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId);
        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
        ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
        ->setTemplateVars($templateParams)
        ->setFrom($this->scopeConfig->getValue($sender, ScopeInterface::SCOPE_STORE, $storeId));
        if ($customer instanceof \Magento\Customer\Api\Data\CustomerInterfaceFactory
         || $customer instanceof \Magento\Customer\Api\CustomerRepositoryInterface
         || is_object($customer)){
            $transport = $transport->addTo($customer->getEmail(), $this->customerViewHelper->getCustomerName($customer));
        }
        else {
            if (is_array($customer)) {
                $name = isset($customer['name'])?$customer['name']:false;
                $transport = $transport->addTo($customer['email'], $name);
            }
            else
                $transport = $transport->addTo($customer);
        }
        $transport = $transport->getTransport();
    
        $transport->sendMessage();
    
        return $this;
    }
    
    /**
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
        ? null
        : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }
    
    /**
     * @param Order $order
     * @return string|null
     */
    protected function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    /**
     * Send B2B Customer Approved email
     * @param Magento\Customer\Api\Data\CustomerInterfaceFactory $_customer
     */
    public function approveCustomerEmail($_customer) {
    
        // load customer
        $customer = $_customer;
        $full_name = $this->customerViewHelper->getCustomerName($customer);
    
        $storeId = $customer->getStoreId();
        if (!$storeId) {
            $storeId = $this->_storeManager->getStore()->getStoreId();
        }
        $store = $this->_storeManager->getStore($storeId);
    
        $store_name = $store->getFrontendName();
    
        $postObject = new DataObject();
        $postObject->setData('storename', $store_name);
        $postObject->setData('customer', $full_name);
        $postObject->setData('email', $customer->getEmail());
    
        $support_email = $this->_helper->getConfig('trans_email/ident_support/email');
        $postObject->setData('support_email', $support_email);
    
        $this->inlineTranslation->suspend();
    
        try {
            $this->sendEmailTemplate(
                    $customer,
                    'b2b/emails/customer_approved',
                    'contact/email/sender_email_identity',
                    ['data' => $postObject, 'store' => $store],
                    $storeId
            );
    
            $this->inlineTranslation->resume();
        } catch (MailException $e) {
            $this->inlineTranslation->resume();
            // If we are not able to send a new account email, this should be ignored
            $this->_logger->critical($e);
        }
    
    }
    
}

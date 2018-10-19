<?php
namespace IWD\B2B\Block\Account;

class Register extends \Magento\Directory\Block\Data
{
    protected $_helper;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    
    protected $directoryHelper;
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Directory\Helper\Data $directoryHelper,
            \Magento\Framework\Json\EncoderInterface $jsonEncoder,
            \Magento\Framework\App\Cache\Type\Config $configCacheType,
            \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
            \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
            \Magento\Customer\Model\Session $customerSession,
            \IWD\B2B\Helper\Data $helper,
            array $data = []
    ) {
        $this->_helper = $helper;
        $this->directoryHelper = $directoryHelper;
        $this->_customerSession = $customerSession;
        
        parent::__construct($context, $directoryHelper, $jsonEncoder, $configCacheType, $regionCollectionFactory, $countryCollectionFactory, $data);
    }
    
    /**
     * Retrieve form data
     *
     * @return mixed
     */
    public function getFormData($clear_session = false)
    {
        $formData = $this->_customerSession->getCustomerFormData($clear_session);
        $data = new \Magento\Framework\DataObject();
        if ($formData) {
            $data->addData($formData);
            $data->setCustomerData(1);
        }
        return $data;
    }
    
    public function getPostUrl() {
        $_secure = $this->_helper->isSecure();
        return  $this->getUrl('b2b/account/registerPost', ['_secure'=>$_secure]);
    }
    
    
    public function isShowNonRequiredState()
    {
        return $this->directoryHelper->isShowNonRequiredState();
    }
    
}

<?php
namespace IWD\B2B\Block;
use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Context;

class Footer extends Template
{
    protected $_coreRegistry;
    
    protected $_isScopePrivate;
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Registry $registry,
            array $data = []
    ) {
        
        $this->_coreRegistry = $registry;
        
        parent::__construct($context, $data);
        
        $this->_isScopePrivate = true;
    }

    public function getIsCheckoutPage() {
        if ($this->getCurrentPage() == 'checkout')
            return $this->getCurrentPage();
        return false;
    }
    
    public function getRegistryData($key) {
        return $this->_coreRegistry->registry($key);
    }

}

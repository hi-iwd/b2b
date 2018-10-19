<?php
namespace IWD\B2B\Block\Account;

use Magento\Framework\View\Element\Template;
 
class Login extends Template
{
    protected $_helper;
    
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \IWD\B2B\Helper\Data $helper,
            array $data = []
    ) {
        $this->_helper = $helper;
        
        parent::__construct($context, $data);
    }
    
	public function getRegisterUrl() {
		$_secure = $this->_helper->isSecure();
		return  $this->getUrl('b2b/account/register', ['_secure'=>$_secure]);
	}	
}

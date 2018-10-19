<?php

namespace IWD\B2B\Block\Account;

use Magento\Customer\Model\Context;

/**
 * "Orders and Returns" link
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Link extends \Magento\Framework\View\Element\Html\Link\Current
{
    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \IWD\B2B\Helper\Data
     */
    protected $_helper;

    /**
     * @var \IWD\B2B\Helper\Roles
     */
    protected $_helper_roles;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\DefaultPathInterface $defaultPath
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\DefaultPathInterface $defaultPath,
        \Magento\Framework\App\Http\Context $httpContext,
        \IWD\B2B\Helper\Data $helper,
        \IWD\B2B\Helper\Roles $helper_roles,
        array $data = []
    ) {
        parent::__construct($context, $defaultPath, $data);
        $this->httpContext = $httpContext;
        $this->_helper = $helper;
        $this->_helper_roles = $helper_roles;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $allow = $this->_helper_roles->checkRoleAccess('manage_users');
        if (!$allow)
            return '';
/*        
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return '';
        }
*/
        return parent::_toHtml();
    }
}

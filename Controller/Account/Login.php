<?php
namespace IWD\B2B\Controller\Account;
use IWD\B2B\Controller\AbstractAccount;

class Login extends AbstractAccount
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    protected $_helper;
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \IWD\B2B\Helper\Data $helper
    ) {
        $this->customerSession = $customerSession;
        $this->pageFactory = $resultPageFactory;
        $this->_helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $err = $this->_helper->checkB2BCustomerAccess();
            if ($err && is_array($err)) { // has no access
                // continue with login form
            }
            else{
                $redirectUrl = $this->_helper->getDashboardUrl();
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setUrl($redirectUrl);
                return $resultRedirect;
            }
        }
        
        $resultPageFactory = $this->pageFactory->create();
        
        // Add page title
        $resultPageFactory->getConfig()->getTitle()->set(__('B2B Login'));
        return $resultPageFactory;
    }

}

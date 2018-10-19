<?php

namespace IWD\B2B\Controller\Account;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;
use IWD\B2B\Controller\AbstractController;

class Notactive extends AbstractController
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Config\Model\ResourceModel\Config
     */
    protected $configResource;
    
    /**
     * @param Context $context
     * @param Session $customerSession
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Config\Model\ResourceModel\Config $configResource,
        \Magento\Framework\Registry $registry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->configResource  = $configResource;
        parent::__construct($context, $registry);
    }

    /**
     * Customer register form page
     *
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $params = $this->getRequest()->getParam('refresh', []);
        if(!empty($params)){
            
            $storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
            $this->configResource->saveConfig('b2b/lstlu', '', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);            
        }
        
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}

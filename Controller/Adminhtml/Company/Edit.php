<?php

namespace IWD\B2B\Controller\Adminhtml\Company;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \IWD\B2B\Model\CompanyFactory
     */
    protected $_b2bCompanyFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \IWD\B2B\Model\CompanyFactory $b2bCompanyFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        $this->_coreRegistry = $coreRegistry;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('IWD_B2B::b2b_company');
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('IWD_B2B::b2b_company')
            ->addBreadcrumb(__('Company'), __('Company'));
        return $resultPage;
    }

    /**
     * Edit Company
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('company_id');
        $model = $this->_b2bCompanyFactory->create();

        // 2. Initial checking
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This company no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        // 3. Set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        $this->_coreRegistry->register('b2b_company', $model);
        
        $this->_objectManager->get('Magento\Backend\Model\Session')->setCurComp($id);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // 5. Build edit form
        $this->initPage($resultPage)->addBreadcrumb(
            $id ? __('Edit Company') : __('New Company'),
            $id ? __('Edit Company') : __('New Company')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Company'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getStoreName() : __('New Company'));
        return $resultPage;
    }
}

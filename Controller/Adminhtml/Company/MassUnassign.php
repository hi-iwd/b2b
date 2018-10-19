<?php

namespace IWD\B2B\Controller\Adminhtml\Company;

class MassUnassign extends \Magento\Backend\App\Action
{
    /**
     * @var \IWD\B2B\Helper\Company
     */
    protected $_company_helper;
    
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \IWD\B2B\Helper\Company $helper
    ) {
        parent::__construct($context);
        $this->_company_helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $companyUserIds = $this->getRequest()->getParam('entity_id');
        $companyId = $this->getRequest()->getParam('id');

        $ret = $this->_company_helper->unassignUsers($companyId, $companyUserIds);

        $updatedCnt = $ret['updated'];
        $errorsCnt = $ret['errors'];
        
        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been unassigned.', $updatedCnt));
        if ($errorsCnt > 0) {
            $this->messageManager->addErrorMessage(__('A total of %1 record(s) have not been unassigned.', $errorsCnt));
        }
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        return $resultRedirect->setPath('*/*/edit', ['company_id' => $companyId]);
    }
}


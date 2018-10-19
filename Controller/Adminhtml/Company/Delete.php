<?php
namespace IWD\B2B\Controller\Adminhtml\Company;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \IWD\B2B\Helper\Company
     */
    protected $_company_helper;
    
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \IWD\B2B\Helper\Company $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \IWD\B2B\Helper\Company $helper
    ) {
        parent::__construct($context);
        
        $this->_company_helper = $helper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('IWD_B2B::b2b_company');
    }

    /**
     * Delete action
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        // check if we know what should be deleted
        if ($id = $this->getRequest()->getParam('company_id')) {

            try {
                // init model and delete
                $model = $this->_company_helper->getCompanyInfo($id);
                $model->delete();

                // unassign users from company
                $ret = $this->_company_helper->unassignUsers($id, [], true);
                
                // display success message
                $this->messageManager->addSuccess(__('The company has been deleted.'));

                // go to grid
                $this->_redirect('*/*/');
                return;
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addError($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/edit', ['company_id' => $id]);
            }
        }

        // display error message
        $this->messageManager->addError(__('Unable to find a company to delete.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }

}

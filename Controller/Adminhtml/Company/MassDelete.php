<?php
namespace IWD\B2B\Controller\Adminhtml\Company;

class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $_customerCollectionFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        parent::__construct($context);
        $this->_customerCollectionFactory = $customerCollectionFactory;
    }
    
    public function execute()
    {
        $companyUserIds = $this->getRequest()->getParam('entity_id');
        $companyId = $this->getRequest()->getParam('id');
        
        $companyUsers = $this->_customerCollectionFactory->create()
            ->addFieldToFilter('entity_id', ['in' => $companyUserIds])
            ->getItems();

        $deletedCnt = 0;

        foreach ($companyUsers as $companyUser) {
            $companyUser->delete();
            $deletedCnt++;
        }

        $this->messageManager->addSuccess(
            __('A total of %1 record(s) have been deleted.', $deletedCnt)
        );
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        
        return $resultRedirect->setPath('*/*/edit', ['company_id' => $companyId]);
    }
}

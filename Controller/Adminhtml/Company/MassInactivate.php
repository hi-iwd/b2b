<?php

namespace IWD\B2B\Controller\Adminhtml\Company;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use IWD\B2B\Model\ResourceModel\Customer\Collection;

/**
 * Class MassInactivate
 * @package IWD\B2B\Controller\Adminhtml\Company
 */
class MassInactivate extends Action
{
    /**
     * @var \IWD\B2B\Model\ResourceModel\Customer\Collection
     */
    private $b2bCustomer;

    /**
     * MassInactivate constructor.
     * @param Context $context
     * @param Collection $b2bCustomer
     */
    public function __construct(
        Context $context,
        Collection $b2bCustomer
    ) {
        parent::__construct($context);
        $this->b2bCustomer = $b2bCustomer;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $updatedCnt = 0;
        $errorsCnt = 0;
        $companyUserIds = $this->getRequest()->getParam('entity_id');
        $companyUsers = $this->b2bCustomer
            ->addFieldToFilter('customer_id', ['in' => $companyUserIds])
            ->getItems();

        foreach ($companyUsers as $customer) {
            try {
                $customer->setData('btb_active', 0);
                $customer->save();
                $updatedCnt++;
            } catch (\Exception $e) {
                $errorsCnt++;
            }
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been inactivated.', $updatedCnt));
        if ($errorsCnt > 0) {
            $this->messageManager->addErrorMessage(__('A total of %1 record(s) have not been inactivated.', $errorsCnt));
        }
        
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $companyId = $this->getRequest()->getParam('id');

        return $resultRedirect->setPath('*/*/edit', ['company_id' => $companyId]);
    }
}


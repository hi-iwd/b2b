<?php
namespace IWD\B2B\Controller\Adminhtml\Company;
use Magento\Framework\Controller\ResultFactory;
class Index extends \IWD\B2B\Controller\Adminhtml\Company
{
    public function execute()
    {
       $resultPage = $this->_initAction();
       $resultPage->setActiveMenu('IWD_B2B::b2b_company');
       $resultPage->addBreadcrumb(__('B2B'), __('Company'));
       $resultPage->getConfig()->getTitle()->prepend(__('Companies'));
       return $resultPage;
    }
}

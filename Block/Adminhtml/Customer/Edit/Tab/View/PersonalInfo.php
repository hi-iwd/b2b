<?php

namespace IWD\B2B\Block\Adminhtml\Customer\Edit\Tab\View;

use IWD\B2B\Model\ResourceModel\Customer\Collection;
use \IWD\B2B\Model\CompanyFactory as B2BCompany;

/**
 * Adminhtml customer view personal information b2b company block.
 *
 * @property  _b2bCustomerFactory
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PersonalInfo extends \Magento\Backend\Block\Template
{
    private $b2bCustomerCollection;
    private $b2bCompanyFactory;

    /**
     * PersonalInfo constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param Collection $b2bCustomerCollection
     * @param B2BCompany $B2BCompanyFactory
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        Collection $b2bCustomerCollection,
        B2BCompany $B2BCompanyFactory
    )
    {
        parent::__construct($context, $data);
        $this->b2bCustomerCollection = $b2bCustomerCollection;
        $this->b2bCompanyFactory = $B2BCompanyFactory;
    }

    /**
     * Retrieve group name
     *
     * @return string|null
     */
    public function getB2BCompany()
    {
        $customer_data = $this->_backendSession->getCustomerData()['account'];
        $b2bCompanyName = '';
        $b2bCustomer = $this->b2bCustomerCollection->addFieldToFilter('customer_id', $customer_data['id'])->getData();
        if ($b2bCustomer) {
            $b2bCompanyName = $this->b2bCompanyFactory->create()->load($b2bCustomer[0]['company_id'])->getStoreName();
        }
        return isset($b2bCompanyName) ? $b2bCompanyName : '';
    }

}

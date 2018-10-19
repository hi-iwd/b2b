<?php

namespace IWD\B2B\Model;

class CompanyPayment extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_company_payments';

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\CompanyPayment');
    }
}

<?php

namespace IWD\B2B\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Company extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('iwd_b2b_company', 'company_id');
    }
}

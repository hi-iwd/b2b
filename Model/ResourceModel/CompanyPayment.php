<?php
     
namespace IWD\B2B\Model\ResourceModel;
 
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
 
class CompanyPayment extends AbstractDb
{
    const TBL_NAME = 'iwd_b2b_company_payments';
    
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('iwd_b2b_company_payments', 'entity_id');
    }
}
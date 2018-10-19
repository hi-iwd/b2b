<?php
     
namespace IWD\B2B\Model\ResourceModel;
 
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
 
class Role extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('iwd_b2b_roles', 'id');
    }
}
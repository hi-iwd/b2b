<?php

namespace IWD\B2B\Model;

class Role extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_roles';

    /**
     * Initialize roles model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\Role');
    }
}

<?php

namespace IWD\B2B\Model;

class RoleAccess extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_role_access';

    /**
     * Initialize role access model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\RoleAccess');
    }
}

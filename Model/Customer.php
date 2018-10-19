<?php

namespace IWD\B2B\Model;

class Customer extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_customer_info';

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\Customer');
    }
}

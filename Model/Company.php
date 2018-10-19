<?php

namespace IWD\B2B\Model;

class Company extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_company';

    /**
     * Initialize company model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\Company');
    }
}

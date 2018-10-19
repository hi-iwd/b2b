<?php

namespace IWD\B2B\Model;

class AccessSection extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_access_sections';

    /**
     * Initialize access sections model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\AccessSection');
    }
}

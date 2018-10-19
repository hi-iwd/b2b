<?php

namespace IWD\B2B\Model;

class Message extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'iwd_b2b_message';
    const CACHE_TAG     = 'b2b_message';
    protected $_cacheTag= 'b2b_message';

    /**
     * Initialize message model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('IWD\B2B\Model\ResourceModel\Message');
    }
}

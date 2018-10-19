<?php

namespace IWD\B2B\Block\Adminhtml\Company\Edit\Tab;

class ShippingTerms extends AbstractInfoTab
{
    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Shipping Terms');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Shipping Terms');
    }
}
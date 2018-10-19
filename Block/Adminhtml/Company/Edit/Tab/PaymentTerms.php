<?php

namespace IWD\B2B\Block\Adminhtml\Company\Edit\Tab;

class PaymentTerms extends AbstractInfoTab
{
    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('Payment Terms');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('Payment Terms');
    }
}

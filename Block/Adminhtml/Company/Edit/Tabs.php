<?php

namespace IWD\B2B\Block\Adminhtml\Company\Edit;

use Magento\Backend\Block\Widget\Tabs as WidgetTabs;

class Tabs extends WidgetTabs
{
    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('b2b_company_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Company Information'));
    }
}

<?php
namespace IWD\B2B\Block\Adminhtml;

class Company extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'IWD_B2B';
        $this->_controller = 'adminhtml_company';
        $this->_headerText = __('Companies');
        $this->_addButtonLabel = __('Add New Company');
        parent::_construct();
    }

}

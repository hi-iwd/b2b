<?php
namespace IWD\B2B\Block\Adminhtml\Company;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
            \Magento\Backend\Block\Widget\Context $context,
            \Magento\Framework\Registry $registry,
            array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }


    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'company_id';
        $this->_blockGroup = 'IWD_B2B';
        $this->_controller = 'adminhtml_company';
        parent::_construct();

        $this->buttonList->update('save', 'label', __('Save Company'));
        $this->buttonList->update('delete', 'label', __('Delete Company'));

        $this->buttonList->add(
            'saveandcontinue',
                [
                'label' => __('Save and Continue Edit'),
                'class' => 'save',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form']],
                    ]
                ],
                -100
        );

    }


    /**
     * Get edit form container header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        if ($this->_coreRegistry->registry('b2b_company')->getId()) {
            return __("Edit Company");
        } else {
            return __('New Company');
        }
    }
}

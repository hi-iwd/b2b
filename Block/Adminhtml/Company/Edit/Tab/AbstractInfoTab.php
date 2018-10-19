<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Block\Adminhtml\Company\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

abstract class AbstractInfoTab extends Generic implements TabInterface
{
    /**
     * Prepare label for tab
     *
     * @return string
     */
    abstract public function getTabLabel();

    /**
     * Prepare title for tab
     *
     * @return string
     */
    abstract public function getTabTitle();

    protected function _prepareForm()
    {
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Get More with B2B Suite Pro'), 'class' => 'fieldset-wide iwd-b2b-pro-info-section']
        );
        $field = $fieldset->addField(
            'info',
            'text',
            []
        );
        $renderer = $this->getLayout()->createBlock(
            'IWD\B2B\Block\Info'
        );
        $field->setRenderer($renderer);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getTabClass()
    {
        return 'b2b-pro-form-tab';
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    public function isAjaxLoaded()
    {
        return false;
    }
}
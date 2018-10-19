<?php

namespace IWD\B2B\Block\Adminhtml\Company\Edit;

/**
 * Adminhtml cms block edit form
 */
 class Form extends \Magento\Backend\Block\Widget\Form\Generic
 {
     protected function _prepareForm()
     {
         /** @var \Magento\Framework\Data\Form $form */
         $form = $this->_formFactory->create(
             ['data' => ['id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post', 'enctype' => 'multipart/form-data']]
         );
         $form->setUseContainer(true);
         $this->setForm($form);
         return parent::_prepareForm();
     }
}

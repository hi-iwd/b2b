<?php

namespace IWD\B2B\Block\Adminhtml\Company\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Cms\Model\Wysiwyg\Config;

class General extends Generic implements TabInterface
{
    /**
     * @var \Magento\Customer\Model\Config\Source\Group
     */
    protected $_customerGroup;

    protected $store;

    /**
    * @var \IWD\B2B\Helper\Data $helper
    */
    protected $helper;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \IWD\B2B\Helper\Data $helper,
        \IWD\B2B\Model\ResourceModel\Customer\CollectionFactory $B2BCustomerCollectionFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \IWD\B2B\Model\ResourceModel\Company\CollectionFactory  $b2bCompanyCollectionFactory,
        \Magento\Customer\Model\Config\Source\Group $group,
        array $data = []
    )
    {
        $this->helper = $helper;
        $this->_B2BCustomerCollectionFactory = $B2BCustomerCollectionFactory;
        $this->_customerFactory = $customerFactory;
        $this->_b2bCompanyCollectionFactory = $b2bCompanyCollectionFactory;
        $this->_customerGroup = $group;
        parent::__construct($context, $registry, $formFactory, $data);
    }

  protected function _prepareForm()
  {
    $model = $this->_coreRegistry->registry('b2b_company');

      /** @var \Magento\Framework\Data\Form $form */
      $form = $this->_formFactory->create();

      $form->setHtmlIdPrefix('');

      $fieldset = $form->addFieldset(
              'base_fieldset',
              ['legend' => __('General Information'), 'class' => 'fieldset-wide']
      );

      if ($model->getId()) {
          $fieldset->addField('company_id', 'hidden', ['name' => 'company_id']);
      }

      $fieldset->addField(
              'store_name',
              'text',
              ['name' => 'store_name', 'label' => __('Company'), 'title' => __('Company'), 'required' => true]
      );

      $fieldset->addField(
              'is_active',
              'select',
              [
              'label' => __('Status'),
              'title' => __('Status'),
              'name' => 'is_active',
              'required' => true,
              'options' => ['1' => __('Active'), '0' => __('Inactive'), '2'=> __('Pending')]
              ]
      );
      if (!$model->getId()) {
          $model->setData('is_active', '2');
      }

      $groups = $this->_customerGroup->toOptionArray();

      $grp = [];
      foreach ($groups as $gr) {
          if (!empty($gr['value']))
              $grp[$gr['value']] = $gr['label'];
      }
      $groups = $grp;

      $fieldset->addField(
              'group_id',
              'select',
              [
              'label' => __('Group'),
              'title' => __('Group'),
              'name' => 'group_id',
              'required' => true,
              'options' => $groups
              ]
      );
      if (!$model->getId()) {
          $model->setData('group_id', '1');
      }

      $fieldset->addField(
              'telephone',
              'text',
              ['name' => 'telephone', 'label' => __('Phone Number'), 'title' => __('Phone Number'), 'required' => false]
      );

      $fieldset->addField(
              'fax',
              'text',
              ['name' => 'fax', 'label' => __('Fax Number'), 'title' => __('Fax Number'), 'required' => false]
      );

      $html = "Applies to all users assigned to company";

      $fieldset->addField(
              'ssn',
              'text',
              ['name' => 'ssn', 'label' => __('Tax ID Number'), 'title' => __('Tax ID Number'), 'required' => true, 'after_element_html' => $html]
      );

      $fieldset->addField(
          'certificate',
          'image',
          [
              'name' => 'certificate',
              'label' => __('Wholesale / Resale Certificate'),
              'title' => __('Wholesale / Resale Certificate'),
              'data-form-part' => $this->getData('target_form'),
              'required' => false
          ]
      );

      $fieldset->addField(
              'fedex',
              'text',
              ['name' => 'fedex', 'label' => __('FedEx Account Number'), 'title' => __('FedEx Account Number'), 'required' => false]
      );

      $fieldset->addField(
              'ups',
              'text',
              ['name' => 'ups', 'label' => __('UPS Account Number'), 'title' => __('UPS Account Number'), 'required' => false]
      );

      $form->setValues($model->getData());
      $this->setForm($form);

      return parent::_prepareForm();
  }
    /**
     * Prepare label for tab
     *
     * @return string
     */
    public function getTabLabel()
    {
        return __('General Information');
    }

    /**
     * Prepare title for tab
     *
     * @return string
     */
    public function getTabTitle()
    {
        return __('General Information');
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

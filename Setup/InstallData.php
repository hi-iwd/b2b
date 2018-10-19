<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * We left original InstallData file for PRO version compatibility
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * Customer setup factory
     *
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;
        
    /**
     * Init
     *
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
            CustomerSetupFactory $customerSetupFactory,
            AttributeSetFactory $attributeSetFactory
            )
    {
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        // need for correct work of new attribute
        $customerEntity = $customerSetup->getEavConfig()->getEntityType('customer');
        $attributeSetId = $customerEntity->getDefaultAttributeSetId();
        
        /** @var $attributeSet AttributeSet */
        $attributeSet = $this->attributeSetFactory->create();
        $attributeGroupId = $attributeSet->getDefaultGroupId($attributeSetId);
        ///
        
        /**
         * Add btb_active attribute to the 'eav_attribute' table
         */
        // remove if exists
        $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_active');
        
        $customerSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'btb_active',
                [
                'sort_order' => 40,
                'type' => 'int',
                'label' => 'Approve B2B Account',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',  
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible' => true,
                'required' => false,
                'user_defined' => true,
                'default' => '0',
                'system' => 0, // it is not system
                'visible_on_front' => false,
                'unique' => false,
                ]
        );

        $b2bActiveAttribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_active');

        $b2bActiveAttribute->setData('attribute_set_id', $attributeSetId)
                ->setData('attribute_group_id', $attributeGroupId)
                ->setData('used_in_forms', ['adminhtml_customer']);
        
        $b2bActiveAttribute->save();
        
    }
}

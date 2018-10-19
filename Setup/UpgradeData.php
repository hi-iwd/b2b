<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * We left original Upgrade Data script for PRO version compatibility
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
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
     * @var \IWD\B2B\Model\CustomerFactory
     */
    private $b2bCustomerFactory;

    public function __construct(
            CustomerSetupFactory $customerSetupFactory,
            AttributeSetFactory $attributeSetFactory,
            \IWD\B2B\Model\CustomerFactory $b2bCustomerFactory
    ) {
        $this->b2bCustomerFactory = $b2bCustomerFactory;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->attributeSetFactory = $attributeSetFactory;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $version = $context->getVersion();

        if (version_compare($version, '2.0.6', '<')) {

            $connection = $setup->getConnection();

            // add Administrator role
            $connection->insert(
                    $setup->getTable('iwd_b2b_roles'),
                    [
                    'role_name' => 'Administrator'
                    ]
            );
            $administrator_role_id = $connection->lastInsertId($setup->getTable('iwd_b2b_roles'));

            // add Requester role
            $connection->insert(
                    $setup->getTable('iwd_b2b_roles'),
                    [
                    'role_name' => 'Requester'
                    ]
            );
            $requester_role_id = $connection->lastInsertId($setup->getTable('iwd_b2b_roles'));

            // add access sections
            $connection->insert(
                    $setup->getTable('iwd_b2b_access_sections'),
                    [
                    'section_code' => 'manage_users',
                    'section_desc' => 'Manage Sub Accounts',
                    ]
            );
            $section_id = $connection->lastInsertId($setup->getTable('iwd_b2b_access_sections'));

            // allow access to this section for Administrator
            $connection->insert(
                    $setup->getTable('iwd_b2b_role_access'),
                    [
                    'role_id' => $administrator_role_id,
                    'section_id' => $section_id
                    ]
            );


            // add access sections
            $connection->insert(
                    $setup->getTable('iwd_b2b_access_sections'),
                    [
                    'section_code' => 'full_checkout',
                    'section_desc' => 'Process orders with all payments',
                    ]
            );
            $section_id = $connection->lastInsertId($setup->getTable('iwd_b2b_access_sections'));

            // allow access to this section for Administrator
            $connection->insert(
                    $setup->getTable('iwd_b2b_role_access'),
                    [
                    'role_id' => $administrator_role_id,
                    'section_id' => $section_id
                    ]
            );

        }

        if (version_compare($version, '2.0.7', '<')) { // add new order status
            $this->addNewOrderStatus($setup);
        }

        if (version_compare($version, '2.0.11', '<')) { // add new order rejected status
            $this->addOrderRejectedStatus($setup);
        }

        if (version_compare($version, '2.1.1', '<')) { // add new order rejected status
            $this->convertPrimaryToComapnies($setup);
        }

        if (version_compare($version, '2.1.2', '<')) { // add company name field to customers
            $this->addCustomerCompanyField($setup);
        }

        if (version_compare($version, '2.1.3', '<')) { // hide B2B Active field from customer form
            $this->hideCustomerActiveField($setup);
        }

        if (version_compare($version, '2.1.4', '<')) {
            $this->removeCustomerFields($setup);
        }

        if (version_compare($version, '2.1.5', '<')) {
            $this->makeStatusNotDefault($setup);
        }

        if (version_compare($version, '2.1.8', '<')) {
            $activationData = $this->getActivationData($setup);
            $this->removeOldData($setup);
            $this->addB2bColumn($setup);
            $this->setActivationData($setup,$activationData);
        }

        $setup->endSetup();
    }

    protected function addNewOrderStatus(ModuleDataSetupInterface $setup) {

        // remove status before add
        $setup->getConnection()->delete(
                $setup->getTable('sales_order_status'),
                ['status = ?' => 'btb_pending_approval']
        );
        // remove status before add
        $setup->getConnection()->delete(
                $setup->getTable('sales_order_status_state'),
                ['status = ?' => 'btb_pending_approval']
        );

        // add status
        $data = [];
        $statuses = [
            'btb_pending_approval' => __('Pending Approval'),
        ];
        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);

        // add state
        $data = [];

        $states = [
            'new' => [
                'label' => __('Pending Approval'),
                'statuses' => ['btb_pending_approval' => ['default' => '1']],
                'visible_on_front' => true,
            ],
        ];

        foreach ($states as $code => $info) {
            if (isset($info['statuses'])) {
                foreach ($info['statuses'] as $status => $statusInfo) {
                    $data[] = [
                        'status' => $status,
                        'state' => $code,
                        'is_default' => is_array($statusInfo) && isset($statusInfo['default']) ? 1 : 0,
                        'visible_on_front' => isset($info['visible_on_front']) && $info['visible_on_front'] ? 1 : 0,
                    ];
                }
            }
        }
        $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $data
        );

    }

    protected function addOrderRejectedStatus(ModuleDataSetupInterface $setup) {
        // remove status before add
        $setup->getConnection()->delete(
                $setup->getTable('sales_order_status'),
                ['status = ?' => 'btb_rejected']
        );
        // remove status before add
        $setup->getConnection()->delete(
                $setup->getTable('sales_order_status_state'),
                ['status = ?' => 'btb_rejected']
        );

        // add status
        $data = [];
        $statuses = [
            'btb_rejected' => __('Rejected'),
        ];
        foreach ($statuses as $code => $info) {
            $data[] = ['status' => $code, 'label' => $info];
        }
        $setup->getConnection()->insertArray($setup->getTable('sales_order_status'), ['status', 'label'], $data);

        // add state
        $data = [];

        $states = [
            'canceled' => [
                'label' => __('Rejected'),
                'statuses' => ['btb_rejected' => ['default' => '0']],
                'visible_on_front' => true,
            ],
        ];

        foreach ($states as $code => $info) {
            if (isset($info['statuses'])) {
                foreach ($info['statuses'] as $status => $statusInfo) {
                    $data[] = [
                    'status' => $status,
                    'state' => $code,
                    'is_default' => is_array($statusInfo) && isset($statusInfo['default']) ? 1 : 0,
                    'visible_on_front' => isset($info['visible_on_front']) && $info['visible_on_front'] ? 1 : 0,
                    ];
                }
            }
        }
        $setup->getConnection()->insertArray(
                $setup->getTable('sales_order_status_state'),
                ['status', 'state', 'is_default', 'visible_on_front'],
                $data
        );

    }

    protected function convertPrimaryToComapnies(ModuleDataSetupInterface $setup){

        $customers = $this->b2bCustomerFactory->create()->getCollection();
        $customers->getSelect()->join(["customer_entity" => $setup->getTable('customer_entity')], "customer_entity.entity_id = main_table.customer_id");
        $customers->addFieldToFilter('role_id', ['eq'=>0]);

        foreach ($customers as $customer) {

            $customer_id = $customer->getData('customer_id');

            $company_id = $customer->getData('company_id');
            if (!empty($company_id))
                continue;

            $is_active = 1; // will set all companies active by default
            $company_status = 2;
            if($is_active == 1)
                $company_status = 1;

            $company_active_limit = 0;
            $company_credit_limit = false;
            $credit_limit = $customer->getData('credit_limit');
            if (!empty($credit_limit) || $credit_limit === 0) {
                $company_credit_limit = $credit_limit;
                $company_active_limit = 1;
            }

            $store_name = $customer->getData('store_name');

            // add new company
            $connection = $setup->getConnection();

            $company_data = [];

            $company_data['email']    = $customer->getData('email');
            $company_data['is_active']= $company_status;
            $company_data['certificate']    = $customer->getData('certificate');
            $company_data['ssn']            = $customer->getData('ssn');
            $company_data['telephone']      = '';
            $company_data['fax']            = '';
            $company_data['fedex']          = $customer->getData('fedex');
            $company_data['store_name']     = $store_name;
            $company_data['ups']            = $customer->getData('ups');
            $company_data['city']           = '';
            $company_data['country_id']     = '';
            $company_data['postcode']       = '';
            $company_data['region']         = '';
            $company_data['region_id']      = '';
            $company_data['street']         = '';
            $company_data['group_id']       = $customer->getGroupId();
            if ($company_credit_limit !== false) {
                $company_data['credit_limit']   = $company_credit_limit;
                $company_data['available_credit']    = 0;
            }
            $company_data['active_limit']    = $company_active_limit;

            // add Administrator role
            $connection->insert(
                $setup->getTable('iwd_b2b_company'),
                $company_data
            );

            $company_id = $connection->lastInsertId($setup->getTable('iwd_b2b_company'));

            // apply new company to all its employees
            $sub_users = $this->b2bCustomerFactory->create()->getCollection();
            $sub_users->addFieldToFilter('parent_id', ['eq'=> $customer_id]);

            $employees_ids = [$customer_id];

            foreach ($sub_users as $user) {
                $employees_ids[] = $user->getData('customer_id');
            }

            $setup->getConnection()->update(
                $setup->getTable('iwd_b2b_customer_info'),
                    [
                    'store_name' => $store_name,
                    'company_id' => $company_id
                    ],
                    ['customer_id IN (?)' => $employees_ids]
            );


        }

    }

    protected function addCustomerCompanyField(ModuleDataSetupInterface $setup) {

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
         * Add btb_company attribute to the 'eav_attribute' table
        */
        // remove if exists
        $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_company');

        $customerSetup->addAttribute(
                \Magento\Customer\Model\Customer::ENTITY,
                'btb_company',
                [
                'sort_order' => 50,
                'type' => 'varchar',
                'label' => 'B2B Company',
                'input' => 'text',
                'source' => '',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General Information',
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'visible' => false,
                'required' => false,
                'user_defined' => false,
                'default' => '',
                'system' => false, // it is not system
                'visible_on_front' => false,
                'unique' => false,
                ]
        );

        $b2bCompanyAttribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_company');

        $b2bCompanyAttribute->setData('attribute_set_id', $attributeSetId)
            ->setData('attribute_group_id', $attributeGroupId);

        $b2bCompanyAttribute->save();
    }

    protected function hideCustomerActiveField(ModuleDataSetupInterface $setup) {

        $setup->startSetup();
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $attributeData = [
            'is_used_in_grid' => false,
            'is_visible_in_grid' => false,
            'is_filterable_in_grid' => false,
            'is_searchable_in_grid' => false,
            'user_defined' => false,
            'visible' => false,
        ];

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'btb_active');
        foreach ($attributeData as $key => $value) {
            $attribute->setData($key, $value);
        }
        $attribute->save();

        // exclude from form
        $b2bActiveAttribute = $customerSetup->getEavConfig()->getAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_active');
        $b2bActiveAttribute->setData('used_in_forms', [])
            ->setData("is_user_defined", 0)
            ->setData("is_visible", 0);
        $b2bActiveAttribute->save();
    }

    protected function removeCustomerFields(ModuleDataSetupInterface $setup)
    {
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'store_name');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'ssn');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'certificate');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'number_location');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'fedex');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'ups');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'credit_limit');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'available_credit');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'image');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'is_active');
    }

    protected function makeStatusNotDefault(ModuleDataSetupInterface $setup)
    {
        // change b2b pending state
        $setup->getConnection()->update(
                $setup->getTable('sales_order_status_state'),
                    [
                    'is_default' => 0,
                    ],
                    ['status = ?' => 'btb_pending_approval']
        );

    }

    protected function getActivationData(ModuleDataSetupInterface $setup){
        $activationData = array();
        $customers = $this->b2bCustomerFactory->create()->getCollection();
        $customers->getSelect()->join(["customer_entity" => $setup->getTable('customer_entity')], "customer_entity.entity_id = main_table.customer_id");
        foreach ($customers as $customer) {
            $activationData[$customer->getData('entity_id')] = $customer->getData('btb_active');
        }
        return $activationData;
    }

    protected function removeOldData(ModuleDataSetupInterface $setup){
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);
        $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_active');
        $customerSetup->removeAttribute(\Magento\Customer\Model\Customer::ENTITY, 'btb_company');
    }

    protected function addB2bColumn(ModuleDataSetupInterface $setup){
        $setup->getConnection()->addColumn(
            $setup->getTable('iwd_b2b_customer_info'),
            'btb_active',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                'default' => 0,
                'nullable' => false,
                'comment' => 'Status'
            ]
        );
    }

    protected function setActivationData(ModuleDataSetupInterface $setup,$activationData){
        $customers = $this->b2bCustomerFactory->create()->getCollection();
        foreach ($customers as $customer) {
            $customer_id = $customer->getData('customer_id');
            if(isset($activationData[$customer_id])){
                $customer->setData('btb_active',$activationData[$customer_id]);
            }
        }
        $customers->save();
    }

}

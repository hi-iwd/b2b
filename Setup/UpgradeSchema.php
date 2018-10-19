<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * We left original Upgrade Schema script for PRO version compatibility
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->removeFileCategoryField($setup);
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->removeFileTypeTable($setup);
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->addFileCategoriestable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.1', '<')) {
            $this->addProductgridTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $this->modifyProductgridTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.3', '<')) {
            $this->addParentId($setup);
        }
        if (version_compare($context->getVersion(), '2.0.4', '<')) {
            $this->addCreditLimit($setup);
        }
        if (version_compare($context->getVersion(), '2.0.5', '<')) {
            $this->addAvailableCredit($setup);
        }

        if (version_compare($context->getVersion(), '2.0.6', '<')) {
            $this->addRoles($setup);
        }

        if (version_compare($context->getVersion(), '2.0.8', '<')) {
            $this->addWhoMadeHistoryTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.9', '<')) {
            $this->addApproveOperationsTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.10', '<')) {
            $this->modifyApproveOperationsTable($setup);
        }

        if (version_compare($context->getVersion(), '2.0.12', '<')) {
            $this->addCustomLogicDownloadCenter($setup);
        }

        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            $this->addCompanyTable($setup);
            $this->addCustomerCompanyColumns($setup);
            $this->addOrderGridCompanyName($setup);
        }

        if (version_compare($context->getVersion(), '2.1.6', '<')) {
            $this->companyPaymentMethods($setup);
        }

        if (version_compare($context->getVersion(), '2.2.1', '<')) {
            $this->companyShippingsMethods($setup);
        }

        $setup->endSetup();
    }

    protected function removeFileCategoryField(SchemaSetupInterface $setup) {

        $setup->getConnection()->dropForeignKey(
                $setup->getTable('iwd_b2b_file'),
                $setup->getFkName(
                        'iwd_b2b_file',
                        'category_id',
                        'iwd_b2b_category',
                        'entity_id'
                )
        );

        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_file'), 'category_id');
    }

    /**
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function addFileCategoriesTable(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'iwd_b2b_file_category'
         */
        $setup->getConnection()->dropTable('iwd_b2b_file_category');

        $table = $setup->getConnection()
            ->newTable($setup->getTable('iwd_b2b_file_category'))
            ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Record entity ID'
            )
            ->addColumn(
                    'file_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'File Entry ID'
            )
            ->addColumn(
                    'category_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Category Entry ID'
            )
            ->addIndex(
                    $setup->getIdxName('iwd_b2b_file_category', ['file_id']),
                    ['file_id']
            );

        $setup->getConnection()->createTable($table);
    }

    protected function removeFileTypeTable(SchemaSetupInterface $setup) {

        // remove old field
        $setup->getConnection()->dropForeignKey(
                $setup->getTable('iwd_b2b_file'),
                $setup->getFkName(
                        'iwd_b2b_file',
                        'type_id',
                        'iwd_b2b_type',
                        'entity_id'
                )
        );

        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_file'), 'type_id');

        // add new field
        $setup->getConnection()->addColumn(
                $setup->getTable('iwd_b2b_file'),
                'type',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 10,
                'nullable' => false,
                'default' => '',
                'comment' => 'Media file type'
                ]
        );

        // remove file type table, type will be saved in files table as string
        $setup->getConnection()->dropTable('iwd_b2b_type');
    }

    /**
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function addProductgridTable(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'iwd_b2b_productgrid_columns'
         */
        $setup->getConnection()->dropTable('iwd_b2b_productgrid_columns');

        $table = $setup->getConnection()
            ->newTable($setup->getTable('iwd_b2b_productgrid_columns'))
            ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'Record entity ID'
            )
            ->addColumn(
                    'user_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'User Entry ID'
            )
            ->addColumn(
                    'type',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    10,
                    ['nullable' => false],
                    'Column Type'
            )
            ->addColumn(
                    'item',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    15,
                    ['nullable' => false],
                    'Item'
            )
            ->addColumn(
                    'order',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    11,
                    ['unsigned' => true, 'nullable' => false, 'default' => 0],
                    'Order'
            );

        $setup->getConnection()->createTable($table);
    }

    /**
     *
     * @param SchemaSetupInterface $setup
     * @return void
     */
    protected function modifyProductgridTable(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_productgrid_columns'), 'user_id');

        // add new field
        $setup->getConnection()->addColumn(
            $setup->getTable('iwd_b2b_productgrid_columns'),
                'grid_name',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 15,
                'nullable' => false,
                'default' => '',
                'comment' => 'Table type'
                ]
        );

    }

    protected function addParentId(SchemaSetupInterface $setup) {
       // Get module table
        $tableName = $setup->getTable('iwd_b2b_customer_info');

        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            // Declare data
            $columns = [
                'parent_id' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'default' => 0,
                    'nullable' => false,
                    'unsigned' => true,
                    'comment' => 'Parent ID'
                ],
            ];

            $connection = $setup->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($tableName, $name, $definition);
            }

        }
    }

    protected function addCreditLimit(SchemaSetupInterface $setup) {
        // Get module table
        $tableName = $setup->getTable('iwd_b2b_customer_info');

        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            // Declare data
            $columns = [
                'credit_limit' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,2',
                    ['nullable' => false, 'default' => '0.00'],
                    'used_in_forms' => ['adminhtml_customer'],
                    'visible' => true,
                    'system' => 0,
                    'comment' => 'Credit Limit'
                ],
            ];

            $connection = $setup->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($tableName, $name, $definition);
            }

        }
    }

    protected function addAvailableCredit(SchemaSetupInterface $setup) {
        // Get module table
        $tableName = $setup->getTable('iwd_b2b_customer_info');

        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            // Declare data
            $columns = [
                'available_credit' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                    'length' => '12,2',
                    ['nullable' => false, 'default' => '0.00'],
                    'visible' => true,
                    'system' => 0,
                    'comment' => 'Available Credit'
                ],
            ];

            $connection = $setup->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($tableName, $name, $definition);
            }

        }
    }

    protected function addRoles(SchemaSetupInterface $setup) {
        /**
         * Create table 'iwd_b2b_roles'
         */
        $setup->getConnection()->dropTable('iwd_b2b_roles');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_roles'))
        ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record entity ID'
        )
        ->addColumn(
                'role_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false],
                'Role Name'
        );

        $setup->getConnection()->createTable($table);


        /**
         * Create table 'iwd_b2b_access_sections'
         */
        $setup->getConnection()->dropTable('iwd_b2b_access_sections');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_access_sections'))
        ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record entity ID'
        )
        ->addColumn(
                'section_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                100,
                ['nullable' => false],
                'Section Code'
        )
        ->addColumn(
                'section_desc',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => false],
                'Section Description'
        );

        $setup->getConnection()->createTable($table);


        /**
         * Create table 'iwd_b2b_role_access'
         */
        $setup->getConnection()->dropTable('iwd_b2b_role_access');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_role_access'))
        ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record entity ID'
        )
        ->addColumn(
                'role_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Role ID'
        )
        ->addColumn(
                'section_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Section ID'
        );

        $setup->getConnection()->createTable($table);

        // add role column
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'role_id');

        // add new field
        $setup->getConnection()->addColumn(
                $setup->getTable('iwd_b2b_customer_info'),
                'role_id',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'length' => 15,
                'unsigned' => true,
                'nullable' => false,
                'default' => '0',
                'comment' => 'Role Id'
                ]
        );

    }

    protected function addWhoMadeHistoryTable(SchemaSetupInterface $setup) {
        /**
         * Create table 'iwd_b2b_history'
         */
        $setup->getConnection()->dropTable('iwd_b2b_history');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_history'))
        ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record entity ID'
        )
        ->addColumn(
                'history_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'History ID'
        )
        ->addColumn(
                'who_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'User id'
        )
        ->addColumn(
                'history_table',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false, 'default' => 'sales_order_status_history'],
                'History Table'
        )
        ->addIndex(
                $setup->getIdxName('iwd_b2b_history', ['history_id']),
                ['history_id']
        );

        $setup->getConnection()->createTable($table);

    }

    protected function addApproveOperationsTable(SchemaSetupInterface $setup) {
        /**
         * Create table 'iwd_b2b_operations'
         */
        $setup->getConnection()->dropTable('iwd_b2b_operations');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_operations'))
        ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record entity ID'
        )
        ->addColumn(
                'customer_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'User id'
        )
        ->addColumn(
                'operation',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false, 'default' => ''],
                'Operation Name'
        )
        ->addColumn(
                'data_type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false, 'default' => ''],
                'Record Type'
        )
        ->addColumn(
                'new_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'New ID'
        )
        ->addColumn(
                'old_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Old ID'
        )
        ->addIndex(
                $setup->getIdxName('iwd_b2b_operations', ['customer_id']),
                ['customer_id']
        );

        $setup->getConnection()->createTable($table);

    }

    protected function modifyApproveOperationsTable(SchemaSetupInterface $setup) {
        // add addon column
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_operations'), 'addon');

        // add new field
        $setup->getConnection()->addColumn(
                $setup->getTable('iwd_b2b_operations'),
                'addon',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 1000,
                'comment' => 'Additional Info'
                ]
        );


        /**
         * Create table 'iwd_b2b_user_orders'
         */
        $setup->getConnection()->dropTable('iwd_b2b_user_orders');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_user_orders'))
        ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Record entity ID'
        )
        ->addColumn(
                'order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Order ID'
        )
        ->addColumn(
                'old_order_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Old Order ID'
        )
        ->addColumn(
                'new_customer',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'New User id'
        )
        ->addColumn(
                'old_customer',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Old User id'
        )
        ->addColumn(
                'operation',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                150,
                ['nullable' => false, 'default' => 'approved'],
                'Record Type'
        )
        ->addIndex(
                $setup->getIdxName('iwd_b2b_user_orders', ['order_id']),
                ['order_id']
        );

        $setup->getConnection()->createTable($table);
    }

    protected function addCustomLogicDownloadCenter (SchemaSetupInterface $setup){

        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_file'), 'download_file_type');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_file'), 'download_url_path');

        // add new field Max
        $setup->getConnection()->addColumn(
                $setup->getTable('iwd_b2b_file'),
                'download_file_type',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => '',
                'comment' => 'Download file type'
                ]
        );
        $setup->getConnection()->addColumn(
                $setup->getTable('iwd_b2b_file'),
                'download_url_path',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'default' => '',
                'comment' => 'Download file url'
                ]
        );

    }


    protected function addCompanyTable(SchemaSetupInterface $setup) {
        /**
         * Create table 'iwd_b2b_company'
         */
        $setup->getConnection()->dropTable('iwd_b2b_company');

        $table = $setup->getConnection()
        ->newTable($setup->getTable('iwd_b2b_company'))
        ->addColumn(
                'company_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
        )->addColumn(
                'email',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Email'
        )->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 2],
                'Status'
        )->addColumn(
                'image',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                1000,
                ['nullable' => true],
                'Logo image path'
        )->addColumn(
                'certificate',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Certificate'
        )->addColumn(
                'ssn',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                245,
                ['nullable' => false],
                'SSN'
        )->addColumn(
                'telephone',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                245,
                ['nullable' => false],
                'Phone Number'
        )->addColumn(
                'fax',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Fax'
        )->addColumn(
                'fedex',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'FedEx'
        )->addColumn(
                'store_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'Store Name'
        )->addColumn(
                'ups',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'UPS'
        )->addColumn(
                'city',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'City'
        )->addColumn(
                'country_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Country'
        )->addColumn(
                'postcode',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Zip/Postal Code'
        )->addColumn(
                'region',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'State/Province'
        )->addColumn(
                'region_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Region ID'
        )->addColumn(
                'street',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Street'
        )->addColumn(
                'group_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 1],
                'Group ID'
        )->addColumn(
                'credit_limit',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => true, 'default' => null],
                'Credit Limit'
        )->addColumn(
                'available_credit',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => true, 'default' => null],
                'Available Credit'
        )->addColumn(
                'active_limit',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'Credit Limit Status'
        );

        $setup->getConnection()->createTable($table);

    }

    protected function addCustomerCompanyColumns(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'is_active');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'image');
        $setup->getConnection()->dropColumn($setup->getTable('iwd_b2b_customer_info'), 'company_id');

        $setup->getConnection()->addColumn(
            $setup->getTable('iwd_b2b_customer_info'),
                'is_active',
                [
                  'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                  null,
                  ['nullable' => false, 'default' => 2],
                  'comment' => 'Status'
                ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('iwd_b2b_customer_info'),
                'image',
                [
                  'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                  'length' => 1000,
                  'nullable' => true,
                  'comment' => 'Logo image path'
                ]
        );

        $setup->getConnection()->addColumn(
            $setup->getTable('iwd_b2b_customer_info'),
                'company_id',
                [
                  'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                  null,
                  ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                  'comment' => 'ID'
                ]
        );
    }

    protected function addOrderGridCompanyName(SchemaSetupInterface $setup)
    {
        $setup->getConnection()->dropColumn($setup->getTable('sales_order_grid'), 'company_name');

        $setup->getConnection()->addColumn(
            $setup->getTable('sales_order_grid'),
                'company_name',
                [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'default' => null],
                'comment' => 'Company Name'
                ]
        );
    }

    protected function companyPaymentMethods(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'iwd_b2b_company_payments'
         */
        $setup->getConnection()->dropTable('iwd_b2b_company_payments');

        $table = $setup->getConnection()
            ->newTable($setup->getTable('iwd_b2b_company_payments'))
            ->addColumn(
                    'entity_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                    'ID'
            )->addColumn(
                    'comp_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['unsigned' => true, 'nullable' => false],
                    'Company'
            )->addColumn(
                    'payment_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'Payment Method Code'
            );

        $setup->getConnection()->createTable($table);
    }

    protected function companyShippingsMethods(SchemaSetupInterface $setup)
    {
        /**
         * Create table 'iwd_b2b_company_shippings'
         */
        $setup->getConnection()->dropTable('iwd_b2b_company_shippings');

        $table = $setup->getConnection()
            ->newTable($setup->getTable('iwd_b2b_company_shippings'))
            ->addColumn(
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )->addColumn(
                'comp_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Company'
            )->addColumn(
                'shipping_code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => false, 'default' => ''],
                'Shipping Method Code'
            );

        $setup->getConnection()->createTable($table);

    }

}

<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * We left original InstallSchema file for PRO version compatibility
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Create table 'iwd_b2b_category'
         */
        $installer->getConnection()->dropTable('iwd_b2b_category');
        
        $table = $installer->getConnection()->newTable(
                $installer->getTable('iwd_b2b_category')
        )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
        )->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Name'
        );

        $installer->getConnection()->createTable($table);


        /**
         * Create table 'iwd_b2b_message'
         */
        $installer->getConnection()->dropTable('iwd_b2b_message');
        
        $table = $installer->getConnection()->newTable(
                $installer->getTable('iwd_b2b_message')
        )->addColumn(
                'message_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
        )->addColumn(
                'group_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Group Id'                
        )->addColumn(
                'message',
                Table::TYPE_TEXT,
                null,
                [],
                'Message'
        )->addColumn(
                'is_active',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true, 'default' => null],
                'Is Active'
        );
        
        $installer->getConnection()->createTable($table);
        
        
        /**
         * Create table 'iwd_b2b_file'
         */
        $installer->getConnection()->dropTable('iwd_b2b_file');
        
        $table = $installer->getConnection()->newTable(
                $installer->getTable('iwd_b2b_file')
        )->addColumn(
                'file_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'File Id'
        )->addColumn(
                'title',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Title'
        )->addColumn(
                'size',
                Table::TYPE_TEXT,
                45,
                ['nullable' => true, 'default' => null],
                'Size'
        )->addColumn(
                'is_active',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => true, 'default' => null],
                'isActive'
        )->addColumn(
                'mode',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => 0],
                'isActive'
        )->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Name'                        
        );
        
        $installer->getConnection()->createTable($table);
        

        /**
         * Create table 'iwd_b2b_file_product'
         */
        $installer->getConnection()->dropTable('iwd_b2b_file_product');
        
        $table = $installer->getConnection()->newTable(
                $installer->getTable('iwd_b2b_file_product')
        )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
        )->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Product Id'
        )->addColumn(
                'file_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'File Id'
        );
        
        $installer->getConnection()->createTable($table);
        
        
        /**
         * Create table 'iwd_b2b_customer_info'
         */
        $installer->getConnection()->dropTable('iwd_b2b_customer_info');
        
        $table = $installer->getConnection()->newTable(
                $installer->getTable('iwd_b2b_customer_info')
        )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
        )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false],
                'Customer Id'
        )->addColumn(
                'store_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Store Name'
        )->addColumn(
                'ssn',
                Table::TYPE_TEXT,
                245,
                ['nullable' => false],
                'SSN'
        )->addColumn(
                'certificate',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'Certificate'
        )
        ->addColumn(
                'number_location',
                Table::TYPE_TEXT,
                45,
                ['nullable' => true, 'default' => null],
                'Accounts Email'
        )->addColumn(
                'fedex',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'FedEx'
        )->addColumn(
                'ups',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true, 'default' => null],
                'UPS'
        )->addForeignKey(
                $installer->getFkName('iwd_b2b_customer_info', 'customer_id', 'customer_entity', 'entity_id'),
                'customer_id',
                $installer->getTable('customer_entity'),
                'entity_id',
                Table::ACTION_CASCADE,
                Table::ACTION_NO_ACTION
        );
        
        $installer->getConnection()->createTable($table);
        
        $installer->endSetup();

    }
}

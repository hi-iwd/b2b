<?php
/**
 * Created by PhpStorm.
 * User: vlad_
 * Date: 09.02.2018
 * Time: 17:46
 */

namespace IWD\B2B\Plugin\Customer\ResourceModel\Customer\Grid;

use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;
use Magento\Framework\Registry;

class Collection
{

    private $registry;
    private $resourceConnection;

    public function __construct(
        Registry $registry,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        $this->registry = $registry;
        $this->resourceConnection = $resource;
    }

    public function beforeLoad(CustomerGridCollection $subject)
    {
//        $subject->addFilterToMap('b2b_company_join.store_name', 'store_name')
        $select = $subject->getSelect();
        $joins = $select->getPart('from');
        if (!isset($joins['b2b_customer_info_join'])) {
            $select->joinLeft(
                ['b2b_customer_info_join' => $this->resourceConnection->getTableName('iwd_b2b_customer_info')],
                'main_table.entity_id = b2b_customer_info_join.customer_id',
                [])
                ->joinLeft(
                    ['b2b_company_join' => $this->resourceConnection->getTableName('iwd_b2b_company')],
                    'b2b_customer_info_join.company_id = b2b_company_join.company_id',
                    ['store_name' => 'b2b_company_join.store_name']);
        }
        return null;
    }
}

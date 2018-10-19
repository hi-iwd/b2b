<?php

namespace IWD\B2B\Block\Adminhtml\Company\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\TestFramework\Event\Magento;
use Magento\Framework\Registry;
use \IWD\B2B\Model\Company as B2BCompany;
use \IWD\B2B\Controller\Adminhtml\Company\RegistryConstants;

class Orders extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_customerCollectionFactory;

    protected $_b2bCompanyFactory;

    protected $_B2BCustomerFactory;

    protected $_B2BCustomerCollectionFactory;

    protected $_storeFactory;

    protected $_orderCollectionFactory;

    protected $_orderStatusFactory;
    
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \IWD\B2B\Model\ResourceModel\Customer\CollectionFactory $B2BCustomerCollectionFactory,
        \IWD\B2B\Model\CustomerFactory $B2BCustomerFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        Registry $registry,
        \Magento\Store\Model\System\StoreFactory $storeFactory,
        \Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory $collectionFactory,
        \Magento\Sales\Helper\Reorder $salesReorder,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Sales\Model\Order\Status $orderStatusFactory,    
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->_B2BCustomerCollectionFactory = $B2BCustomerCollectionFactory;
        $this->_B2BCustomerFactory = $B2BCustomerFactory;
        $this->_customerFactory = $customerFactory;
        $this->_registry = $registry;
        $this->_storeFactory = $storeFactory;
        $this->_salesReorder = $salesReorder;
        $this->_collectionFactory = $collectionFactory;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->_orderStatusFactory = $orderStatusFactory;
    }
    /**
     * Set grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('b2b_company_orders');
        $this->setDefaultSort('created_at', 'desc');
        $this->setUseAjax(true);
//        $this->setDefaultFilter(['is_assigned' => 1]);
    }


    protected function _prepareCollection()
    {
        $collection = $this->_orderCollectionFactory->create()
        ->addExpressionFieldToSelect(
                'fullname',
                'CONCAT({{customer_firstname}}, \' \', {{customer_lastname}})',
                ['customer_firstname' => 'main_table.customer_firstname', 'customer_lastname' => 'main_table.customer_lastname']);

        $collectionTemp = $this->_orderCollectionFactory->create();
        $B2BCustomer = $this->_B2BCustomerFactory->create()->getCollection();
        $companyID= $this->getRequest()->getParam('company_id');
        $B2BCustomer->addFieldToFilter('company_id', ['eq' => $companyID]);

        foreach ($B2BCustomer as $value) {
            $collectionTemp->addFieldToFilter('customer_id', ['neq' => $value->getData('customer_id')]);
        }

        foreach ($collectionTemp as $value) {
            $collection->addFieldToFilter('customer_id', ['neq' => $value->getData('customer_id')]);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', ['header' => __('Order Number'), 'width' => '100', 'index' => 'increment_id']);

        $this->addColumn(
            'created_at',
            ['header' => __('Purchased Date'), 'index' => 'created_at', 'type' => 'datetime']
        );

        $this->addColumn('fullname',
        [
        'header' => __('Bill-to-Name'),
        'index' => 'fullname',
        'filter_index' => 'customer_lastname'
        ]);

        $this->addColumn(
            'grand_total',
            [
                'header' => __('Order Total'),
                'index' => 'grand_total',
                'type' => 'currency',
                'currency' => 'order_currency_code'
            ]
        );
      
        $statuses = [];
        $statuses[] = '';
        $collection = $this->_orderStatusFactory->getCollection();
        foreach($collection as $st){
            $statuses[$st->getData('status')] = $st->getData('label');
        }
        
        $this->addColumn('status', 
                [
                'header' => __('Order Status'), 
                'index' => 'status',
                'type' => 'options',
                'options'   =>  $statuses,
                ]
        );
        
        return parent::_prepareColumns();
    }

    /**
     * Retrieve grid URL
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getData(
            'grid_url'
        ) ? $this->getData(
            'grid_url'
        ) : $this->getUrl(
            'b2b/company/orders',
            ['_current' => true]
        );
    }

    /**
     * Retrieve the Url for a specified sales order row.
     *
     * @param \Magento\Sales\Model\Order|\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $row->getId()]);
    }

    public function canShowTab()
    {
        return true;
    }

    public function isHidden()
    {
        return false;
    }

    public function isAjaxLoaded()
    {
        return false;
    }
}

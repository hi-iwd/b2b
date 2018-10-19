<?php

namespace IWD\B2B\Block\Adminhtml\Company;
use IWD\B2B\Model\ResourceModel\Company\CollectionFactory as B2BCompanyFactory;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \IWD\B2B\Model\ResourceModel\company\CollectionFactory
     */
    protected $_b2bCompanyFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
            \Magento\Backend\Block\Template\Context $context,
            \Magento\Backend\Helper\Data $backendHelper,
            B2BCompanyFactory $b2bCompanyFactory,
            array $data = []
    ) {
        $this->_b2bCompanyFactory = $b2bCompanyFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('companyBlockGrid');
        $this->setDefaultSort('store_name');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('company_filter');
    }


    protected function _prepareCollection() {

        $collection = $this->_b2bCompanyFactory->create();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        /*$this->addColumn(
            'company_id',
                [
                'header' => __('ID'),
                'index' => 'company_id',
                ]
        );*/

        $this->addColumn(
            'store_name',
                [
                'header' => __('Company Name'),
                'index' => 'store_name',
                ]
        );

        $this->addColumn(
            'is_active',
            [
                'header' => __('Status'),
                'width'     =>  '130',
                'index' => 'is_active',
                'type' => 'options',
                'options'   => [
                    0 => __('Inactive'),
                    1 => __('Active'),
                    2 => __('Pending'),
                ],
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData(
                'grid_url'
        ) ? $this->_getData(
                'grid_url'
        ) : $this->getUrl(
                'b2b/company/search'
        );
    }

    /**
     * @param \IWD\B2B\Model\Company\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', ['company_id' => $row->getId()]);
    }

}

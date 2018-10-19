<?php
namespace IWD\B2B\Model\System\Config\Source;

class Segment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $_options;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options[] = ['value'=>'', 'label'=> __('None')];
            $this->_options[] = ['value'=>'cms_index_index', 'label'=>__("Home page")];
            $this->_options[] = ['value'=>'category', 'label'=>__("Categories")];
            $this->_options[] = ['value'=>'product', 'label'=>__("Products")];
            $this->_options[] = ['value'=>'static', 'label'=>__("Static Pages")];
        }
        return $this->_options;
    }
  
}
<?php
namespace IWD\B2B\Model\System\Config\Source;

class RedirectPages implements \Magento\Framework\Option\ArrayInterface
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
            $this->_options[] = ['value'=>'home', 'label'=>__("Home page")];
            $this->_options[] = ['value'=>'dashboard', 'label'=>__("Dashboard")];
            $this->_options[] = ['value'=>'custom', 'label'=>__("Custom Url")];
        }
        return $this->_options;
    }
  
}
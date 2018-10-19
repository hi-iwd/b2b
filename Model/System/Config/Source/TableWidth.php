<?php
namespace IWD\B2B\Model\System\Config\Source;

class TableWidth
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
    
        $options[] = ['value'=>'full', 'label'=>__("Enable full page width")];
        $options[] = ['value'=>'fixed', 'label'=>__("Enable fixed width (1000px)")];
        $options[] = ['value'=>'auto', 'label'=>__("Enable auto-width")];
    
        return $options;
    }    
}

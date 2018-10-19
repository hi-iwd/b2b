<?php
namespace IWD\B2B\Model\System\Config\Source;

class Align
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
    
        $options[] = ['value'=>'left', 'label'=>__("Left")];
        $options[] = ['value'=>'right', 'label'=>__("Right")];
        $options[] = ['value'=>'center', 'label'=>__("Middle")];
    
        return $options;
    }    
}

<?php
namespace IWD\B2B\Model\System\Config\Source;

class Addtocart
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
    
        $options[] = ['value'=>'auto', 'label'=>__("Automatically Add to Cart")];
        $options[] = ['value'=>'manual', 'label'=>__("Manually Add to Cart")];
    
        return $options;
    }    
}

<?php
namespace IWD\B2B\Model\System\Config\Source;

class Message
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $factory = $objectManager->get('Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory');
        
        $collection = $factory->create()->addFieldToFilter(
                'frontend_input',
                ['eq' => 'textarea']
        );
        
        $options = [];
        $options[] = ['value'=>'', 'label'=>''];
        foreach ($collection as $item) {
            $options[] = ['value'=>$item->getData('attribute_code'), 'label'=>$item->getData('frontend_label')];
        }
        
        return $options;
    }
    
  
}
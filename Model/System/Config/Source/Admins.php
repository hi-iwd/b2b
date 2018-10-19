<?php
namespace IWD\B2B\Model\System\Config\Source;

class Admins implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        $users = [];
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $_collection = $objectManager->get('Magento\User\Model\ResourceModel\User\Collection');
       
        $users[] = ['value'=>'', 'label'=>__('None')];
        foreach ($_collection as $user) {
            $users[] = ['value'=>$user->getId(), 'label'=>$user->getFirstname() . ' ' . $user->getLastname()];
        }
        
        return $users;
    }
        
}
<?php
namespace IWD\B2B\Helper;

class Roles extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \IWD\B2B\Helper\Data
     */
    protected $_helper;
    
    /**
     * @var \IWD\B2B\Model\RoleFactory
     */
    protected $_roles;
    
    /**
     * @var \IWD\B2B\Model\RoleAccessFactory
     */
    protected $_roleaccess;
    
    /**
     * @var \IWD\B2B\Model\AccessSectionFactory
     */
    protected $_sections;
    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \IWD\B2B\Helper\Data $helper,
            \IWD\B2B\Model\RoleAccessFactory $roleaccess,
            \IWD\B2B\Model\AccessSectionFactory $sections,
            \IWD\B2B\Model\RoleFactory $roles
    ) {
        parent::__construct($context);

        $this->_helper = $helper;
        $this->_roles = $roles;
        $this->_roleaccess = $roleaccess;
        $this->_sections = $sections;
    }

    public function gerRoles() {
        $collection = $this->_roles->create()->getCollection();
        //        $collection = $collection->addFieldToSort($params['attribute'], $params['sort']);
        $collection->setOrder('role_name','ASC');
    
        return $collection;
    }
    
    /**
     * get customer role id
     */
    public function getRole($customer_id = false) {
        $model = $this->_helper->isB2BUser($customer_id);
        if (!$model)
            return false;
    
        $parent_id = $model->getParentId();
        $role_id = $model->getRoleId();
    
        if (empty($parent_id)) // primary contact has access to all
            return 0;
    
        if (empty($role_id))
            return false;
    
        return $role_id;
    }
    
    /**
     * check if user is requester role
     */
    public function isRequesterRole() {
        $role_id = $this->getRole();
        if ($role_id === 2 || $role_id == 2)
            return true;
    
        return false;
    }
    
    /**
     * check user access to some b2b section
     */
    public function checkRoleAccess($section, $customer_id = false) {
    
        // check b2b active for current store
        if (!$this->_helper->isEnable())
            return false;
    
        $_customer = false;
        if ($customer_id) {
            $_customer = $this->_helper->getCustomerInfo($customer_id);
        }
    
        $error = $this->_helper->checkB2BCustomerAccess($_customer);
        if ($error && is_array($error))
            return false;
        /////////
    
        // check b2b role access
        $role_id = $this->getRole();
        if ($role_id === false)
            return false;
    
        if ($role_id === 0)  // primary contact has access to all
            return true;
    
        // get section id
        $sec = $this->_sections->create();
        $sec = $sec->load($section, 'section_code');
    
        if (!$sec || $sec->getId() == null)
            return false;
    
        $section_id = $sec->getId();
    
        $collection = $this->_roleaccess->create()->getCollection();
        $collection->addFieldToFilter('role_id', ['eq'=>$role_id]);
        $collection->addFieldToFilter('section_id', ['eq'=>$section_id]);
    
        if (count($collection)>0)
            return true;
    
        return false;
    }
    
    /**
     * check access for all users (allow for all non b2b, restrict for specific b2b)
     */
    public function detectSectionAccess($section, $customer_id = false) {
        
        $is_not_b2b = $this->_helper->isNotB2B($customer_id);
        if ($is_not_b2b)
            return true;

        // check b2b role access
        $role_id = $this->getRole();
        if ($role_id === false)
            return false;
    
        if ($role_id === 0)  // primary contact has access to all
            return true;
    
        // get section id
        $sec = $this->_sections->create();
        $sec = $sec->load($section, 'section_code');
    
        if (!$sec || $sec->getId() == null)
            return false;
    
        $section_id = $sec->getId();
    
        $collection = $this->_roleaccess->create()->getCollection();
        $collection->addFieldToFilter('role_id', ['eq'=>$role_id]);
        $collection->addFieldToFilter('section_id', ['eq'=>$section_id]);
    
        if (count($collection)>0)
            return true;
    
        return false;
    
    }

    public function isGuest(){
        $is_logged = $this->_helper->isLoggedIn();
        if(!$is_logged)
            return true;
        return false;
    }
}

<?php
namespace IWD\B2B\Helper;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;

class Approve extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerDataFactory;
    
    /**
     * @var \Magento\Customer\Model\Customer\Mapper
     */
    protected $customerMapper;
    
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    protected $dataObjectHelper;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Model\Customer\Mapper $customerMapper,
        DataObjectHelper $dataObjectHelper

    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->customerDataFactory = $customerDataFactory;
        $this->customerMapper = $customerMapper;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    public function getCustomerData($customerId, $new_data = false) {
    
        $customerFactory = $this->customerDataFactory->create();
        $savedCustomerData = $this->customerRepository->getById($customerId);
        $customerData = $this->customerMapper->toFlatArray($savedCustomerData);
    
        if(!empty($new_data)){
            foreach($new_data as $key => $val)
                $customerData[$key] = $val;
        }
            
        $this->dataObjectHelper->populateWithArray(
                $customerFactory,
                $customerData,
                '\Magento\Customer\Api\Data\CustomerInterface'
        );
    
        return $customerFactory;
    }
}

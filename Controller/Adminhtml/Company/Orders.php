<?php
    namespace IWD\B2B\Controller\Adminhtml\Company;

    use Magento\Framework\Registry;

    class Orders extends \Magento\Backend\App\Action
    {
        /**
         * @var \Magento\Framework\View\Result\LayoutFactory
         */
        protected $resultLayoutFactory;

        protected $_adminUser;

        protected $_userFactory;

        protected $_registry;

        /**
         * @param \Magento\Backend\App\Action\Context $context
         * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
         * @param \Magento\User\Model\UserFactory $userFactory
         * @param Registry $registry
         * @param \Magento\Catalog\Controller\Adminhtml\Product\Builder $productBuilder
         */
        public function __construct(
            \Magento\Backend\App\Action\Context $context,
            \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
            \Magento\User\Model\UserFactory $userFactory,
            Registry $registry
        ) {
            parent::__construct($context);
            $this->_registry = $registry;
            $this->resultLayoutFactory = $resultLayoutFactory;
            $this->_userFactory = $userFactory;
        }

        public function execute()
        {
            $resultLayout = $this->resultLayoutFactory->create();
            $resultLayout->getLayout()->getBlock('b2b.company.orders')
    //            ->setProductsRelated($this->getRequest()->getPost('products_related', null))
            ;
            return $resultLayout;
        }
    }

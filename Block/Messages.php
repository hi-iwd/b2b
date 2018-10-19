<?php
namespace IWD\B2B\Block;

use Magento\Framework\Message\MessageInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Framework\View\Element\Template;

class Messages extends \Magento\Framework\View\Element\Messages
{
    public $_mode = 1;
    
    private $_helper;
    
    /**
     * @var InterpretationStrategyInterface
     */
    private $interpretationStrategy;
    
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Message\Factory $messageFactory
     * @param \Magento\Framework\Message\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param InterpretationStrategyInterface $interpretationStrategy
     * @param array $data
     */
    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Framework\Message\Factory $messageFactory,
            \Magento\Framework\Message\CollectionFactory $collectionFactory,
            \Magento\Framework\Message\ManagerInterface $messageManager,
            InterpretationStrategyInterface $interpretationStrategy,
            \IWD\B2B\Helper\Messages $helper_messages,
            array $data = []
    ) {
        parent::__construct(
                $context,
                $messageFactory,
                $collectionFactory,
                $messageManager,
                $interpretationStrategy,
                $data
        );
        $this->_helper = $helper_messages;
        $this->interpretationStrategy = $interpretationStrategy;
    }
    
    public function setMode($mode) {
        $this->_mode = $mode;
        return $this;
    }

    public function getMode() {
        return $this->_mode;
    }
    
    public function getMessages() {
        $mode = $this->getMode();
        
        $groups = [];
        
        if ($mode == 1) {
            // get default messages
            $messages = $this->messageManager->getMessages(true);
            $items = $messages->getItems();
            foreach ($items as $item) {
                $type = $item->getType();
                $groups[$type][] = $this->interpretationStrategy->interpret($item);
            }
        }
        
        // get B2B messages
        $skip_time = false;
        if ($mode == 2) {
            $skip_time = true;
        }
        $clean_messages = true;
        if ($mode == 3) 
            $clean_messages = false;
        $messages = $this->_helper->getMessages($clean_messages, false, $skip_time);
        if(!empty($messages)){
            foreach($messages as $type => $items)
                $groups[$type] = $items;
        }
        ////
        
        return $groups;
    }
    
    public function getGroups() {
        return [MessageInterface::TYPE_SUCCESS, 
                    MessageInterface::TYPE_ERROR, 
                    MessageInterface::TYPE_WARNING, 
                    MessageInterface::TYPE_NOTICE];
    }
    
}

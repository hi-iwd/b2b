<?php
namespace IWD\B2B\Helper;

use Magento\Framework\Message\MessageInterface;

class Messages extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $session;
    
    protected $group = 'B2B_Messages';
    
    protected $message_lifetime = 10;
    
    public function __construct(
            \Magento\Framework\App\Helper\Context $context,
            \Magento\Framework\Message\Session $session
    ) {
        parent::__construct($context);
        
        $this->session = $session;
    }

    /**
     * @param bool $clear
     * @return array
     */
    public function getMessages($clear = false, $full_info = false, $skip_time = false)
    {
        $group = $this->group;
        
        $messages = $this->session->getData($group);
        if (empty($messages))
            $messages = [];

        if ($clear) {
            $this->session->setData($group, false);
        }
        else{
            // remove timeout messages
            $time = time();
            $active_messages = [];
            foreach ($messages as $type => $items) {
                foreach ($items as $item) {
                    $add = true;
                    if (!$skip_time) {
                        $it = isset($item['time'])?$item['time']:0;
                        $diff = $time-$it;
                        if ($diff >= $this->message_lifetime) {
                            $add = false;
                        }
                    }
                    if ($add) {
                        $active_messages[$type][] = $item;
                    }
                }
            }
            //
            $messages = $active_messages;
            $this->session->setData($group, $messages);
        }
        
        if (!$full_info) {
            // reformat output
            $clean_messages = [];
            foreach ($messages as $type => $items) {
                foreach ($items as $item) {
                    if (isset($item['message']) && !empty($item['message']))
                        $clean_messages[$type][] = $item['message'];
                }
            }
            $messages = $clean_messages;
        }
        
        return $messages; 
    }

    /**
     * @param string $message
     */
    public function addMessage($message, $type)
    {
        if (is_object($message))
            $message = $message->getText();
        
        $messages = $this->getMessages(false, true);
        $msg = [];
        $msg['time'] = time();
        $msg['message'] = $message;
        $messages[$type][] = $msg;
        
        $this->session->setData($this->group, $messages);
    }

    /**
     * conver object message to b2b message
     * @param object $message
     */
    public function convertMessage($message) {
        if (is_object($message)) {
            
            $type = false;
            if ($message instanceof \Magento\Framework\Message\Error){
                $type = MessageInterface::TYPE_ERROR;
            }
            if ($message instanceof \Magento\Framework\Message\Warning){
                $type = MessageInterface::TYPE_WARNING;
            }
            if ($message instanceof \Magento\Framework\Message\Notice){
                $type = MessageInterface::TYPE_NOTICE;
            }
            if ($message instanceof \Magento\Framework\Message\Success){
                $type = MessageInterface::TYPE_SUCCESS;
            }
            
            if ($type) {
                $message = $message->getText();
                $this->addMessage($message, $type);
            }
        }
    }
    
    /**
     * @param string $message
     */
    public function addError($message)
    {
        $this->addMessage($message, MessageInterface::TYPE_ERROR);
    }

    /**
     * @param string $message
     */
    public function addWarning($message)
    {
        $this->addMessage($message, MessageInterface::TYPE_WARNING);
    }

    /**
     * @param string $message
     */
    public function addNotice($message)
    {
        $this->addMessage($message, MessageInterface::TYPE_NOTICE);
    }

    /**
     * @param string $message
     */
    public function addSuccess($message)
    {
        $this->addMessage($message, MessageInterface::TYPE_SUCCESS);
    }

    /**
     * @param \Exception $exception
     * @param string $alternativeText
     */
    public function addException(\Exception $exception, $alternativeText)
    {
        $message = sprintf(
                'Exception message: %s%sTrace: %s',
                $exception->getMessage(),
                "\n",
                $exception->getTraceAsString()
        );

        $this->_logger->critical($message);
        $this->addMessage($alternativeText, MessageInterface::TYPE_ERROR);
    }


}

<?php
namespace IWD\B2B\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\DataObject;

class Js extends Template
{
    protected $_helper;

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \IWD\B2B\Helper\Data $helper,
            array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_helper = $helper;
    }

    public function getJsonConfig() {

        $_secure = $this->_helper->isSecure();

        $config = new DataObject();

        // url for search product at quick ordering page
        $config->setData('quickSearchUrl', $this->getUrl('b2b/search/quick', ['_secure'=>$_secure]));
        $config->setData('quickSearchMoreUrl', $this->getUrl('b2b/search/more', ['_secure'=>$_secure]));
        //url for add product to preselected product at quick ordering page
        $config->setData('addProductToListUrl', $this->getUrl('b2b/lists/add', ['_secure'=>$_secure]));
        //url for clear list
        $config->setData('clearTableUrl', $this->getUrl('b2b/lists/clear', ['_secure'=>$_secure]));

        $config->setData('signInUrl', $this->getUrl('b2b/account/loginPost', ['_secure'=>$_secure]));
        //url for forgot password
        $config->setData('forgotPasswordUrl', $this->getUrl('b2b/account/ForgotPasswordPost', ['_secure'=>$_secure]));
        //url for load product block
        $config->setData('viewProduct', $this->getUrl('b2b/product/load', ['_secure'=>$_secure]));

        //url for update qty
        $config->setData('updateQtyUrl', $this->getUrl('b2b/cart/update', ['_secure'=>$_secure]));
        //url for add product to cart
        $config->setData('addProductUrl', $this->getUrl('b2b/cart/add', ['_secure'=>$_secure]));
        //url for process product to cart
        $config->setData('processProductUrl', $this->getUrl('b2b/cart/process', ['_secure'=>$_secure]));
        //url for process product to cart
        $config->setData('refreshUrl', $this->getUrl('b2b/cart/refresh', ['_secure'=>$_secure]));
        //url for remove item from shopping cart
        $config->setData('removeItemCartUrl', $this->getUrl('b2b/cart/remove', ['_secure'=>$_secure]));
        //url for clear shopping cart
        $config->setData('clearCartUrl', $this->getUrl('b2b/cart/clear', ['_secure'=>$_secure]));

        //add to cart method
        $config->setData('addtocart_method', $this->_helper->getText('b2btables/addtocart_method'));
        //delay for auto add to cart
        $config->setData('addtocart_auto_delay', $this->_helper->getText('b2btables/addtocart_auto_delay'));
        
        //url for clear shopping cart
        $config->setData('previewProductUrl', $this->getUrl('b2b/product/preview', ['_secure'=>$_secure]));
        //url for load next page of product
        $config->setData('loadProductPageUrl', $this->getUrl('b2b/lists/load', ['_secure'=>$_secure]));

        $config->setData('downloadInfoUrl', $this->getUrl('b2b/download/popup', ['_secure'=>$_secure]));
        $config->setData('downloadUrl', $this->getUrl('b2b/download/download', ['_secure'=>$_secure]));

        $config->setData('extensionActive', (int)$this->_helper->isEnable());
        
        $config->setData('csvUploadUrl', $this->getUrl('b2b/cart/uploadcsv', ['_secure'=>$_secure]));

        return $config->toJson();
    }
}

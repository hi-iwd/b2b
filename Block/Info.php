<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Info extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Render fieldset html
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if($element instanceof \Magento\Framework\Data\Form\Element\Text) {
            $html = $this->getInfoHtml();
        } else {
            $html = $this->_getHeaderHtml($element);
            $html .= $this->getInfoHtml();
            $html .= $this->_getFooterHtml($element);
        }

        return $html;
    }

    /**
     * @return mixed
     */
    public function getInfoHtml()
    {
        $content = __("Take your business to the next level with B2B Suite Pro, a robust set of tools 
            that makes it easier than ever to integrate your existing Magento store into a 
            full-fledged wholesale experience that will delight your customers. And the 
            best part is – you can try it without worry or hassle, with flexible subscription 
            plans that you can cancel at any time. \n\n
            • B2B Dashboard, a hub for your individual wholesellers.
            • Bulk order faster with our industry-leading product matrix.
            • Upload orders via CSV.
            • A single source for all of your downloadable marketing materials.
            • Account credit limits and pricing per accounts.
            • And more..."
        );
        $html = '<div class="free-b2b-dialog-content">' . $content . '</div>';
        $html .= '<button class="action-primary b2b-pay-button" type="button" data-role="action"><span>' .
            __('Try It Now') . '</span></button>';

        return $html;
    }
}

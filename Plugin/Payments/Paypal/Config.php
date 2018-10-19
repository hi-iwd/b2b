<?php
/**
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
namespace IWD\B2B\Plugin\Payments\Paypal;

use \Magento\Paypal\Model\AbstractConfig as PaypalConfig;

/**
 * Class Config
 * @package IWD\B2B\Model\Payments\Paypal
 */
class Config
{
    /**
     * @param PaypalConfig $subject
     * @param $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetBuildNotationCode(PaypalConfig $subject, $result)
    {
        return 'IWD_SI_MagentoCE_WPS';
    }
}
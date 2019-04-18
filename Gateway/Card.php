<?php

/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to tech@dotpay.pl so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade WooCommerce to newer
* versions in the future. If you wish to customize WooCommerce for your
* needs please refer to http://www.dotpay.pl for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

/**
 * Card gateway channel
 */
class Gateway_Card extends Gateway_Gateway {
    /**
     * Prepare gateway
     */
    public function __construct() {
        $this->title = __('Credit Cards via Dotpay', 'dotpay-payment-gateway');
        parent::__construct();
        $this->id = 'Dotpay_cc';
        $this->method_description = __('All Dotpay settings can be adjusted', 'dotpay-payment-gateway').sprintf('<a href="%s"> ', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dotpay' ) ).__('here', 'dotpay-payment-gateway').'</a>.';
        $this->addActions();
    }
    
    /**
     * Return channel id
     * @return int
     */
    protected function getChannel() {
        // return self::$ccChannel;
		return $this->getCCnumber();
    }

    /**
     * Return data for payments form
     * @return array
     */
    protected function getDataForm() {
        $hiddenFields = parent::getDataForm();
        
        $hiddenFields['channel'] = $this->getChannel();
        $hiddenFields['ch_lock'] = 1;
        $hiddenFields['type'] = 4;
         $hiddenFields['postcode'] = $this->getPostcode($hiddenFields['postcode']);
        
        return $hiddenFields;
    }
    
    /**
     * Return url to icon file
     * @return string
     */
    protected function getIcon() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/cc.png';
    }
    
    /**
     * Return flag, if this channel is enabled
     * @return bool
     */
    protected function isEnabled() {
        return parent::isEnabled() && $this->isCreditCardEnabled();
    }
}

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
 * BLIK gateway channel
 */
class Gateway_Blik extends Gateway_Gateway {
    /**
     * Prepare gateway
     */
    public function __construct() {
        parent::__construct();
        $this->id = 'Dotpay_blik';
        $this->title = __('BLIK via Dotpay', 'dotpay-payment-gateway');
        $this->method_description = __('All Dotpay settings can be adjusted', 'dotpay-payment-gateway').sprintf('<a href="%s"> ', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dotpay' ) ).__('here', 'dotpay-payment-gateway').'</a>.';
        $this->addActions();
    }
    
    /**
     * Return channel id
     * @return int
     */
    protected function getChannel() {
        return self::$blikChannel;
    }
    
    /**
     * Return data for payments form
     * @return array
     */
    protected function getDataForm() {
        $hiddenFields = parent::getDataForm();
        
        if(!$this->isTestMode())
            $hiddenFields['blik_code'] = $this->getBlikCode();
        $hiddenFields['channel'] = self::$blikChannel;
        $hiddenFields['ch_lock'] = 1;
        $hiddenFields['type'] = 4;
        
        return $hiddenFields;
    }
    
    /**
     * Return url to icon file
     * @return string
     */
    protected function getIcon() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/BLIK.png';
    }
    
    /**
     * Return flag, if this channel is enabled
     * @return bool
     */
    protected function isEnabled() {
        return parent::isEnabled() && $this->isBlikEnabled();
    }
    
    /**
     * Validate fields before creation of order
     * @return boolean
     */
    public function validate_fields() {
        $blikCode = (int)$_POST['blik_code'];
        if(strlen($blikCode) != 6) {
            wc_add_notice( __('BLIK code is incorrect', 'dotpay-payment-gateway') , 'error' );
        } else if(parent::validate_fields()) {
            $this->setBlikCode($blikCode);
            return true;
        }
        return false;
    }
    
    /**
     * Save BLIK code in a session
     * @param string $blikCode BLIK code
     */
    private function setBlikCode($blikCode) {
        $_SESSION['blik_code'] = $blikCode;
    }
    
    /**
     * Return BLIK code from session and forget it
     * @return string
     */
    private function getBlikCode() {
        $blikCode = $_SESSION['blik_code'];
        unset($_SESSION['blik_code']);
        return $blikCode;
    }
}
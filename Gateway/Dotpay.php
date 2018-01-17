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
 * Standard gateway channel
 */
class Gateway_Dotpay extends Gateway_Gateway {
    /**
     * Prepare gateway
     */
    public function __construct() {
        $this->title = 'Dotpay';
        parent::__construct();
        $this->description = __('Fast and secure payment via Dotpays', 'dotpay-payment-gateway');
        $this->addActions();
    }
    
    /**
     * Return data for payments form
     * @return array
     */
    protected function getDataForm() {
        $hiddenFields = parent::getDataForm();
        if($this->isWidgetEnabled()) {
            $hiddenFields['channel'] = $this->getChannel();
            $hiddenFields['ch_lock'] = 1;
            $hiddenFields['type'] = 4;
        }
        return $hiddenFields;
    }
    
    public function getFormPath() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'form/standard.phtml';
    }
    
    /**
     * Return url to icon file
     * @return string
     */
    protected function getIcon() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/dotpay.png';
    }
    
    public function validate_fields() {
        if(empty($_POST['channel'])&&$this->isWidgetEnabled()) {
            wc_add_notice( __('You must select a payment channel', 'dotpay-payment-gateway') , 'error' );
            return false;
        } else if(!parent::validate_fields())
            return false;
        $this->setChannel($_POST['channel']);
        return true;
    }
    
    public function init_form_fields() {
        $nameArrayMasterPass = array(
            __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
            __('Payment with a credit card by MasterPass', 'dotpay-payment-gateway'),
        );
        $nameArrayccPV = array(
            __('I have a separate account in Dotpay: show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
            __('Credit Card for currencies (PLN, EUR, USD or GBP)', 'dotpay-payment-gateway'),
        );
        $nameArrayBLIK = array(
            __('Show separately payment channel in a shop', 'dotpay-payment-gateway'),
            '<strong>'.__('(only PLN)', 'dotpay-payment-gateway').'</strong>',
            ' - BLIK (Polski Standard Płatności Sp. z o.o.)',
        );

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'dotpay-payment-gateway'),
                'label' => __('You can enable Dotpay payments', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'id' => array(
                'title' => __('Dotpay customer ID', 'dotpay-payment-gateway'),
                'type' => 'text',
				'css' => 'max-width: 100px; color: #006799; font-size: 16px; border-color: #6ec5ef;',
                'default' => '',
				'description' => __('ID number is a 6-digit string after # in a "Shop" line. You can find it at the Dotpay panel in Settings in the top bar.', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'pin' => array(
                'title' => __('Dotpay customer PIN', 'dotpay-payment-gateway'),
                'type' => 'text',
				'css' => 'min-width: 200px; color: #006799; font-size: 16px; border-color: #6ec5ef;',
                'default' => '',
				'description' => __('PIN number is a minimum 16 and maximum 32 alphanumeric characters. You can find it at the Dotpay panel in Settings in the top bar.', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'test' => array(
                'title' => __('Testing environment', 'dotpay-payment-gateway'),
                'label' => __('Only payment simulation - required Dotpay test account: <a href="https://ssl.dotpay.pl/test_seller/test/registration/" class="hide-if-no-js page-title-action"  target="_blank" title="Dotpay test account registration">registration</a>', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),

            'ccPV_show' => array(
                'title' => '<span style="color: #0073AA;">'.__('Separate Dotpay account for currencies (EUR, USD or GBP)', 'dotpay-payment-gateway').'</span>',
                'type' => 'checkbox',
                'label' => implode(' ', $nameArrayccPV),
                'default' => 'no',
                'class' => 'pv_switch',

            ),

            'id2' => array(
                'title' => '<span style="color: #0073AA;">'.__('Dotpay customer ID2 (for second account)', 'dotpay-payment-gateway').'</span>',
                'type' => 'text',
				'css' => 'max-width: 100px; color: #006799; font-size: 16px; border-color: #6ec5ef;',
                'default' => '',
                'class' => 'pv_option'
            ),

            'pin2' => array(
                'title' => '<span style="color: #0073AA;">'.__('Dotpay customer PIN2 (for second account)', 'dotpay-payment-gateway').'</span>',
                'type' => 'text',
				'css' => 'min-width: 200px; color: #006799; font-size: 16px; border-color: #6ec5ef;',
                'default' => '',
                'class' => 'pv_option'
            ),

            'ccPV_currency' => array(
                'title' => '<span style="color: #0073AA;">'.__('Channel visible only for currencies', 'dotpay-payment-gateway').'</span>',
                'type' => 'text',
                'default' => 'EUR,USD,GBP',
                'class' => 'pv_option',
                'description' => __('Leave it blank or enter a currency separated by commas eg. (EUR, GBP).', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'dontview_currency' => array(
                'title' => __('Currencies for disable main method', 'dotpay-payment-gateway'),
                'type' => 'text',
                'default' => 'EUR,USD,GBP',
                'description' => __('Leave it blank or enter a currency separated by commas eg. (EUR, GBP).', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'credit_card_show' => array(
                'title' => __('Credit cards', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
                'default' => 'no',
				'class' => 'cc_switch',
            ),
			'credit_card_channel_number' => array(
                'title' => '<span style="color: #666666;">'.__('Number of credit card channel', 'dotpay-payment-gateway').'</span>',
                'type' => 'text',
				'css' => 'max-width: 50px; color: #91999c; font-size: 16px; border-color: #b2bfc5;',
                'default' => '248',
				'description' => __('The default card channel number for the Dotpay account is 248. Leave this number if everything works for you.', 'dotpay-payment-gateway'),
				'desc_tip' => true,
				'placeholder' => _x(' eq. 248', 'placeholder', 'dotpay-payment-gateway'),
                'class' => 'cc_option'
            ),
			
            'oneclick_show' => array(
                'title' => __('OneClick', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
                'default' => 'no',
            ),
            
            'api_username' => array(
                'title' => __('Username API', 'dotpay-payment-gateway'),
                'type' => 'text',
                'default' => '',
                'label' => __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
            ),
            
            'api_password' => array(
                'title' => __('Password API', 'dotpay-payment-gateway'),
                'type' => 'password',
                'default' => '',
                'label' => __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
            ),

            'masterpass_show' => array(
                'title' => __('MasterPass', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => implode(' ', $nameArrayMasterPass),
                'default' => 'no',
            ),

            'blik_show' => array(
                'title' => __('BLIK', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => implode(' ', $nameArrayBLIK),
                'default' => 'no',
            ),

            'channels_show' => array(
                'title' => __('Widget', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Display payment channels in a shop', 'dotpay-payment-gateway'),
                'default' => 'yes',
            ),
        );
    }
    
    /**
     * Return flag, if this channel is enabled
     * @return bool
     */
    protected function isEnabled() {
        return parent::isEnabled() && $this->isMainChannelEnabled();
    }
    
    /**
     * Return list of channels, enabled as independent channel and blocked on widget
     * @return array
     */
    protected function getDisabledChannelsList() {
        $dChannels = array();
        if($this->isOneClickEnabled())
            $dChannels[] = self::$ocChannel;
        if($this->isCcPVEnabled())
            $dChannels[] = self::$pvChannel;
        if($this->isCreditCardEnabled())
            // $dChannels[] = self::$ccChannel;
            $dChannels[] = $this->getCCnumber();
        if($this->isMasterPassEnabled())
            $dChannels[] = self::$mpChannel;
        if($this->isBlikEnabled())
            $dChannels[] = self::$blikChannel;
        return implode(',', $dChannels);
    }
}

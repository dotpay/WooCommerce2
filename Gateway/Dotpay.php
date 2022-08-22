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
        $this->title = __('Dotpay (fast payment)', 'dotpay-payment-gateway');
        parent::__construct();
        $this->description = __('Fast and secure payment via Dotpay', 'dotpay-payment-gateway');
        $this->method_description = __(' Online payment', 'dotpay-payment-gateway');
        $this->addActions();
    }

    /**
     * Return data for payments form
     * @return array
     */
    protected function getDataForm() {
        $hiddenFields = parent::getDataForm();
        if($this->isWidgetEnabled()) {

            $channel = trim($this->getChannel());
            if($channel == "" || $channel == 0){
				
						$dp_channel_selected = "";

						if(null !== WC()->session->get('dotpay_payment_channel') && WC()->session->get('dotpay_payment_channel') > 0 )
						{               
							$dp_channel_selected = (int)WC()->session->get('dotpay_payment_channel');

						}else{
							if(isset( $_COOKIE['dp_channel_selected']) && (int)$_COOKIE['dp_channel_selected'] > 0 ) 
							{
								$dp_channel_selected = (int)$_COOKIE['dp_channel_selected'];
							}else{
								$dp_channel_selected = ""; 
							} 

						}
				
					if($dp_channel_selected != ""){
						$hiddenFields['channel'] = (string)trim($dp_channel_selected);
						$hiddenFields['type'] = '4';
						
					}else{
						$hiddenFields['type'] = '4';
					}
            }else{
                $hiddenFields['channel'] = (string)trim($this->getChannel());
                $hiddenFields['type'] = '4';
            }

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

        $dp_channel = "";

        if(!empty($_POST['channel'])) {
            $dp_channel = $_POST['channel'];
        }else if (!isset($_POST['channel']) && empty((int)$_POST['channel']) && null !== WC()->session->get('dotpay_payment_channel')) {
            $dp_channel = WC()->session->get('dotpay_payment_channel');
        } else {
        $dp_channel = "";
        }
		
		$this->setChannel($dp_channel);
        
		return true;
    }

    public function init_form_fields() {
        $nameArrayMasterPass = array(
            __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
            __('Payment with a credit card by MasterPass', 'dotpay-payment-gateway').
            '<p class="description">'.__('Needed separate agreement.', 'dotpay-payment-gateway').'<br>'.
			__('Contact Dotpay customer service before using this option', 'dotpay-payment-gateway').' <a href="https://www.dotpay.pl/kontakt" target="_blank" '.
               'title="'.__('Dotpay customer service', 'dotpay-payment-gateway').'">'.__('Contact', 'dotpay-payment-gateway').'</a></p>',
        );
        $nameArrayPayPo = array(
            __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
            __('Payment with a postponed method by <a href="https://www.dotpay.pl/en/payment-methods/deferred-payments/paypo/" target="_blank" title="Postponed payments – PayPo">PayPo</a>', 'dotpay-payment-gateway').
            '<p class="description">'.__('Needed separate agreement.', 'dotpay-payment-gateway').'<br>'.
			__('Contact Dotpay customer service before using this option', 'dotpay-payment-gateway').' <a href="https://www.dotpay.pl/kontakt" target="_blank" '.
               'title="'.__('Dotpay customer service', 'dotpay-payment-gateway').'">'.__('Contact', 'dotpay-payment-gateway').'</a></p>',
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
                'title' => __('Enable', 'dotpay-payment-gateway').' <img src="'.WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/dotpay.png'.'" style="vertical-align: text-bottom" alt="Dotpay">',
                'label' => '<strong style="color: #881920; font-size: 1.4em;">'.__('You can enable Dotpay payments', 'dotpay-payment-gateway').'</strong>',
                'type' => 'checkbox',
                'default' => 'yes',
				'class' => 'dotpay_module_enable'
            ),
            'id' => array(
                'title' => "🆔 ".__('Dotpay customer ID', 'dotpay-payment-gateway'),
                'type' => 'text',
				'css' => 'max-width: 100px; color: #006799; font-size: 16px; border-color: #6ec5ef;',
                'default' => '',
				'description' => __('ID number is a 6-digit string after # in a "Shop" line. You can find it at the Dotpay panel in Settings in the top bar.', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'pin' => array(
                'title' => "🗝️ ".__('Dotpay customer PIN', 'dotpay-payment-gateway'),
                'type' => 'text',
				'css' => 'min-width: 200px; color: #006799; font-size: 16px; border-color: #6ec5ef;',
                'default' => '',
				'description' => __('PIN number is a minimum 16 and maximum 32 alphanumeric characters. You can find it at the Dotpay panel in Settings in the top bar.', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'test' => array(
                'title' => "🧪 ".__('Testing environment', 'dotpay-payment-gateway'),
                'label' => __('Only payment simulation - required Dotpay test account: <a href="https://www.dotpay.pl/developer/sandbox/en/?affilate_id=woocommerce" class="hide-if-no-js page-title-action"  target="_blank" title="Dotpay test account registration">registration</a>', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'proxy_server' => array(
                'title' => "💻 ".__('My server does not use a proxy', 'dotpay-payment-gateway'),
                'label' => __('By default, we recommend that you set it on (no proxy).', 'dotpay-payment-gateway')."<br>". __('If you are sure otherwise or you have problems receiving confirmations about the completed payment - set it to off.', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'default' => 'yes'
            ),
            'productname' => array(
                'title' => "📜 ".__('Show product name in payment title', 'dotpay-payment-gateway'),
                'label' => __('If there is only one product in the cart - show its name in the transaction description.', 'dotpay-payment-gateway'),
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
                'title' => "🚫 ". __('Currencies that disable main method', 'dotpay-payment-gateway'),
                'type' => 'text',
                'default' => '',
                'description' => __('Leave it blank or enter a currency separated by commas eg. (EUR, GBP).', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'credit_card_show' => array(
                'title' => __('Credit cards', 'dotpay-payment-gateway').'<br><img src="'.WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/cc.png'.'" alt="Credit Cards" width="83" height="29" style="margin-top: 10px;">',
                'type' => 'checkbox',
				'description' => __('Needed separate agreement.', 'dotpay-payment-gateway'),
				'desc_tip' => true,
                'label' => __('Show separately payment channel in a shop.', 'dotpay-payment-gateway').'
							<br><p class="description">'. __('Needed separate agreement.','dotpay-payment-gateway').'<br>'.
						  __('Contact Dotpay customer service before using this option', 'dotpay-payment-gateway').
                          ' <a href="https://www.dotpay.pl/kontakt" target="_blank" '.
                          'title="'.__('Dotpay customer service', 'dotpay-payment-gateway').'">'.
						  __('Contact', 'dotpay-payment-gateway', 'dotpay-payment-gateway').'</a></p>',
                'default' => 'no',
				'class' => 'cc_switch',
            ),
			'credit_card_channel_number' => array(
                'title' => '<span style="color: #666666;">'.__('Number of credit card channel', 'dotpay-payment-gateway').'</span>',
                'type' => 'text',
				'css' => 'max-width: 72px; color: #91999c; font-size: 16px; border-color: #b2bfc5;',
                'default' => '248',
				'description' => __('The default card channel number for the Dotpay account is 248. Leave this number if everything works for you.', 'dotpay-payment-gateway'),
				'desc_tip' => true,
				'placeholder' => __(' eq. 248','dotpay-payment-gateway'),
                'class' => 'cc_option',
            ),

            'oneclick_show' => array(
                'title' => __('One Click for credit card', 'dotpay-payment-gateway').'<br><img src="'.WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/cc.png'.'" alt="One Click Credit Cards" width="83" height="29" style="margin-top: 10px;">',
                'type' => 'checkbox',
				'description' => __('Needed separate agreement.', 'dotpay-payment-gateway'),
				'desc_tip' => true,
                'label' => __('Show separately payment channel in a shop.', 'dotpay-payment-gateway').'
							<br><p class="description">'. __('Needed separate agreement.','dotpay-payment-gateway').'<br>'.
						  __('Contact Dotpay customer service before using this option', 'dotpay-payment-gateway').
                          ' <a href="https://www.dotpay.pl/kontakt" target="_blank" '.
                          'title="'.__('Dotpay customer service', 'dotpay-payment-gateway').'">'.
						  __('Contact', 'dotpay-payment-gateway', 'dotpay-payment-gateway').'</a></p><strong>'.
						  __('Requires Dotpay API username and password (enter below).', 'dotpay-payment-gateway').'</strong>',
                'default' => 'no',
            ),

            'api_username' => array(
                'title' => "👤 ".__('Username API', 'dotpay-payment-gateway')."<br><em>".__('(optional)', 'dotpay-payment-gateway')."</em>",
                'type' => 'text',
                'default' => '',
				'description' => __('Leave this field empty if you do not use One Click and if you do not want to present the payment instructions on the shop page for semi-automatic channels. Data for access to the Dotpay administration panel.', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'api_password' => array(
                'title' => "🔑 ".__('Password API', 'dotpay-payment-gateway')."<br><em>".__('(optional)', 'dotpay-payment-gateway')."</em><br><br><em>".
                __('Contact Dotpay customer service before using this option', 'dotpay-payment-gateway').
              "( <a href=\"https://www.dotpay.pl/kontakt\" target=\"_blank\" title=\"". __('Dotpay customer service', 'dotpay-payment-gateway')."\">".
              __('Contact', 'dotpay-payment-gateway', 'dotpay-payment-gateway')."</a>). ".__('Required additional permissions for the user API.','dotpay-payment-gateway')."</em>",
                'type' => 'password',
                'default' => '',
                'description' => __('Leave this field empty if you do not use One Click and if you do not want to present the payment instructions on the shop page for semi-automatic channels. Data for access to the Dotpay administration panel.', 'dotpay-payment-gateway'),
                'desc_tip' => true,
            ),

            'masterpass_show' => array(
                'title' => __('MasterPass', 'dotpay-payment-gateway').'<br><img src="'.WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/MasterPass.png'.'" alt="MasterPass" width="62px" height="35px" style="margin-top: 10px;">',
                'type' => 'checkbox',
				'description' => __('Needed separate agreement.', 'dotpay-payment-gateway'),
				'desc_tip' => true,
                'label' => implode(' ', $nameArrayMasterPass),
                'default' => 'no',
            ),

            'blik_show' => array(
                'title' => __('BLIK', 'dotpay-payment-gateway').'<br><img src="'.WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/BLIK.png'.'" alt="BLIK" width="82" height="40" style="margin-top: 10px;">',
                'type' => 'checkbox',
                'label' => implode(' ', $nameArrayBLIK),
                'default' => 'no',
            ),

            'channels_show' => array(
                'title' => "🖼 ".__('Widget (display payment channel in a shop)', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Display payment channels in the store available for the account', 'dotpay-payment-gateway'),
                'default' => 'yes',
                'class' => 'widget_show',
            ),
            'channel_name_show' => array(
                'title' => "💬 ".__('Toggle channel names in widget view', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Display payment channels names in widget (recommends: no)', 'dotpay-payment-gateway'),
                'default' => 'no',
                'class' => 'widget_channel_names',
            ),
            'transfer_instruction' => array(
                'title' => "📜 ".__('Display transfer payment instructions without redirecting to Dotpay site', 'dotpay-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Show payment instructions for traditional transfers (some payment methods: cash, online payments) on your website.', 'dotpay-payment-gateway')."<br><em><strong>".__('Requires Dotpay API username and password (enter above).', 'dotpay-payment-gateway')."</em></strong><br><br><em id='dp_transfer_instruction_contact'>".__('Contact Dotpay customer service before using this option', 'dotpay-payment-gateway')
                ."( <a href=\"https://www.dotpay.pl/kontakt\" target=\"_blank\" title=\"". __('Dotpay customer service', 'dotpay-payment-gateway')."\">"
                .__('Contact', 'dotpay-payment-gateway', 'dotpay-payment-gateway')."</a>). ".__('Required additional permissions for the user API.','dotpay-payment-gateway')."</em>",
                'default' => 'no',
                'class' => 'dp_transfer_instruction',
            ),
            'paypo_show' => array(
                'title' => __('PayPo - postponed payments', 'dotpay-payment-gateway').'<br><img src="'.WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/PayPo.png'.'" alt="PayPo" width="77" height="50" style="margin-top: 10px;">',
                'type' => 'checkbox',
				'description' => __('Needed separate agreement.', 'dotpay-payment-gateway'),
				'desc_tip' => true,
                'label' => implode(' ', $nameArrayPayPo),
                'default' => 'no',
            ),

        );

	    $zones = WC_Shipping_Zones::get_zones();

        foreach($zones as $zone)
	    {
	    	foreach($zone['shipping_methods'] as $method)
		    {
			    $this->form_fields["shipping_mapping_" . $method->instance_id] = $this->getMappingFieldForShippingMethod($zone['zone_name'], $method->title);
		    }
	    }

        $zone0 = WC_Shipping_Zones::get_zone(0);

		foreach($zone0->get_shipping_methods() as $method)
		{
			$this->form_fields["shipping_mapping_" . $method->instance_id] = $this->getMappingFieldForShippingMethod($zone0->get_zone_name(), $method->title);
		}
    }


    

    /**
     * Return flag, if this channel is enabled
     * @return bool
     */
    protected function isEnabled() {
        return parent::isEnabled() && $this->isMainChannelEnabled();
    }

    private function getMappingFieldForShippingMethod($zoneName, $methodTitle)
    {
    	return array(
		    'title'       => __( $zoneName . " - " . $methodTitle, 'dotpay-payment-gateway'),
		    'type'        => 'select',
		    'class'       => 'wc-enhanced-select',
		    'description' => __( 'Choose what kind of delivery describes this shipping method', 'dotpay-payment-gateway' ),
		    'default'     => '',
		    'desc_tip'    => true,
		    'options'     => array(
			    ''          => __( '-', 'dotpay-payment-gateway' ),
			    'COURIER'          => __( 'Courier', 'dotpay-payment-gateway' ),
			    'POCZTA_POLSKA'          => __( 'Poczta Polska', 'dotpay-payment-gateway' ),
			    'PICKUP_POINT'          => __( 'Pickup point', 'dotpay-payment-gateway' ),
			    'PACZKOMAT'          => __( 'Paczkomat', 'dotpay-payment-gateway' ),
			    'PACZKA_W_RUCHU'          => __( 'Paczka w Ruchu', 'dotpay-payment-gateway' ),
			    'PICKUP_SHOP'          => __( 'Local pickup', 'dotpay-payment-gateway' ),
		    ),
	    );
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
        if($this->isPayPoEnabled())
            $dChannels[] = self::$paypoChannel;
        if($this->isBlikEnabled())
            $dChannels[] = self::$blikChannel;
        return implode(',', $dChannels);
    }
}
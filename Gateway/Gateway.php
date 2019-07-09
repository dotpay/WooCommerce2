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
 * Abstract gateway channel
 */
abstract class Gateway_Gateway extends Dotpay_Payment {

    /**
     * Status of order after complete payment
     */
    const STATUS_COMPLETED = 'processing';
	
	/**
     * Status of order after complete payment for virtual products
     */
    const STATUS_COMPLETED_VIRTUAL = 'completed';

    /**
     * Status of order after failed payment
     */
    const STATUS_REJECTED = 'failed';

    /**
     * Status of order before complete payment
     */
    const STATUS_DEFAULT = 'pending';

    /**
     * Name of cash group of channels
     */
    const cashGroup = 'cash';

    /**
     * Name of transfer group of channels
     */
    const transferGroup = 'transfers';

    /**
     * Prepare gateway
     */
    public function __construct() {
        $this->id = 'dotpay';
        $this->icon = $this->getIcon();
        $this->has_fields = true;
        $this->method_title = __('DOTPAY PAYMENT', 'dotpay-payment-gateway');
        $this->description = __('Fast and secure payment via Dotpay', 'dotpay-payment-gateway');


        $this->init_form_fields();
        $this->init_settings();
        $this->enabled = ($this->isEnabled())?'yes':'no';

    }


    /**
     * Add actions to API plugin
     */
    protected function addActions() {
        add_action('woocommerce_api_'.strtolower($this->id).'_form', array($this, 'getRedirectForm'));
        add_action('woocommerce_api_'.strtolower($this->id).'_confirm', array($this, 'confirmPayment'));
        add_action('woocommerce_api_'.strtolower($this->id).'_status', array($this, 'checkStatus'));
    }

    /**
     * Return url to image with admin settings logo
     * @return string
     */
    protected function getAdminSettingsLogo() {
        $dotpay_logo_lang = ($this->getPaymentLang() === 'pl')?'pl':'en';
        return WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/Dotpay_logo_desc_'.$dotpay_logo_lang .'.png';
    }

    /**
     * Return option key for Dotpay plugin
     * @return string
     */
    public function get_option_key() {
        return $this->plugin_id . $this->id . '_settings';
    }

    /**
     * Init plugin settings and add save options action
     */
    public function init_settings() {
        parent::init_settings();
        if ( version_compare( WOOCOMMERCE_VERSION, '3.2.0', '>=' ) ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
        } else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
    }

    /**
     * Includes payment fields for a specific channel
     */
    public function payment_fields() {
        include($this->getFormPath());
    }

    /**
     * Create payment and return details of the next action
     * @global type $woocommerce WOOCOMMERCE object
     * @param int $order_id order id
     * @return array
     */
    public function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);
        $woocommerce->cart->empty_cart();
        $this->setOrderId($order_id);

        $sellerApi = new Dotpay_SellerApi($this->getSellerApiUrl());
        if($this->isChannelInGroup($this->getChannel(), array(self::cashGroup, self::transferGroup)) &&
           $sellerApi->isAccountRight($this->getApiUsername(), $this->getApiPassword())) {
            $gateway = new Gateway_Transfer();
            $redirectUrl = $gateway->generateWcApiUrl('form');
        } else {
            $redirectUrl = $this->generateWcApiUrl('form');
        }
        return array(
            'result'   => 'success',
            'redirect' => $redirectUrl
        );
    }

    /**
     * Generate url address for plugin API functionality
     * @param string $target target API function
     * @return string
     */
    protected function generateWcApiUrl($target) {
        return add_query_arg('wc-api', $this->id.'_'.$target, home_url('/'));
    }

    /**
     * Return data for payments form
     * @return array
     */
    protected function getDataForm() {
        global $file_prefix;
        if (function_exists('wp_cache_clean_cache')) {
            wp_cache_clean_cache($file_prefix, true);
        }
        if(empty($_SESSION['dotpay_payment_order_id'])) {
            die(__('Order not found', 'dotpay-payment-gateway'));
        }
        $this->setOrderId($_SESSION['dotpay_payment_order_id']);
        $streetData = $this->getStreetAndStreetN1();
        return array(
            'id' => $this->getSellerId(),
            'control' => $this->getControl('full'),
            'p_info' => $this->getPinfo(),
            'amount' => $this->getOrderAmount(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'lang' => $this->getPaymentLang(),
            'URL' => $this->getUrl(),
            'URLC' => $this->getUrlC(),
            'api_version' => $this->getApiVersion(),
            'type' => 0,
            'ch_lock' => 0,
            'firstname' => $this->getFirstname(),
            'lastname' => $this->getLastname(),
            'email' => $this->getEmail(),
            'phone' => $this->getPhone(),
            'street' => $streetData['street'],
            'street_n1' => $streetData['street_n1'],
            'city' => $this->getCity(),
            'postcode' => $this->getPostcode(),
            'country' => $this->getCountry(),
			'personal_data' => 1,
            'bylaw' => 1,
	        'customer' => $this->getCustomerBase64()
        );
    }

    /**
     * Return fields for payments form with calculated CHK
     * @return array
     */
    protected function getHiddenFields() {
        $data = $this->getDataForm();
        $this->forgetChannel();
        $data['chk'] = $this->generateCHK($this->getSellerId(), $this->getSellerPin(), $data);
        return $data;
    }

	/**
	 * Returns data to 'customer' parameter
	 * @return string encoded base64
	 */
	public function getCustomerBase64() {

		$customer = array (
			"payer" => array(
				"first_name" => $this->getFirstname(),
				"last_name" => $this->getLastname(),
				"email" => $this->getEmail(),
				"phone" => $this->getPhone()
			),
			"order" => array(
				"delivery_address" => array(

					"city" => $this->getShippingCity(),
					"street" => $this->getShippingStreetAndStreetN1()['street'],
					"building_number" => $this->getShippingStreetAndStreetN1()['street_n1'],
					"postcode" => $this->getShippingPostcode(),
					"country" => $this->getShippingCountry()
				)
			)
		);

		if($user = $this->getOrder()->get_user()) {

			$customer["registered_since"] = date("Y-m-d", strtotime($user->get('user_registered')));
			$customer["order_count"] = wc_get_customer_order_count($user->ID);
        }

        if ($this->getSelectedCarrierMethodGroup() != "") {
            $customer["order"]["delivery_type"] = $this->getSelectedCarrierMethodGroup();
        }
        

		$customer_base64 = base64_encode(json_encode($customer, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
		
		return $customer_base64;
	}

	protected function getSelectedCarrierMethodGroup()
	{
		$methods = $this->getOrder()->get_shipping_methods();
		$method = array_pop($methods);
		return $this->getShippingMapping($method) ? $this->getShippingMapping($method->get_instance_id()) : "";
	}

    /**
     * Check, if channel is in channels groups
     * @param int $channelId channel id
     * @param array $group names of channel groups
     * @return boolean
     */
    public function isChannelInGroup($channelId, array $groups) {
        $resultJson = $this->getDotpayChannels($this->getOrderAmount());
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['group']) && $channel['id']==$channelId && in_array($channel['group'], $groups)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Return flag, if this channel is enabled
     * @return bool
     */
    protected function isEnabled() {
        $result = false;
        if ('yes' === $this->get_option('enabled')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return admin prompt class name
     * @return string
     */
    protected function getAdminPromptClass() {
        $result = 'error';
        if ('yes' === $this->get_option('enabled')) {
            $result = 'updated';
        }

        return $result;
    }

    /**
     * Return flag, if main channel is enabled
     * @return boolean
     */
    protected function isMainChannelEnabled() {
        $result = true;
        if($this->isDotSelectedCurrency($this->get_option('dontview_currency'))) {
            $result = false;
        }
        return $result;
    }

    /**
     * Return flag, if Dotpay widget is enabled
     * @return boolean
     */
    protected function isWidgetEnabled() {
        $result = false;
        if ('yes' === $this->get_option('channels_show')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return flag, if One Click is enabled
     * @return boolean
     */
    protected function isOneClickEnabled() {
        $result = false;
        if ('yes' === $this->get_option('oneclick_show')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return flag, if MasterPass is enabled
     * @return boolean
     */
    protected function isMasterPassEnabled() {
        $result = false;
        if ('yes' === $this->get_option('masterpass_show')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return flag, if Credit card PV is enabled
     * @return boolean
     */
    protected function isCcPVEnabled() {
        $result = true;
        if ('no' === $this->get_option('ccPV_show')) {
            $result = false;
        }
        if(!$this->isDotSelectedCurrency($this->get_option('ccPV_currency'))) {
            $result = false;
        }

        return $result;
    }

    /**
     * Return flag, if BLIK is enabled
     * @return boolean
     */
    protected function isBlikEnabled() {
        $result = false;
        if ('yes' === $this->get_option('blik_show')) {
            $result = true;
        }
        if($this->getCurrency() != 'PLN') {
            $result = false;
        }

        return $result;
    }

    /**
     * Return flag, if credit card is enabled
     * @return boolean
     */
    protected function isCreditCardEnabled() {
        $result = false;
        if ('yes' === $this->get_option('credit_card_show')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Generate CHK for seller and payment data
     * @param type $DotpayId Dotpay seller ID
     * @param type $DotpayPin Dotpay seller PIN
     * @param array $ParametersArray parameters of payment
     * @return string
     */
    protected function generateCHK($DotpayId, $DotpayPin, $ParametersArray) {
        $ParametersArray['id'] = $DotpayId;
        $ChkParametersChain =
        $DotpayPin.
        (isset($ParametersArray['api_version']) ? $ParametersArray['api_version'] : null).
        (isset($ParametersArray['charset']) ? $ParametersArray['charset'] : null).
        (isset($ParametersArray['lang']) ? $ParametersArray['lang'] : null).
        (isset($ParametersArray['id']) ? $ParametersArray['id'] : null).
        (isset($ParametersArray['amount']) ? $ParametersArray['amount'] : null).
        (isset($ParametersArray['currency']) ? $ParametersArray['currency'] : null).
        (isset($ParametersArray['description']) ? $ParametersArray['description'] : null).
        (isset($ParametersArray['control']) ? $ParametersArray['control'] : null).
        (isset($ParametersArray['channel']) ? $ParametersArray['channel'] : null).
        (isset($ParametersArray['credit_card_brand']) ? $ParametersArray['credit_card_brand'] : null).
        (isset($ParametersArray['ch_lock']) ? $ParametersArray['ch_lock'] : null).
        (isset($ParametersArray['channel_groups']) ? $ParametersArray['channel_groups'] : null).
        (isset($ParametersArray['onlinetransfer']) ? $ParametersArray['onlinetransfer'] : null).
        (isset($ParametersArray['URL']) ? $ParametersArray['URL'] : null).
        (isset($ParametersArray['type']) ? $ParametersArray['type'] : null).
        (isset($ParametersArray['buttontext']) ? $ParametersArray['buttontext'] : null).
        (isset($ParametersArray['URLC']) ? $ParametersArray['URLC'] : null).
        (isset($ParametersArray['firstname']) ? $ParametersArray['firstname'] : null).
        (isset($ParametersArray['lastname']) ? $ParametersArray['lastname'] : null).
        (isset($ParametersArray['email']) ? $ParametersArray['email'] : null).
        (isset($ParametersArray['street']) ? $ParametersArray['street'] : null).
        (isset($ParametersArray['street_n1']) ? $ParametersArray['street_n1'] : null).
        (isset($ParametersArray['street_n2']) ? $ParametersArray['street_n2'] : null).
        (isset($ParametersArray['state']) ? $ParametersArray['state'] : null).
        (isset($ParametersArray['addr3']) ? $ParametersArray['addr3'] : null).
        (isset($ParametersArray['city']) ? $ParametersArray['city'] : null).
        (isset($ParametersArray['postcode']) ? $ParametersArray['postcode'] : null).
        (isset($ParametersArray['phone']) ? $ParametersArray['phone'] : null).
        (isset($ParametersArray['country']) ? $ParametersArray['country'] : null).
        (isset($ParametersArray['code']) ? $ParametersArray['code'] : null).
        (isset($ParametersArray['p_info']) ? $ParametersArray['p_info'] : null).
        (isset($ParametersArray['p_email']) ? $ParametersArray['p_email'] : null).
        (isset($ParametersArray['n_email']) ? $ParametersArray['n_email'] : null).
        (isset($ParametersArray['expiration_date']) ? $ParametersArray['expiration_date'] : null).
        (isset($ParametersArray['recipient_account_number']) ? $ParametersArray['recipient_account_number'] : null).
        (isset($ParametersArray['recipient_company']) ? $ParametersArray['recipient_company'] : null).
        (isset($ParametersArray['recipient_first_name']) ? $ParametersArray['recipient_first_name'] : null).
        (isset($ParametersArray['recipient_last_name']) ? $ParametersArray['recipient_last_name'] : null).
        (isset($ParametersArray['recipient_address_street']) ? $ParametersArray['recipient_address_street'] : null).
        (isset($ParametersArray['recipient_address_building']) ? $ParametersArray['recipient_address_building'] : null).
        (isset($ParametersArray['recipient_address_apartment']) ? $ParametersArray['recipient_address_apartment'] : null).
        (isset($ParametersArray['recipient_address_postcode']) ? $ParametersArray['recipient_address_postcode'] : null).
        (isset($ParametersArray['recipient_address_city']) ? $ParametersArray['recipient_address_city'] : null).
        (isset($ParametersArray['warranty']) ? $ParametersArray['warranty'] : null).
        (isset($ParametersArray['bylaw']) ? $ParametersArray['bylaw'] : null).
        (isset($ParametersArray['personal_data']) ? $ParametersArray['personal_data'] : null).
        (isset($ParametersArray['credit_card_number']) ? $ParametersArray['credit_card_number'] : null).
        (isset($ParametersArray['credit_card_expiration_date_year']) ? $ParametersArray['credit_card_expiration_date_year'] : null).
        (isset($ParametersArray['credit_card_expiration_date_month']) ? $ParametersArray['credit_card_expiration_date_month'] : null).
        (isset($ParametersArray['credit_card_security_code']) ? $ParametersArray['credit_card_security_code'] : null).
        (isset($ParametersArray['credit_card_store']) ? $ParametersArray['credit_card_store'] : null).
        (isset($ParametersArray['credit_card_store_security_code']) ? $ParametersArray['credit_card_store_security_code'] : null).
        (isset($ParametersArray['credit_card_customer_id']) ? $ParametersArray['credit_card_customer_id'] : null).
        (isset($ParametersArray['credit_card_id']) ? $ParametersArray['credit_card_id'] : null).
        (isset($ParametersArray['blik_code']) ? $ParametersArray['blik_code'] : null).
        (isset($ParametersArray['credit_card_registration']) ? $ParametersArray['credit_card_registration'] : null).
	    (isset($ParametersArray['customer']) ? $ParametersArray['customer'] : null);

        return hash('sha256',$ChkParametersChain);
    }

    /**
     * Return url to icon file
     * @return string
     */
    protected function getIcon() {
        return '';
    }

    /**
     * Return rendered status page HTML
     * @return string
     */
    public function getStatusPage() {
        $this->message = NULL;
        if($this->getParam('error_code')!==false) {
            switch($this->getParam('error_code')) {
                case 'PAYMENT_EXPIRED':
                    $this->message = __('Exceeded expiration date of the generated payment link.', 'dotpay-payment-gateway');
                    break;
                case 'UNKNOWN_CHANNEL':
                    $this->message = __('Selected payment channel is unknown.', 'dotpay-payment-gateway');
                    break;
                case 'DISABLED_CHANNEL':
                    $this->message = __('Selected channel payment is desabled.', 'dotpay-payment-gateway');
                    break;
                case 'BLOCKED_ACCOUNT':
                    $this->message = __('Account is disabled.', 'dotpay-payment-gateway');
                    break;
                case 'INACTIVE_SELLER':
                    $this->message = __('Seller account is inactive.', 'dotpay-payment-gateway');
                    break;
                case 'AMOUNT_TOO_LOW':
                    $this->message = __('Amount is too low.', 'dotpay-payment-gateway');
                    break;
                case 'AMOUNT_TOO_HIGH':
                    $this->message = __('Amount is too high.', 'dotpay-payment-gateway');
                    break;
                case 'BAD_DATA_FORMAT':
                    $this->message = __('Data format is bad.', 'dotpay-payment-gateway');
                    break;
                case 'HASH_NOT_EQUAL_CHK':
                    $this->message = __('Request has been modified during transmission.', 'dotpay-payment-gateway');
                    break;
                case 'REQUIRED_PARAMS_NOT_FOUND':
                    $this->message = __('There were not given all request parameters.', 'dotpay-payment-gateway');
                    break;
                case 'URLC_INVALID':
                    $this->message = __('Account settings in Dotpay require the seller to have SSL certificate enabled on his website.', 'dotpay-payment-gateway');
                    break; 
                default:
                    $this->message = __('There was an unidentified error. Please contact to your seller and give him the order number.', 'dotpay-payment-gateway');
            }
        }
        return $this->render('check_status.phtml');
    }

    /**
     * Return content of bylaw agreement
     * @return string
     */
    protected function getBylaw() {
        return $this->getDotpayAgreement($this->get_order_total(), 'bylaw');
    }

    /**
     * Return content of personal data agreement
     * @return string
     */
    protected function getPersonalData() {
        return $this->getDotpayAgreement($this->get_order_total(), 'personal_data');
    }

    /**
     * Validate fields before creation of order
     * @return boolean
     */
    public function validate_fields() {
        if($this->getParam('bylaw')!= '1' ) {
            wc_add_notice( __('Please accept all agreements', 'dotpay-payment-gateway') , 'error' );
            return false;
        }
        return true;
    }

    /**
     * Return ID of current shop client
     * @return int
     */
    protected function getCurrentUserId() {
        return wp_get_current_user()->ID;
    }

    /**
     * Display redirect form with payment details
     */
    public function getRedirectForm() {
        die($this->render('redirect_form.phtml'));
    }
	
	
    /**
     * Confirm payment after getting confirmation info from Dotpay
     * @global string $wp_version version of installed instance of WordPress
     * @global type $woocommerce WOOCOMMERCE object
     */
    public function confirmPayment() {
        global $wp_version, $woocommerce;
        if($this->getClientIp() == self::OFFICE_IP && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
            $sellerApi = new Dotpay_SellerApi($this->getSellerApiUrl());
            $dotpayGateways = '';
            $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			$curlvalues=curl_version();
            foreach(self::getDotpayChannelsList() as $channel) {
                $gateway = new $channel();
                $dotpayGateways .= $gateway->id.': '.$this->checkIfEnabled($gateway)."<br />";
            }
            $shopGateways = '';
            foreach(WC_Payment_Gateways::instance()->payment_gateways() as $channel) {
                $gateway = new $channel();
                $shopGateways .= $gateway->id.': '.$this->checkIfEnabled($gateway)."<br />";
            }
            die("WooCommerce Dotpay payment module debug:<br><br>
			      * Dotpay module ver: ".self::MODULE_VERSION.
                "<br> * Wordpress ver: ". $wp_version .
                "<br> * Woocommerce ver: ". $woocommerce->version .
                "<br> * PHP ver: ". phpversion() .
                "<br> * cURL ver: ". $curlvalues['version']." ".$curlvalues['ssl_version'].
				"<br> * MySQL ver: ".  mysqli_get_server_info($connection).
                "<br>  _____________ ".
                "<br>  - Active: ".(bool)$this->isEnabled().
				"<br>  - ID: ".$this->getSellerId().
                "<br>  - Test: ".(bool)$this->isTestMode().
                "<br>  - currencies_that_block_main:  ".$this->get_option('dontview_currency').
                "<br>  - is_multisite: ".(bool)is_multisite().
                "<br>  - is_plugin_active_for_network: ".(bool)is_plugin_active_for_network('woocommerce/woocommerce.php').
				"<br><br /> --- Dotpay API data: --- ".
				"<br>  - Dotpay username: ".$this->getApiUsername().
				"<br>  - correct API auth data: ".$sellerApi->isAccountRight($this->getApiUsername(), $this->getApiPassword()).
                "<br><br /> --- Dotpay channels: --- <br />".$dotpayGateways.
                "<br /> --- Shop channels: --- <br />".$shopGateways
            );
        }

        if (!($this->getClientIp() == self::DOTPAY_IP || $this->getClientIp() == self::OFFICE_IP)) {
            die("WooCommerce - ERROR (REMOTE ADDRESS: ".$this->getClientIp(true).")");
        }

        if (strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') {
            die("WooCommerce - ERROR (METHOD <> POST)");
        }

        if (!$this->checkConfirmSign()) {
            die("WooCommerce - ERROR SIGN");
        }

        if ($this->getParam('id') != $this->getSellerId()) {
            die("WooCommerce - ERROR ID: ".$this->getSellerId());
        }


		$controlNr = explode('|', (string)$this->getParam('control'));
        $order = new WC_Order($controlNr[0]);
        if (!$order && $order->get_id() === NULL) {
            die('FAIL ORDER: not exist');
        }

        $this->checkCurrency($order);
        $this->checkAmount($order);

        $status = $this->getParam('operation_status');
        $operationNR = $this->getParam('operation_number');
		$chNR = $this->getParam('channel');
		$chDataNR = $this->getChannelName($chNR);
        $PaymentChannelName = $chDataNR['name'];
        $PaymentChannelLogo= $chDataNR['logo'];
        $note = __("Dotpay send notification", 'dotpay-payment-gateway') . ": <br><span style=\"color: #4b5074; font-style: italic;\">".__("transaction number:", 'dotpay-payment-gateway') ." <span style=\"font-weight: bold;\">".$operationNR."</span>, <br>". __("payment channel:", 'dotpay-payment-gateway')." <span style=\"font-weight: bold;\">".$PaymentChannelName."</span> /<span style=\"font-weight: bold;\">".$chNR."</span>/</span><br><img src=\"".$PaymentChannelLogo."\" width=\"100px\" height=\"50px\" alt=\"".$PaymentChannelName."\"> <br><span style=\"font-weight: bold; \">status</span>: ";

        switch ($status) {
            case 'completed':	
				$order_status_note =  $order->needs_processing() ? __('paid - processing', 'dotpay-payment-gateway') :  __('paid - completed (virtual product)', 'dotpay-payment-gateway');
				$order->update_status($order->needs_processing() ? self::STATUS_COMPLETED : self::STATUS_COMPLETED_VIRTUAL, $note.' <span style="color: green; font-weight: bold;">'.$order_status_note.'</span>. <br>');
				
			    do_action('woocommerce_order_status_pending_to_quote', $order->get_id());
                do_action('woocommerce_payment_complete', $order->get_id());
                break;
            case 'rejected':
                $order->update_status(self::STATUS_REJECTED, $note.' <span style="color: red; font-weight: bold;">'.__('cancelled', 'dotpay-payment-gateway').'</span>. <br>');
                break;
            default:
                $order->update_status(self::STATUS_DEFAULT, $note.'  <span style="color: orange; font-weight: bold;">'.__('processing', 'dotpay-payment-gateway').'</span>. <br>');
        }
        if($this->postConfirmOrder($order)) {
            die('OK');
        }
    }

    /**
     * Check, if payment gateway is enabled
     * @param type $object Payment gateway instance
     * @return int
     */
    private function checkIfEnabled($object) {
        return (int)$object->is_available();
    }

    /**
     * Display status number of selected order
     */
    public function checkStatus() {
        switch($this->getOrder()->get_status()) {
            case self::STATUS_COMPLETED:
                $this->forgetOrder();
                die('1');
			case self::STATUS_COMPLETED_VIRTUAL:
                $this->forgetOrder();
                die('1');	
            case self::STATUS_REJECTED:
                $this->forgetOrder();
                die('-1');
            case self::STATUS_DEFAULT:
                die('0');
            default:
                die('ERROR');
        }
    }

    /**
     * Return url for check status request
     * @return string
     */
    public function getCheckStatusUrl() {
        return get_site_url(Dotpay_Page::getPageId(DOTPAY_STATUS_PNAME));
    }

    /**
     * Overrides a method from parent, because Dotpay Payment Gateway uses another method to checking if gateway is available
     * @return boolean
     */
    public function is_available() {
        return $this->isEnabled();
    }

    protected function checkConfirmSign() {
        $signature = $this->getSellerPin().$this->getSellerId().
        $this->getParam('operation_number').
        $this->getParam('operation_type').
        $this->getParam('operation_status').
        $this->getParam('operation_amount').
        $this->getParam('operation_currency').
        $this->getParam('operation_withdrawal_amount').
        $this->getParam('operation_commission_amount').
        $this->getParam('is_completed').
        $this->getParam('operation_original_amount').
        $this->getParam('operation_original_currency').
        $this->getParam('operation_datetime').
        $this->getParam('operation_related_number').
        $this->getParam('control').
        $this->getParam('description').
        $this->getParam('email').
        $this->getParam('p_info').
        $this->getParam('p_email').
        $this->getParam('credit_card_issuer_identification_number').
        $this->getParam('credit_card_masked_number').
        $this->getParam('credit_card_expiration_year').
        $this->getParam('credit_card_expiration_month').
        $this->getParam('credit_card_brand_codename').
        $this->getParam('credit_card_brand_code').
        $this->getParam('credit_card_unique_identifier').
        $this->getParam('credit_card_id').
        $this->getParam('channel').
        $this->getParam('channel_country').
        $this->getParam('geoip_country');

        return ($this->getParam('signature') === hash('sha256', $signature));
    }

    /**
     * Break the program, if currency in order and in confirmation are different
     * @param WC_Order $order order object
     */
    protected function checkCurrency($order) {
        $currencyOrder = $order->get_currency();
        $currencyResponse = $this->getParam('operation_original_currency');

        if ($currencyOrder !== $currencyResponse) {
            die('FAIL CURRENCY');
        }
    }

    /**
     * Break the program, if amount in order and in confirmation are different
     * @param WC_Order $order order object
     */
    protected function checkAmount($order) {
        $amount = $this->getFormatAmount(round($order->get_total(), 2));
        $amountOrder = sprintf("%01.2f", $amount);
        $amountResponse = $this->getParam('operation_original_amount');

        if ($amountOrder !== $amountResponse) {
            die('FAIL AMOUNT');
        }
    }

    protected function postConfirmOrder($order) {
        return true;
    }

    /**
     * Save channel id persistent
     * @param int $channel channel id
     */
    protected function setChannel($channel) {
        $_SESSION['dotpay_payment_channel'] = (int)$channel;
    }

    /**
     * Return channel id
     * @return int/null
     */
    protected function getChannel() {
        if(isset($_SESSION['dotpay_payment_channel'])) {
            $channel = $_SESSION['dotpay_payment_channel'];
        } else {
            $channel = null;
        }
        return $channel;
    }

    /**
     * Forget channel id
     */
    protected function forgetChannel() {
        unset($_SESSION['dotpay_payment_channel']);
    }
}

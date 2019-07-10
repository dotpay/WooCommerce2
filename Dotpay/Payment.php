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
 * Abstract class of skeleton of Dotpay gateway plugin
 */
abstract class Dotpay_Payment extends WC_Payment_Gateway
{
    // Dotpay IP address
    const DOTPAY_IP = '195.150.9.37';
    // Local IP address
    const LOCAL_IP = '127.0.0.1';
    // Office Dotpay IP address
    const OFFICE_IP = '77.79.195.34';
    // Dotpay URL
    const DOTPAY_URL = 'https://ssl.dotpay.pl/t2/';
    // Dotpay URL TEST
    const DOTPAY_URL_TEST = 'https://ssl.dotpay.pl/test_payment/';
    // Dotpay Seller Api URL
    const DOTPAY_SELLER_API_URL = 'https://ssl.dotpay.pl/s2/login/';
    // Dotpay Seller Api URL test
    const DOTPAY_TEST_SELLER_API_URL = 'https://ssl.dotpay.pl/test_seller/';
    // STR EMPTY
    const STR_EMPTY = '';
    // Module version
    const MODULE_VERSION = '3.2.4';


    public static $ocChannel = 248;
    public static $pvChannel = 248;
    public static $ccChannel = 248; // or 246
    public static $blikChannel = 73;
    public static $transferChannel = 11;
    public static $mpChannel = 71;




    private $orderObject = null;
    private $orderId = null;


    /**
     * Return API username
     * @return string
     */
    public function getApiUsername()
    {
        return $this->get_option('api_username');
    }

    /**
     * Return channel number of credit card
     * @return string
     */
    public function getCCnumber()
    {
        if (($this->get_option('credit_card_channel_number')) && is_numeric($this->get_option('credit_card_channel_number'))) {
            return $this->get_option('credit_card_channel_number');
        } else {
            return self::$ccChannel;
        }
    }

    /**
     * Return channel name visibility
     * @return boolean
     */
    public function getChannelNameVisiblity()
    {
        $result = 0;
        if ('yes' === $this->get_option('channel_name_show')) {
            $result = true;
        }
        return $result;
    }

    /**
     * Return API password
     * @return string
     */
    public function getApiPassword()
    {
        return $this->get_option('api_password');
    }

    /**
     * Return seller id
     * @return int
     */
    public function getSellerId()
    {
        return $this->get_option('id');
    }

	/**
	 * Return delivery type for specific shipping
	 * @return int
	 */
	public function getShippingMapping($id)
	{
		return $this->get_option('shipping_mapping_'.$id);
	}

    /**
     * Return seller pin
     * @return string
     */
    protected function getSellerPin()
    {
        return $this->get_option('pin');
    }

    public static function getDotpayChannelsList()
    {
        return array(
            'Gateway_OneClick',
            'Gateway_PV',
            'Gateway_Card',
            'Gateway_Blik',
            'Gateway_Transfer',
            'Gateway_MasterPass',
            'Gateway_Dotpay'
        );
    }

    /**
     * Return class name of the gateway, dedicated for selected channel id
     * @param int $channel channel id
     * @return string
     */
    public static function getGatewayClassNameByChannelId($channel)
    {
        switch ($channel) {
            case self::$blikChannel:
                return 'Gateway_Blik';
            case self::$transferChannel:
                return 'Gateway_Transfer';
            case self::$pvChannel:
                return 'Gateway_PV';
            case self::$ocChannel:
                return 'Gateway_OneClick';
            case self::$ccChannel:
                return 'Gateway_CC';
            case self::$mpChannel:
                return 'Gateway_MasterPass';
            default:
                return 'Gateway_Dotpay';
        }
    }

    /**
     * Return flag, if test mode is enabled
     * @return boolean
     */
    public function isTestMode()
    {
        $result = false;
        if ('yes' === $this->get_option('test')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return url to Dotpay payment server
     * @return string
     */
    public function getPaymentUrl()
    {
        $dotpay_url = self::DOTPAY_URL;
        if ($this->isTestMode()) {
            $dotpay_url = self::DOTPAY_URL_TEST;
        }

        return $dotpay_url;
    }

    /**
     * Return value for control field
     * @return string
     * @param full|null $full - set 'control' to sent
     */
    function getControl($full = null)
    {
        $order = $this->getOrder();
        if ($full == 'full') {
            return $this->getLegacyOrderId($order) . '|domain:' . $_SERVER['SERVER_NAME'] . '|WC-module:' . self::MODULE_VERSION;
        } else {
            return $this->getLegacyOrderId($order);
        }
    }

    /**
     * Return value for p_info field
     * @return string
     */
    public function getPinfo()
    {
        return __('Shop - ', 'dotpay-payment-gateway') . $_SERVER['HTTP_HOST'];
    }

    /**
     * Return amount of order
     * @return float
     */
    public function getOrderAmount()
    {
        return $this->getFormatAmount($this->getOrder()->get_total());
    }

    /**
     * Return amount of cart
     * @return float
     */
    public function getCartAmount()
    {
        global $woocommerce;
        return $this->getFormatAmount($woocommerce->cart->total);
    }

    /**
     * Return amount of order or card if it's available
     * @return float
     */
    public function getAmountForWidget()
    {
        $orderPay = get_query_var('order-pay');
        $order = $this->getOrder();
        $id = $this->getLegacyOrderId($order);
        if ($id == null && !empty($orderPay)) {
            $this->setOrderId(get_query_var('order-pay'));
        }
        if ($id != null) {
            return $this->getOrderAmount();
        } else {
            return $this->getCartAmount();
        }
    }

    /**
     * Return currency name
     * @return string
     */
    public function getCurrency()
    {
        return get_woocommerce_currency();
    }

    /**
     * Return payment description
     * @return string
     */
    public function getDescription()
    {
        return __('Order ID: ', 'dotpay-payment-gateway') . esc_attr($this->getLegacyOrderId($this->getOrder()));
    }

    /**
     * Return payment language name
     * @return string
     */
    protected function getPaymentLang()
    {

        $language = get_bloginfo('language');
        $wp_dotpay_lang = '';

        if (is_string($language)) {
            $languageArray = explode('-', $language);
            if (isset($languageArray[0])) {
                $languageLower = strtolower($languageArray[0]);
                $wp_dotpay_lang = $languageLower;
            }
        }

        if ($wp_dotpay_lang == 'pl') {
            $dotpay_lang = 'pl';
        } else {
            if (!in_array($languageLower, $this->getAcceptLang())) {
                $dotpay_lang = 'en';
            } else {
                $dotpay_lang = $languageLower;
            }
        }

        return $dotpay_lang;
    }


    /**
     * Return url where Dotpay could do a redirection after payment making
     * @return string
     */
    public function getUrl()
    {
        $page = new Dotpay_Page(DOTPAY_STATUS_PNAME);
        return $page->getUrl();
    }

    /**
     * Return url for page with order summary
     * @return string
     */
    public function getOrderSummaryUrl()
    {
        return $this->get_return_url($this->getOrder());
    }

    /**
     * Return url to payment confirmation by Dotpay
     * @return string
     */
    public function getUrlc()
    {
        $http = 'http:';
        if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS'] == "on" || $_SERVER['HTTPS']) == "1")) {
            $http = 'https:';
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            $http = 'https:';
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $http = 'https:';
        }
        return str_replace('https:', $http, add_query_arg('wc-api', $this->id . '_confirm', home_url('/')));
    }

    /**
     * Return Dotpay api version
     * @return string
     */
    public function getApiVersion()
    {
        return 'dev';
    }

    /**
     * Return ip address from is the confirmation request.
     */

    public function getClientIp($list_ip = null)
    {
        $ipaddress = '';
        // CloudFlare support
        if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
            // Validate IP address (IPv4/IPv6)
            if (filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
                $ipaddress = $_SERVER['HTTP_CF_CONNECTING_IP'];
                return $ipaddress;
            }
        }
        if (array_key_exists('X-Forwarded-For', $_SERVER)) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $_SERVER['X-Forwarded-For'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $ipaddress = $ips[0];
            } else {
                $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }
        } else {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        }


        if (isset($list_ip) && $list_ip != null) {
            if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                return  $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else if (array_key_exists('HTTP_CF_CONNECTING_IP', $_SERVER)) {
                return $_SERVER["HTTP_CF_CONNECTING_IP"];
            } else if (array_key_exists('REMOTE_ADDR', $_SERVER)) {
                return $_SERVER["REMOTE_ADDR"];
            }
        } else {
            return $ipaddress;
        }
    }



    /**
     * Return customer firstname
     * @return string
     */
    public function getFirstname()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_first_name')) {
            $firstName = esc_attr($order->get_billing_first_name());
        } else {
            $firstName = esc_attr($order->billing_first_name);
        }
        //allowed only: letters, digits, spaces, symbols _-.,'
        $firstName = preg_replace('/[^\w _-]/u', '', $firstName);
        $firstName1 = html_entity_decode($firstName, ENT_QUOTES, 'UTF-8');

        return $firstName1;
    }

    /**
     * Return customer lastname
     * @return string
     */
    public function getLastname()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_last_name')) {
            $lastName = esc_attr($order->get_billing_last_name());
        } else {
            $lastName = esc_attr($order->billing_last_name);
        }
        //allowed only: letters, digits, spaces, symbols _-.,'
        $lastName = preg_replace('/[^\w _-]/u', '', $lastName);
        $lastName1 = html_entity_decode($lastName, ENT_QUOTES, 'UTF-8');
        return $lastName1;
    }

    /**
     * Return customer email
     * @return string
     */
    public function getEmail()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_email')) {
            $email = esc_attr($order->get_billing_email());
        } else {
            $email = esc_attr($order->billing_email);
        }
        return $email;
    }

    /**
     * Return customer phone
     * @return string
     */
    public function getPhone()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_phone')) {
            $phone = esc_attr($order->get_billing_phone());
        } else {
            $phone = esc_attr($order->billing_phone);
        }
        $phone = str_replace(' ', '', $phone);
        $phone = str_replace('+', '', $phone);
        return $phone;
    }

    /**
     * Return customer city
     * @return string
     */
    public function getCity()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_city')) {
            $city = esc_attr($order->get_billing_city());
        } else {
            $city = esc_attr($order->billing_city);
        }
        //allowed only: letters, digits, spaces, symbols _-.,'
        $city = preg_replace('/[^.\w \'_-]/u', '', $city);
        $city1 = html_entity_decode($city, ENT_QUOTES, 'UTF-8');

        return $city1;
    }

    /**
     * Return customer postcode
     * @return string
     */
    public function getPostcode()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_postcode')) {
            $postcode = esc_attr($order->get_billing_postcode());
        } else {
            $postcode = esc_attr($order->billing_postcode);
        }
        if (empty($postcode)) {
            return $postcode;
        }
        if (preg_match('/^\d{2}\-\d{3}$/', $postcode) == 0 && strtolower($this->getCountry()) == 'pl') {
            $postcode = str_replace('-', '', $postcode);
            $postcode = substr($postcode, 0, 2) . '-' . substr($postcode, 2, 3);
        }
        return $postcode;
    }

    /**
     * Return customer country
     * @return string
     */
    public function getCountry()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_country')) {
            $country = $order->get_billing_country();
        } else {
            $country = $order->billing_country;
        }
        return esc_attr(strtoupper($country));
    }

    /**
     * Return customer street and house number
     * @return array
     */
    public function getStreetAndStreetN1()
    {
        $order = $this->getOrder();
        if (method_exists($order, 'get_billing_address_1')) {
            $street = esc_attr($order->get_billing_address_1());
        } else {
            $street = esc_attr($order->billing_address_1);
        }
        //allowed only: letters, digits, spaces, symbols _-.,'
        $street = preg_replace('/[^.\w \'_-]/u', '', $street);
        $street1 = html_entity_decode($street, ENT_QUOTES, 'UTF-8');

        if (method_exists($order, 'get_billing_address_2')) {
            $street_n1 = esc_attr($order->get_billing_address_2());
        } else {
            $street_n1 = esc_attr($order->billing_address_2);
        }

        if (empty($street_n1)) {
            preg_match("/\s[\w\d\/_\-]{0,30}$/", $street1, $matches);
            if (count($matches) > 0) {
                $street_n1 = trim($matches[0]);
                $street1 = str_replace($matches[0], '', $street1);
            }
        }

        if (!empty($street_n1)) {
            $building_numberRO = $street_n1;
        } else {
            $building_numberRO = " ";  //this field may not be blank in register order
        }

        return array(
            'street' => $street1,
            'street_n1' => $building_numberRO
        );
    }

	/**
	 * Return customer shipping city
	 * @return string
	 */
	public function getShippingCity()
	{
		$order = $this->getOrder();
		if (method_exists($order, 'get_shipping_city')) {
			$city = esc_attr($order->get_shipping_city());
		} else {
			$city = esc_attr($order->shipping_city);
		}
		//allowed only: letters, digits, spaces, symbols _-.,'
		$city = preg_replace('/[^.\w \'_-]/u', '', $city);
		$city1 = html_entity_decode($city, ENT_QUOTES, 'UTF-8');

		return $city1;
	}

	/**
	 * Return customer shipping postcode
	 * @return string
	 */
	public function getShippingPostcode()
	{
		$order = $this->getOrder();
		if (method_exists($order, 'get_shipping_postcode')) {
			$postcode = esc_attr($order->get_shipping_postcode());
		} else {
			$postcode = esc_attr($order->shipping_postcode);
		}
		if (empty($postcode)) {
			return $postcode;
		}
		if (preg_match('/^\d{2}\-\d{3}$/', $postcode) == 0 && strtolower($this->getShippingCountry()) == 'pl') {
			$postcode = str_replace('-', '', $postcode);
			$postcode = substr($postcode, 0, 2) . '-' . substr($postcode, 2, 3);
		}
		return $postcode;
	}

	/**
	 * Return customer shipping country
	 * @return string
	 */
	public function getShippingCountry()
	{
		$order = $this->getOrder();
		if (method_exists($order, 'get_shipping_country')) {
			$country = $order->get_shipping_country();
		} else {
			$country = $order->shipping_country;
		}
		return esc_attr(strtoupper($country));
	}

	/**
	 * Return customer shipping street and house number
	 * @return array
	 */
	public function getShippingStreetAndStreetN1()
	{
		$order = $this->getOrder();
		if (method_exists($order, 'get_shipping_address_1')) {
			$street = esc_attr($order->get_shipping_address_1());
		} else {
			$street = esc_attr($order->shipping_address_1);
		}
		//allowed only: letters, digits, spaces, symbols _-.,'
		$street = preg_replace('/[^.\w \'_-]/u', '', $street);
		$street1 = html_entity_decode($street, ENT_QUOTES, 'UTF-8');

		if (method_exists($order, 'get_shipping_address_2')) {
			$street_n1 = esc_attr($order->get_shipping_address_2());
		} else {
			$street_n1 = esc_attr($order->shipping_address_2);
		}

		if (empty($street_n1)) {
			preg_match("/\s[\w\d\/_\-]{0,30}$/", $street1, $matches);
			if (count($matches) > 0) {
				$street_n1 = trim($matches[0]);
				$street1 = str_replace($matches[0], '', $street1);
			}
		}

		if (!empty($street_n1)) {
			$building_numberRO = $street_n1;
		} else {
			$building_numberRO = " ";  //this field may not be blank in register order
		}

		return array(
			'street' => $street1,
			'street_n1' => $building_numberRO
		);
	}

    /**
     * Return array of languages that are accepted by Dotpay
     * @return array
     */
    public function getAcceptLang()
    {
        return array(
            'pl',
            'en',
            'de',
            'it',
            'fr',
            'es',
            'cz',
            'cs',
            'ru',
            'hu',
            'ro',
            'uk'
        );
    }

    /**
     * Returns Dotpay seller Api url
     * @return string
     */
    public function getSellerApiUrl()
    {
        $dotSellerApi = self::DOTPAY_SELLER_API_URL;
        if ($this->isTestMode()) {
            $dotSellerApi = self::DOTPAY_TEST_SELLER_API_URL;
        }

        return $dotSellerApi;
    }

    /**
     * Returns Dotpay payment Api url
     * @return string
     */
    public function getPaymentChannelsUrl()
    {
        return $this->getPaymentUrl() . 'payment_api/v1/channels/';
    }

    /**
     *
     * @param float $amount
     * @return float
     */
    public function getFormatAmount($amount)
    {
        return number_format(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $amount)), 2, '.', '');
    }

    /**
     * Check, if currently currency is exist in the prameter
     * @param string $allow_currency_form list of currencies
     * @return boolean
     */
    protected function isDotSelectedCurrency($allow_currency_form)
    {
        $result = false;
        $payment_currency = $this->getCurrency();
        $allow_currency = str_replace(';', ',', $allow_currency_form);
        $allow_currency = strtoupper(str_replace(' ', '', $allow_currency));
        $allow_currency_array =  explode(",", trim($allow_currency));

        if (in_array(strtoupper($payment_currency), $allow_currency_array)) {
            $result = true;
        }

        return $result;
    }

    /**
     * Return Dotpay channels, which are availaible for the given amount as a parameter
     * @param float $amount amount
     * @return boolean
     */
    public function getDotpayChannels($amount)
    {
        $dotpay_url = $this->getPaymentChannelsUrl();
        $payment_currency = $this->getCurrency();

        $dotpay_id = $this->get_option('id');

        $order_amount = $this->getFormatAmount($amount);

        $dotpay_lang = $this->getPaymentLang();

        $curl_url = "{$dotpay_url}";
        $curl_url .= "?currency={$payment_currency}";
        $curl_url .= "&id={$dotpay_id}";
        $curl_url .= "&amount={$order_amount}";
        $curl_url .= "&lang={$dotpay_lang}";
        /**
         * curl
         */
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_URL, $curl_url);
            curl_setopt($ch, CURLOPT_REFERER, $curl_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $resultJson = curl_exec($ch);
        } catch (Exception $exc) {
            $resultJson = false;
        }

        if ($ch) {
            curl_close($ch);
        }

        return $resultJson;
    }

    /**
     * Returns channel data, if payment channel is active for order data
     * @param type $id channel id
     * @return array|false
     */
    public function getChannelData($id)
    {
        $resultJson = $this->getDotpayChannels($this->getOrderAmount());
        if (false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id'] == $id) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }


    /**
     * Return Dotpay agreement for the given amount and type
     * @param float $amount amount
     * @param string $what type of agreements
     * @return string
     */
    protected function getDotpayAgreement($amount, $what)
    {
        $resultStr = '';

        $resultJson = $this->getDotpayChannels($amount);

        if (false !== $resultJson) {
            $result = json_decode($resultJson, true);

            if (isset($result['forms']) && is_array($result['forms'])) {
                foreach ($result['forms'] as $forms) {
                    if (isset($forms['fields']) && is_array($forms['fields'])) {
                        foreach ($forms['fields'] as $forms1) {
                            if ($forms1['name'] == $what) {
                                $resultStr = $forms1['description_html'];
                            }
                        }
                    }
                }
            }
        }

        return $resultStr;
    }

    /**
     * Returns channel name
     * @param type $id channel id
     * @return array|false
     */

    public function getChannelName($id)
    {

        $resultJson = $this->getDotpayChannels('1000');
        if (false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id'] == $id) {
                        return $channel;
                    }
                }
            }
        }
        return false;
    }


    /**
     * Return path to file with payment form
     * @return string
     */
    public function getFormPath()
    {
        return WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'form/' . str_replace('Dotpay_', '', $this->id) . '.phtml';
    }

    public function getFullFormPath()
    {
        return $_SERVER['HTTP_ORIGIN'] . WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'form/' . str_replace('Dotpay_', '', $this->id) . '.phtml';
    }

    /**
     * Return path to template dir
     * @return string
     */
    public function getTemplatesPath()
    {
        return WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'templates/';
    }

    /**
     * Return path to resource dir
     * @return strin
     */
    public function getResourcePath()
    {
        return WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/';
    }

    /**
     * Return rendered HTML from tamplate file
     * @param string $file name of template file
     * @return string
     */
    public function render($file)
    {
        ob_start();
        include($this->getTemplatesPath() . $file);
        return ob_get_clean();
    }

    /**
     * Persist order id
     * @param int $orderId order id
     */
    protected function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        $_SESSION['dotpay_payment_order_id'] = $orderId;
    }

    /**
     * Return order object with last order
     * @return WC_Order
     */
    protected function getOrder()
    {
        if ($this->orderObject == null || $this->getLegacyOrderId($this->orderObject) == null) {
            if ($this->orderId == null) {
                if (isset($_SESSION['dotpay_payment_order_id'])) {
                    $this->orderId = $_SESSION['dotpay_payment_order_id'];
                }
            }
            $this->orderObject = new WC_Order($this->orderId);
        }
        return $this->orderObject;
    }

    /**
     * Forget saved order
     */
    protected function forgetOrder()
    {
        unset($_SESSION['dotpay_payment_order_id']);
        $this->orderObject = null;
        $this->orderId = null;
    }

    /**
     * Return currently cart
     * @global type $woocommerce WOOCOMMERCE object
     * @return WC_Cart
     */
    protected function getCart()
    {
        global $woocommerce;
        return $woocommerce->cart;
    }

    /**
     * Return param, which was sending to page by GET or POST method
     * @param string $name name of param
     * @param mixed $default default value
     * @return boolean
     */
    public function getParam($name, $default = false)
    {
        if (!isset($name) || empty($name) || !is_string($name)) {
            return false;
        }
        $ret = (isset($_POST[$name]) ? $_POST[$name] : (isset($_GET[$name]) ? $_GET[$name] : $default));
        if (is_string($ret)) {
            return addslashes($ret);
        }
        return $ret;
    }

    private function getLegacyOrderId($orderObject)
    {
        if (method_exists($orderObject, 'get_id')) {
            return $orderObject->get_id();
        } else {
            return $orderObject->id;
        }
    }
}

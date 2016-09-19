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
abstract class Dotpay_Payment extends WC_Payment_Gateway {
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
    const MODULE_VERSION = '3.0.1';
    
    public static $ocChannel = 248;
    public static $pvChannel = 248;
    public static $ccChannel = 246;
    public static $blikChannel = 73;
    public static $mpChannel = 71;
    
    private $orderObject = null;
    private $orderId = null;
    
    /**
     * Return API username
     * @return string
     */
    public function getApiUsername() {
        return $this->get_option('api_username');
    }
    
    /**
     * Return API password
     * @return string
     */
    public function getApiPassword() {
        return $this->get_option('api_password');
    }
    
    /**
     * Return seller id
     * @return int
     */
    public function getSellerId() {
        return $this->get_option('id');
    }
    
    /**
     * Return seller pin
     * @return string
     */
    protected function getSellerPin() {
        return $this->get_option('pin');
    }

    /**
     * Return class name of the gteway, dedicated for selected channel id
     * @param int $channel channel id
     * @return string
     */
    public static function getGatewayClassNameByChannelId($channel) {
        switch($channel) {
            case self::$blikChannel:
                return 'Gateway_Blik';
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
    protected function isTestMode() {
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
    public function getPaymentUrl() {
        $dotpay_url = self::DOTPAY_URL;
        if ($this->isTestMode()) {
            $dotpay_url = self::DOTPAY_URL_TEST;
        }
        
        return $dotpay_url;
    }
    
    /**
     * Return value for control field
     * @return string
     */
    public function getControl() {
        return $this->getOrder()->id;
    }

    /**
     * Return value for p_info field
     * @return string
     */
    public function getPinfo() {
        return __('Shop - ', 'dotpay-payment-gateway') . $_SERVER['HTTP_HOST'];
    }
    
    /**
     * Return amount of order
     * @return float
     */
    public function getOrderAmount() {
        return $this->getFormatAmount($this->getOrder()->get_total());
    }
    
    /**
     * Return amount of cart
     * @return float
     */
    public function getCartAmount() {
        global $woocommerce;
        return $this->getFormatAmount($woocommerce->cart->get_total());
    }
    
    /**
     * Return currency name
     * @return string
     */
    public function getCurrency() {
        return get_woocommerce_currency();
    }
    
    /**
     * Return payment description
     * @return string
     */
    public function getDescription() {
        return __('Order ID: ', 'dotpay-payment-gateway') . esc_attr($this->getOrder()->id);
    }
    
    /**
     * Return payment language name
     * @return string
     */
    protected function getPaymentLang() {
        $dotpay_lang = 'pl';
        if (!$this->isTestMode()) {
            $language = get_bloginfo('language');
            if(is_string($language)) {
                $languageArray = explode('-', $language);
                if(isset($languageArray[0])) {
                    $languageLower = strtolower($languageArray[0]);
                    if(in_array($languageLower, $this->getAcceptLang())) {
                        $dotpay_lang = $languageLower;
                    }
                }
            }
        }
        
        return $dotpay_lang;
    }
    
    /**
     * Return url where Dotpay could do a redirection after payment making
     * @return string
     */
    public function getUrl() {
        $page = new Dotpay_Page(DOTPAY_STATUS_PNAME);
        return $page->getUrl();
    }
    
    /**
     * Return url for page with order summary
     * @return string
     */
    public function getOrderSummaryUrl() {
        return $this->get_return_url($this->getOrder());
    }

    /**
     * Return url to payment confirmation by Dotpay
     * @return string
     */
    public function getUrlc() {
        $http = 'http:';
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on")
            $http = 'https:';
        return str_replace('https:', $http, add_query_arg('wc-api', $this->id.'_confirm', home_url('/')));
    }
    
    /**
     * Return Dotpay api version
     * @return string
     */
    public function getApiVersion() {
        return 'dev';
    }
    
    /**
     * Return customer firstname
     * @return string
     */
    public function getFirstname() {
        return esc_attr($this->getOrder()->billing_first_name);
    }
    
    /**
     * Return customer lastname
     * @return string
     */
    public function getLastname() {
        return esc_attr($this->getOrder()->billing_last_name);
    }
    
    /**
     * Return customer email
     * @return string
     */
    public function getEmail() {
        return esc_attr($this->getOrder()->billing_email);
    }
    
    /**
     * Return customer phone
     * @return string
     */
    public function getPhone() {
        return esc_attr($this->getOrder()->billing_phone);
    }
    
    /**
     * Return customer city
     * @return string
     */
    public function getCity() {
        return esc_attr($this->getOrder()->billing_city);
    }
    
    /**
     * Return customer postcode
     * @return string
     */
    public function getPostcode() {
        $postcode = esc_attr($this->getOrder()->billing_postcode);
        if(empty($postcode))
            return $postcode;
        if(strpos('-', $postcode) === false && $this->getCountry() == 'pl') {
            $part1 = substr($postcode, 0, 2);
            $part2 = substr($postcode, 2, 3);
            $postcode = $part1.'-'.$part2;
        }
        return $postcode;
    }
    
    /**
     * Return customer country
     * @return string
     */
    public function getCountry() {
        return esc_attr(strtoupper($this->getOrder()->billing_country));
    }
    
    /**
     * Return customer street and house number
     * @return string
     */
    public function getStreetAndStreetN1() {
        $street = esc_attr($this->getOrder()->billing_address_1);
        $street_n1 = esc_attr($this->getOrder()->billing_address_2);
        
        if(empty($street_n1))
        {
            preg_match("/\s[\w\d\/_\-]{0,30}$/", $street, $matches);
            if(count($matches)>0)
            {
                $street_n1 = trim($matches[0]);
                $street = str_replace($matches[0], '', $street);
            }
        }
        
        return array(
            'street' => $street,
            'street_n1' => $street_n1
        );
    }
    
    /**
     * Return array of languages that are accepted by Dotpay
     * @return array
     */
    public function getAcceptLang() {
        return array(
            'pl',
            'en',
            'de',
            'it',
            'fr',
            'es',
            'cz',
            'ru',
            'bg'
        );
    }
    
    /**
     * Returns Dotpay seller Api url
     * @return string
     */
    public function getSellerApiUrl() {
        $dotSellerApi = self::DOTPAY_SELLER_API_URL;
        if($this->isTestMode()) {
            $dotSellerApi = self::DOTPAY_TEST_SELLER_API_URL;
        }
        
        return $dotSellerApi;
    }
    
    /**
     * Returns Dotpay payment Api url
     * @return string
     */
    public function getPaymentChannelsUrl() {
        return $this->getPaymentUrl().'payment_api/v1/channels/';
    }
    
    /**
     * 
     * @param float $amount
     * @return float
     */
    public function getFormatAmount($amount) {
        return number_format(preg_replace('/[^0-9.]/', '', str_replace(',', '.', $amount)), 2, '.', '');
    }
    
    /**
     * Check, if currently currency is exist in the prameter
     * @param string $allow_currency_form list of currencies
     * @return boolean
     */
    protected function isDotSelectedCurrency($allow_currency_form) {
        $result = false;
        $payment_currency = $this->getCurrency();
        $allow_currency = str_replace(';', ',', $allow_currency_form);
        $allow_currency = strtoupper(str_replace(' ', '', $allow_currency));
        $allow_currency_array =  explode(",",trim($allow_currency));
        
        if(in_array(strtoupper($payment_currency), $allow_currency_array)) {
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Return Dotpay channels, which are availaible for the given amount as a parameter
     * @param float $amount amount
     * @return boolean
     */
    public function getDotpayChannels($amount) {
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
        
        if($ch) {
            curl_close($ch);
        }
        
        return $resultJson;
    }
    
    /**
     * Returns channel data, if payment channel is active for order data
     * @param type $id channel id
     * @return array|false
     */
    public function getChannelData($id) {
    $resultJson = $this->getDotpayChannels($this->getOrderAmount());
        if(false !== $resultJson) {
            $result = json_decode($resultJson, true);
            if (isset($result['channels']) && is_array($result['channels'])) {
                foreach ($result['channels'] as $channel) {
                    if (isset($channel['id']) && $channel['id']==$id) {
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
    protected function getDotpayAgreement($amount, $what) {
        $resultStr = '';
        
        $resultJson = $this->getDotpayChannels($amount);
        
        if(false !== $resultJson) {
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
     * Return path to file with payment form
     * @return string
     */
    public function getFormPath() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'form/'.str_replace('Dotpay_', '', $this->id).'.phtml';
    }
    
    public function getFullFormPath() {
        return $_SERVER['HTTP_ORIGIN'].WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'form/'.str_replace('Dotpay_', '', $this->id).'.phtml';
    }
    
    /**
     * Return path to template dir
     * @return string
     */
    public function getTemplatesPath() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_DIR . 'templates/';
    }
    
    /**
     * Return rendered HTML from tamplate file
     * @param string $file name of template file
     * @return string
     */
    public function render($file) {
        ob_start();
        include($this->getTemplatesPath().$file);
        return ob_get_clean();
    }
    
    /**
     * Persist order id
     * @param int $orderId order id
     */
    protected function setOrderId($orderId) {
        $this->orderId = $orderId;
        $_SESSION['dotpay_payment_order_id'] = $orderId;
    }

    /**
     * Return order object with last order
     * @return WC_Order
     */
    protected function getOrder() {
        if($this->orderObject == null) {
            if($this->orderId == null)
                $this->orderId = $_SESSION['dotpay_payment_order_id'];
            $this->orderObject = new WC_Order($this->orderId);
        }
        return $this->orderObject;
    }
    
    /**
     * Forget saved order
     */
    protected function forgetOrder() {
        unset($_SESSION['dotpay_payment_order_id']);
        $this->orderObject = null;
        $this->orderId = null;
    }
    
    /**
     * Return currently cart
     * @global type $woocommerce WOOCOMMERCE object
     * @return WC_Cart
     */
    protected function getCart() {
        global $woocommerce;
        return $woocommerce->cart;
    }
    
    /**
     * Return param, which was sending to page by GET or POST method
     * @param string $name name of param
     * @param mixed $default default value
     * @return boolean
     */
    public function getParam($name, $default = false) {
        if (!isset($name) || empty($name) || !is_string($name)) {
            return false;
        }
        $ret = (isset($_POST[$name]) ? $_POST[$name] : (isset($_GET[$name]) ? $_GET[$name] : $default));
        if (is_string($ret)) {
            return addslashes($ret);
        }
        return $ret;
    }
}

<?php

abstract class WC_Gateway_Dotpay_Abstract extends WC_Payment_Gateway {

    // Check Real IP if server is proxy, balancer...
    const CHECK_REAL_IP = false;
    // Dotpay IP address
    const DOTPAY_IP = '195.150.9.37';
    // Local IP address
    const LOCAL_IP = '127.0.0.1';
    // Dotpay URL
    const DOTPAY_URL = 'https://ssl.dotpay.pl/t2/';
    // Dotpay URL TEST
    const DOTPAY_URL_TEST = 'https://ssl.dotpay.pl/test_payment/';
    // Gateway name
    const PAYMENT_METHOD = 'dotpay';
    // STR EMPTY
    const STR_EMPTY = '';
    
    protected $dotpayAgreements = true;
    
    protected $agreementByLaw = '';
    
    protected $agreementPersonalData = '';

    protected $fieldsResponse = array(
        'id' => self::STR_EMPTY,
        'operation_number' => self::STR_EMPTY,
        'operation_type' => self::STR_EMPTY,
        'operation_status' => self::STR_EMPTY,
        'operation_amount' => self::STR_EMPTY,
        'operation_currency' => self::STR_EMPTY,
        'operation_withdrawal_amount' => self::STR_EMPTY,
        'operation_commission_amount' => self::STR_EMPTY,
        'operation_original_amount' => self::STR_EMPTY,
        'operation_original_currency' => self::STR_EMPTY,
        'operation_datetime' => self::STR_EMPTY,
        'operation_related_number' => self::STR_EMPTY,
        'control' => self::STR_EMPTY,
        'description' => self::STR_EMPTY,
        'email' => self::STR_EMPTY,
        'p_info' => self::STR_EMPTY,
        'p_email' => self::STR_EMPTY,
        'channel' => self::STR_EMPTY,
        'channel_country' => self::STR_EMPTY,
        'geoip_country' => self::STR_EMPTY,
        'signature' => self::STR_EMPTY
    );

    /**
     * initialise gateway with custom settings
     */
    public function __construct() {
        $this->id = self::PAYMENT_METHOD;
        $this->icon = $this->getIconDotpay();
        $this->has_fields = false;
        $this->title = 'Dotpay';
        $this->description = __('Credit card payment via Dotpay', 'dotpay-payment-gateway');
        $this->init_form_fields();
        $this->init_settings();
        
        /**
         * Actions
         */
        $this->init_dotpay_actions();
    }
    
    /**
     * 
     */
    protected function init_dotpay_actions() {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_dotpay_response'));
        add_action('woocommerce_api_' . strtolower(get_class($this)) . '_2', array($this, 'build_dotpay_signature'));
    }
    
    protected function getIconMasterPass() {
        return WOOCOMMERCE_DOTPAY_PLUGIN_URL . 'resources/images/MasterPass.png';
    }
    
    protected function getIconBLIK() {
        return WOOCOMMERCE_DOTPAY_PLUGIN_URL . 'resources/images/BLIK.png';
    }
    
    protected function getIconDotpay() {
        return WOOCOMMERCE_DOTPAY_PLUGIN_URL . 'resources/images/dotpay.png';
    }

    public function init_form_fields() {
        $this->form_fields = WC_Gateway_Dotpay_Include('/includes/settings-dotpay.php');
    }

    protected function getDotpayUrl() {
        $dotpay_url = self::DOTPAY_URL;
        if ($this->isDotTest()) {
            $dotpay_url = self::DOTPAY_URL_TEST;
        }
        
        return $dotpay_url;
    }
    
    protected function isDotTest() {
        $result = false;
        if ('yes' === $this->get_option('dotpay_test')) {
            $result = true;
        }
        
        return $result;
    }
    
    protected function isDotWidget() {
        $result = false;
        if ('yes' === $this->get_option('dotpay_channel_show')) {
            $result = true;
        }
        if(false === $this->dotpayAgreements) {
            $result = false;
        }
        
        return $result;
    }
    
    protected function isDotSecurity() {
        $result = false;
        if ('yes' === $this->get_option('dotpay_security')) {
            $result = true;
        }
        
        return $result;
    }
    
    protected function isDotMasterPass() {
        $result = false;
        if ('yes' === $this->get_option('dotpay_masterpass_show')) {
            $result = true;
        }
        if(false === $this->dotpayAgreements) {
            $result = false;
        }
        
        return $result;
    }
    
    protected function isDotBlik() {
        $result = false;
        if ('yes' === $this->get_option('dotpay_blik_show')) {
            $result = true;
        }
        if(false === $this->dotpayAgreements) {
            $result = false;
        }
        
        return $result;
    }
    
    protected function getDotpayApiVersion() {
        return 'dev';
    }
    
    protected function getPaymentCurrency() {
        $payment_currency = get_woocommerce_currency();
        if ($this->isDotTest()) {
            $payment_currency = 'PLN';
        }
        
        return $payment_currency;
    }
    
    protected function getAmmount($amount) {
        $roundAmount = round($amount, 2);
        $formatAmount = sprintf("%01.2f", $roundAmount);
        
        return $formatAmount;
    }
    
    protected function getOrderAmmount($order) {
        return $this->getAmmount($order->get_total());
    }
    
    protected function getPaymentLang() {
        $dotpay_lang = 'pl';
        if (!$this->isDotTest()) {
            $language = get_bloginfo('language');
            if(is_string($language)) {
                $languageArray = explode('-', $language);
                if(isset($languageArray[0])) {
                    $languageLower = strtolower($languageArray[0]);
                    if(in_array($languageLower, $this->getDotpayAcceptLang())) {
                        $dotpay_lang = $languageLower;
                    }
                }
            }
        }
        
        return $dotpay_lang;
    }

    protected function getPostParams() {
        foreach ($this->fieldsResponse as $k => &$v) {
            $value = isset($_POST[$k]) ? $_POST[$k] : '';
            if ($value !== '') {
                $v = $value;
            }
        }
    }

    protected function checkRemoteIP() {
        $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $realIp = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '0.0.0.0';

        if ($remoteIp === self::DOTPAY_IP) {
            /**
             * OK NOP
             */
        } elseif (self::CHECK_REAL_IP && $realIp === self::DOTPAY_IP && $remoteIp === self::LOCAL_IP) {
            /**
             * OK NOP
             */
        } else {
            die('FAIL IP: access denied');
        }
    }

    protected function getOrder($idOrder) {
        $order = new WC_Order($idOrder);
        if (!$order) {
            die('FAIL ORDER: not exist');
        }

        return $order;
    }

    protected function checkCurrency($order) {
        $currencyOrder = $order->get_order_currency();
        $currencyResponse = $this->fieldsResponse['operation_original_currency'];

        if ($currencyOrder !== $currencyResponse) {
            die('FAIL CURRENCY');
        }
    }

    protected function checkAmount($order) {
        $amount = round($order->get_total(), 2);
        $amountOrder = sprintf("%01.2f", $amount);
        $amountResponse = $this->fieldsResponse['operation_original_amount'];

        if ($amountOrder !== $amountResponse) {
            die('FAIL AMOUNT');
        }
    }

    protected function checkEmail($order) {
        $emailBilling = $order->billing_email;
        $emailResponse = $this->fieldsResponse['email'];

        if ($emailBilling !== $emailResponse) {
            die('FAIL EMAIL');
        }
    }

    protected function checkSignature($order) {
        $hashDotpay = $this->fieldsResponse['signature'];
        $hashCalculate = $this->calculateSignature($order);

        if ($hashDotpay !== $hashCalculate) {
            die('FAIL SIGNATURE');
        }
    }

    protected function calculateSignature($order) {
        $string = '';
        $string .= $this->get_option('dotpay_pin');

        foreach ($this->fieldsResponse as $k => $v) {
            switch ($k) {
                case 'signature':
                    /**
                     * NOP
                     */
                    break;
                default:
                    $string .= $v;
            }
        }

        return hash('sha256', $string);
    }

    /**
     * accept langs
     * @return array
     */
    public function getDotpayAcceptLang() {
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
    
    protected function getDotpayAgreement($order, $what) {
        $resultStr = '';
        
        $dotpay_url = $this->getDotpayUrl();
        $payment_currency = $this->getPaymentCurrency();
        
        $dotpay_id = $this->get_option('dotpay_id');
        
        $order_amount = $this->getOrderAmmount($order);
        
        $dotpay_lang = $this->getPaymentLang();
        
        $curl_url = "{$dotpay_url}payment_api/channels/";
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
        
        /**
         * 
         */
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
        
        if($resultStr === '') {
            $this->dotpayAgreements = false;
        }

        return $resultStr;
    }
    
    protected function buildSignature4Request(array $allHiddenFields, $type, $channel = null, $blik = null) {
        
        switch ($type) {
            case 'mp':
                $hiddenFields = $allHiddenFields[$type]['fields'];
                break;
            case 'blik':
                $hiddenFields = $allHiddenFields[$type]['fields'];
                break;
            case 'dotpay':
            default:
                $hiddenFields = $allHiddenFields['dotpay']['fields'];
        }
        
        $fieldsRequestArray = array(
            'DOTPAY_PIN' => $this->get_option('dotpay_pin'),
            'api_version' => $this->getDotpayApiVersion(),
            'lang' => $hiddenFields['lang'],
            'DOTPAY_ID' => $hiddenFields['id'],
            'amount' => $hiddenFields['amount'],
            'currency' => $hiddenFields['currency'],
            'description' => $hiddenFields['description'],
            'control' => $hiddenFields['control'],
            'channel' => isset($hiddenFields['channel']) ? $hiddenFields['channel'] : self::STR_EMPTY,
            'ch_lock' => $hiddenFields['ch_lock'],
            'URL' => $hiddenFields['URL'],
            'type' => $hiddenFields['type'],
            'buttontext' => self::STR_EMPTY,
            'URLC' => $hiddenFields['URLC'],
            'firstname' => $hiddenFields['firstname'],
            'lastname' => $hiddenFields['lastname'],
            'email' => $hiddenFields['email'],
            'street' => $hiddenFields['street'],
            'street_n1' => $hiddenFields['street_n1'],
            'street_n2' => self::STR_EMPTY,
            'state' => self::STR_EMPTY,
            'addr3' => self::STR_EMPTY,
            'city' => $hiddenFields['city'],
            'postcode' => $hiddenFields['postcode'],
            'phone' => $hiddenFields['phone'],
            'country' => $hiddenFields['country'],
            'bylaw' => self::STR_EMPTY,
            'personal_data' => self::STR_EMPTY,
            'blik_code' => self::STR_EMPTY
        );
        
        if('mp' === $type && $this->isDotMasterPass()) {
            if(isset($channel)) {
                $fieldsRequestArray['channel'] = $channel;
            }
            $fieldsRequestArray['bylaw'] = '1';
            $fieldsRequestArray['personal_data'] = '1';
        } elseif('blik' === $type && $this->isDotBlik()) {
            if(isset($channel)) {
                $fieldsRequestArray['channel'] = $channel;
            }
            if(isset($blik)) {
                $fieldsRequestArray['blik_code'] = $blik;
            }
            $fieldsRequestArray['bylaw'] = '1';
            $fieldsRequestArray['personal_data'] = '1';
        } elseif('dotpay' === $type) {
            if(isset($channel)) {
                $fieldsRequestArray['channel'] = $channel;
            }
            if($this->isDotWidget()) {
                $fieldsRequestArray['bylaw'] = '1';
                $fieldsRequestArray['personal_data'] = '1';
            }
        }
        
        $fieldsRequestStr = implode(self::STR_EMPTY, $fieldsRequestArray);
        
        return hash('sha256', $fieldsRequestStr);
    }
    
    private function getHiddenFields($order_id) {
        /**
         * order
         */
        $order = new WC_Order($order_id);
        
        /**
         * 
         */
        $payment_currency = $this->getPaymentCurrency();
        
        /**
         * info and description
         */
        $dotpay_info = __('Shop - ', 'dotpay-payment-gateway') . $_SERVER['HTTP_HOST'];
        $dotpay_description = __('Order ID: ', 'dotpay-payment-gateway') . esc_attr($order_id);
        
        /**
         * amount
         */
        $order_amount = $this->getOrderAmmount($order);

        /**
         * lang
         */
        $dotpay_lang = $this->getPaymentLang();
        
        /**
         * url redirect and back
         */
        $return_url = $this->get_return_url($order);
        $notify_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_Dotpay', home_url('/')));
        
        /**
         * user data
         */
        $firstname = $order->billing_first_name;
        $lastname = $order->billing_last_name;
        $email = $order->billing_email;
        $phone = $order->billing_phone;
        $street = $order->billing_address_1;
        $street_n1 = $order->billing_address_2;
        $city = $order->billing_city;
        $postcode = $order->billing_postcode;
        $country = strtoupper($order->billing_country);
        
        return array(
            'id' => $this->get_option('dotpay_id'),
            'control' => esc_attr($order_id),
            'p_info' => esc_attr($dotpay_info),
            'amount' => $order_amount,
            'currency' => $payment_currency,
            'description' => esc_attr($dotpay_description),
            'lang' => $dotpay_lang,
            'URL' => $return_url,
            'ch_lock' => 0,
            'URLC' => $notify_url,
            'api_version' => $this->getDotpayApiVersion(),
            'type' => 0,
            'firstname' => esc_attr($firstname),
            'lastname' => esc_attr($lastname),
            'email' => esc_attr($email),
            'phone' => esc_attr($phone),
            'street' => esc_attr($street),
            'street_n1' => esc_attr($street_n1),
            'city' => esc_attr($city),
            'postcode' => esc_attr($postcode),
            'country' => esc_attr($country)
        );
    }
    
    protected function getHiddenFieldsDotpay($order_id) {
        $hiddenFields = $this->getHiddenFields($order_id);
        
        if($this->isDotWidget()) {
            $hiddenFields['ch_lock'] = 1;
            $hiddenFields['type'] = 4;
        }
        
        return $hiddenFields;
    }
    
    protected function getHiddenFieldsMasterPass($order_id) {
        $hiddenFields = $this->getHiddenFields($order_id);
        
        $hiddenFields['channel'] = 71;
        $hiddenFields['ch_lock'] = 1;
        $hiddenFields['type'] = 4;
        
        return $hiddenFields;
    }
    
    protected function getHiddenFieldsBlik($order_id) {
        $hiddenFields = $this->getHiddenFields($order_id);
        
        $hiddenFields['channel'] = 73;
        $hiddenFields['ch_lock'] = 1;
        $hiddenFields['type'] = 4;
        
        return $hiddenFields;
    }

}

<?php

class WC_Gateway_Dotpay extends WC_Payment_Gateway {

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
        $this->icon = WOOCOMMERCE_DOTPAY_PLUGIN_URL . 'resources/images/dotpay.png';
        $this->has_fields = false;
        $this->title = 'Dotpay';
        $this->description = __('Credit card payment via Dotpay', 'dotpay-payment-gateway');
        $this->init_form_fields();
        $this->init_settings();
        //Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_dotpay_response'));
        add_action('woocommerce_api_' . strtolower(get_class($this)) . '_2', array($this, 'build_dotpay_signature'));
    }

    public function init_form_fields() {
        $this->form_fields = WC_Gateway_Dotpay_Include('/includes/settings-dotpay.php');
    }

    public function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        $order->reduce_order_stock();

        $woocommerce->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    public function receipt_page($order) {
        echo $this->generate_dotpay_form($order);
    }
    
    protected function getDotpayUrl() {
        $dotpay_url = self::DOTPAY_URL;
        if ($this->get_option('dotpay_test') == 'yes') {
            $dotpay_url = self::DOTPAY_URL_TEST;
        }
        
        return $dotpay_url;
    }
    
    protected function getDotpayApiVersion() {
        return 'dev';
    }
    
    protected function getPaymentCurrency() {
        $payment_currency = get_woocommerce_currency();
        if ($this->get_option('dotpay_test') == 'yes') {
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
        if ($this->get_option('dotpay_test') != 'yes') {
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

    public function generate_dotpay_form($order_id) {
        $order = new WC_Order($order_id);
        
        $widget = $this->get_option('dotpay_channel_show');
        $security = $this->get_option('dotpay_security');

        $dotpay_id = $this->get_option('dotpay_id');

        $dotpay_url = $this->getDotpayUrl();
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
         * url build signature
         */
        $signature_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_Dotpay_2', home_url('/')));

        /**
         * user data
         */
        $firstname = $order->billing_first_name;
        $lastname = $order->billing_last_name;
        $email = $order->billing_email;
        
        /**
         * hidden fields
         */
        $hiddenFields = array(
            'id' => $dotpay_id,
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
            'email' => esc_attr($email)
        );
        
        /**
         * 
         */
        if($widget === 'yes') {
            /**
             * 
             */
            $hiddenFields['type'] = 4;
            $hiddenFields['ch_lock'] = 1;
            
            /**
             * 
             */
            $agreementByLaw = $this->getDotpayAgreement($order, 'bylaw');
            $agreementPersonalData = $this->getDotpayAgreement($order, 'personal_data');
            $tagP = __('You chose payment by Dotpay. Select a payment channel and click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to channel payment.', 'dotpay-payment-gateway'));
        } else {
            $agreementByLaw = '';
            $agreementPersonalData = '';
            $tagP = __('You chose payment by Dotpay. Click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to Dotpay to make payment.', 'dotpay-payment-gateway'));
        }
        
        /**
         * 
         */
        if($security === 'yes') {
            $chk = $this->buildSignature4Request($hiddenFields);
            
            $_SESSION['hiddenFields'] = $hiddenFields;
            
            $hiddenFields['CHK'] = $chk;
        }
        
        /**
         * js code
         */
        wc_enqueue_js(WC_Gateway_Dotpay_Include('/includes/block-ui.js.php', array(
            'widget' => $widget,
            'message' => $message,
            'signature_url' => $signature_url,
        )));
        
        /**
         * html code
         */
        return WC_Gateway_Dotpay_Include('/includes/form-redirect.html.php', array(
            'widget' => $widget,
            'h3' => __('Transaction Details', 'dotpay-payment-gateway'),
            'p' => $tagP,
            'agreement_bylaw' => $agreementByLaw,
            'agreement_personal_data' => $agreementPersonalData,
            'submit' => __('Continue', 'dotpay-payment-gateway'),
            'action' => esc_attr($dotpay_url),
            'hiddenFields' => $hiddenFields,
        ));
    }
    
    public function build_dotpay_signature() {
        $chk = '';
        if(isset($_SESSION['hiddenFields'])) {
            $hiddenFields = $_SESSION['hiddenFields'];
            if(isset($_POST['channel'])) {
                $channel = $_POST['channel'];
                $chk = $this->buildSignature4Request($hiddenFields, $channel);
            } else {
                $chk = $this->buildSignature4Request($hiddenFields);
            }
        }
        die($chk);
    }

    public function check_dotpay_response() {
        $this->checkRemoteIP();
        $this->getPostParams();

        /**
         * check order
         */
        $order = $this->getOrder($this->fieldsResponse['control']);

        /**
         * check currency, amount, email
         */
        $this->checkCurrency($order);
        $this->checkAmount($order);
        $this->checkEmail($order);

        /**
         * check signature
         */
        $this->checkSignature($order);

        /**
         * update status
         */
        $status = $this->fieldsResponse['operation_status'];
        $note = __("Gateway Dotpay send status {$status}.");
        switch ($status) {
            case 'completed':
                $order->update_status('completed', $note);
                break;
            case 'rejected':
                $order->update_status('cancelled', $note);
                break;
            default:
                $order->update_status('processing', $note);
        }

        /**
         * OK
         */
        die('OK');
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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_REFERER, $curl_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resultJson = curl_exec($ch);
        curl_close($ch);
        
        /**
         * 
         */
        $result = json_decode($resultJson, true);

        foreach ($result['forms'] as $forms) {
            foreach ($forms['fields'] as $forms1) {
                if ($forms1['name'] == $what) {
                    $resultStr = $forms1['description_html'];
                }
            }
        }

        return $resultStr;
    }
    
    protected function buildSignature4Request(array $hiddenFields, $channel = null) {
        $fieldsRequestArray = array(
            'DOTPAY_PIN' => $this->get_option('dotpay_pin'),
            'api_version' => $this->getDotpayApiVersion(),
            'lang' => $hiddenFields['lang'],
            'DOTPAY_ID' => $hiddenFields['id'],
            'amount' => $hiddenFields['amount'],
            'currency' => $hiddenFields['currency'],
            'description' => $hiddenFields['description'],
            'control' => $hiddenFields['control'],
            'channel' => self::STR_EMPTY,
            'ch_lock' => $hiddenFields['ch_lock'],
            'URL' => $hiddenFields['URL'],
            'type' => $hiddenFields['type'],
            'buttontext' => self::STR_EMPTY,
            'URLC' => $hiddenFields['URLC'],
            'firstname' => $hiddenFields['firstname'],
            'lastname' => $hiddenFields['lastname'],
            'email' => $hiddenFields['email'],
            'street' => self::STR_EMPTY,
            'street_n1' => self::STR_EMPTY,
            'street_n2' => self::STR_EMPTY,
            'state' => self::STR_EMPTY,
            'addr3' => self::STR_EMPTY,
            'city' => self::STR_EMPTY,
            'postcode' => self::STR_EMPTY,
            'phone' => self::STR_EMPTY,
            'country' => self::STR_EMPTY,
            'bylaw' => self::STR_EMPTY,
            'personal_data' => self::STR_EMPTY,
            'blik_code' => self::STR_EMPTY
        );
        
        $widget = $this->get_option('dotpay_channel_show');
        
        if($channel) {
            $fieldsRequestArray['channel'] = $channel;
        }
        
        if($widget) {
            $fieldsRequestArray['bylaw'] = '1';
            $fieldsRequestArray['personal_data'] = '1';
        }
        
        $fieldsRequestStr = implode(self::STR_EMPTY, $fieldsRequestArray);
        
        return hash('sha256', $fieldsRequestStr);
    }

}

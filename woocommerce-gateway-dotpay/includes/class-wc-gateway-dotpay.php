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

    protected $fieldsResponse = array(
        'id' => '',
        'operation_number' => '',
        'operation_type' => '',
        'operation_status' => '',
        'operation_amount' => '',
        'operation_currency' => '',
        'operation_withdrawal_amount' => '',
        'operation_commission_amount' => '',
        'operation_original_amount' => '',
        'operation_original_currency' => '',
        'operation_datetime' => '',
        'operation_related_number' => '',
        'control' => '',
        'description' => '',
        'email' => '',
        'p_info' => '',
        'p_email' => '',
        'channel' => '',
        'channel_country' => '',
        'geoip_country' => '',
        'signature' => ''
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
            'URLC' => $notify_url,
            'api_version' => 'dev',
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
            $message = esc_js(__('Thank you for your order. We are now redirecting you to channel to make payment.', 'dotpay-payment-gateway'));
        } else {
            $agreementByLaw = '';
            $agreementPersonalData = '';
            $tagP = __('You chose payment by Dotpay. Click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to Dotpay to make payment.', 'dotpay-payment-gateway'));
        }
        
        /**
         * js code
         */
        wc_enqueue_js(WC_Gateway_Dotpay_Include('/includes/block-ui.js.php', array(
            'widget' => $widget,
            'message' => $message,
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

}

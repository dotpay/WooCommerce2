<?php

class WC_Gateway_Dotpay extends WC_Payment_Gateway {

    // Dotpay IP address
    const DOTPAY_IP = '195.150.9.37';
    // Dotpay URL
    const DOTPAY_URL = 'https://ssl.dotpay.pl';
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

    function process_payment($order_id) {
        global $woocommerce;

        $order = new WC_Order($order_id);

        $order->reduce_order_stock();

        $woocommerce->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_payment_url(true)
        );
    }

    function receipt_page($order) {
        echo $this->generate_dotpay_form($order);
    }

    function generate_dotpay_form($order_id) {
        $order = new WC_Order($order_id);

        $dotpay_id = $this->get_option('dotpay_id');

        $dotpay_url = self::DOTPAY_URL;
        $payment_currency = get_woocommerce_currency();
        if ($this->get_option('dotpay_test') == 'yes') {
            $dotpay_url = self::DOTPAY_URL_TEST;
            $payment_currency = 'PLN';
        }

        /**
         * info and description
         */
        $dotpay_info = __('Shop - ', 'dotpay-payment-gateway') . $_SERVER['HTTP_HOST'];
        $dotpay_description = __('Order ID: ', 'dotpay-payment-gateway') . esc_attr($order_id);

        /**
         * amount
         */
        $amount = round($order->get_total(), 2);
        $order_amount = sprintf("%01.2f", $amount);

        /**
         * lang
         */
        $lang = strtolower(explode('-', get_bloginfo('language'))[0]);
        $dotpay_lang = 'pl';
        if (in_array($lang, $this->getDotpayAcceptLang())) {
            $dotpay_lang = $lang;
        }

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


        wc_enqueue_js(WC_Gateway_Dotpay_Include('/includes/block-ui.js.php', array(
            'message' => esc_js(__('Thank you for your order. We are now redirecting you to Dotpay to make payment.', 'dotpay-payment-gateway')),
        )));

        return WC_Gateway_Dotpay_Include('/includes/form-redirect.html.php', array(
            'h3' => __('Transaction Details', 'dotpay-payment-gateway'),
            'p' => __('You chose payment by Dotpay. Click Continue do proceed', 'dotpay-payment-gateway'),
            'submit' => __('Continue', 'dotpay-payment-gateway'),
            'action' => esc_attr($dotpay_url),
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
            'email' => esc_attr($email),
        ));
    }

    function check_dotpay_response() {
        $this->checkRemoteIP();
        $this->getPostParams();
        $order = $this->getOrder($this->fieldsResponse['control']);

        /**
         * check order amount, currency, email
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
        $ip = self::DOTPAY_IP;

        $realIp = isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '0.0.0.0';
        $remoteIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

        if (($realIp !== $ip) && ($remoteIp !== $ip)) {
            die('FAIL');
        }
    }

    protected function getOrder($idOrder) {
        $order = new WC_Order($idOrder);
        if (!$order) {
            die('FAIL');
        }

        return $order;
    }

    protected function checkSignature($order) {
        $hashDotpay = $this->fieldsResponse['signature'];
        $hashCalculate = $this->calculateSignature($order);

        if ($hashDotpay !== $hashCalculate) {
            die('FAIL');
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
                case 'operation_original_amount':
                    $origAmount = round($order->get_total(), 2);
                    $string .= sprintf("%01.2f", $origAmount);
                    break;
                case 'operation_original_currency':
                     $string .= $order->get_order_currency();
                    break;
                case 'email':
                     $string .= $order->billing_email;
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

}

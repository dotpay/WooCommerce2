<?php

class WC_Gateway_Dotpay extends WC_Gateway_Dotpay_Abstract {

    /**
     * initialise gateway with custom settings
     */
    public function __construct() {
        parent::__construct();
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
    
    protected function generate_dotpay_form($order_id) {
        $order = new WC_Order($order_id);
        
        $widget = $this->isDotWidget();
        $security = $this->isDotSecurity();

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
        if($widget) {
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
        if($security) {
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
            'iconMasterPass' => $this->getIconMasterPass(),
            'iconBLIK' => $this->getIconBLIK(),
            'iconDotpay' => $this->getIconDotpay(),
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

}

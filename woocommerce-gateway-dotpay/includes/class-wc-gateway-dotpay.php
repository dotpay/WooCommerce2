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
        
        /**
         * 
         */
        $agreementByLaw = $this->getDotpayAgreement($order, 'bylaw');
        $agreementPersonalData = $this->getDotpayAgreement($order, 'personal_data');
        
        /**
         * 
         */
        $agreements = array(
            'bylaw' => $agreementByLaw,
            'personal_data' => $agreementPersonalData,
        );
        
        /**
         * hidden fields MasterPass, BLIK, Dotpay
         */
        $hiddenFields = array(
            'mp' => array(
                'active' => $this->isDotMasterPass(),
                'fields' => $this->getHiddenFieldsMasterPass($order_id),
                'agreements' => $agreements,
                'icon' => $this->getIconMasterPass(),
            ),
            'blik' => array(
                'active' => $this->isDotBlik(),
                'fields' => $this->getHiddenFieldsBlik($order_id),
                'agreements' => $agreements,
                'icon' => $this->getIconBLIK(),
            ),
            'dotpay' => array(
                'active' => $this->isDotWidget(),
                'fields' => $this->getHiddenFieldsDotpay($order_id),
                'agreements' => $agreements,
                'icon' => $this->getIconDotpay(),
            ),
        );
        
        $security = $this->isDotSecurity();

        $dotpay_url = $this->getDotpayUrl();
        
        /**
         * url build signature
         */
        $signature_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_Dotpay_2', home_url('/')));
        
        /**
         * 
         */
        if($this->isDotWidget()) {
            /**
             * 
             */
            $tagP = __('You chose payment by Dotpay. Select a payment channel and click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to channel payment.', 'dotpay-payment-gateway'));
        } else {
            $tagP = __('You chose payment by Dotpay. Click Continue do proceed', 'dotpay-payment-gateway');
            $message = esc_js(__('Thank you for your order. We are now redirecting you to Dotpay to make payment.', 'dotpay-payment-gateway'));
        }
        
        /**
         * 
         */
//        if($security) {
//            $chk = $this->buildSignature4Request($hiddenFields);
//            
//            $_SESSION['hiddenFields'] = $hiddenFields;
//            
//            $hiddenFields['CHK'] = $chk;
//        }
        
        /**
         * js code
         */
        wc_enqueue_js(WC_Gateway_Dotpay_Include('/includes/block-ui.js.php', array(
            'mp' => $this->isDotMasterPass(),
            'blik' => $this->isDotBlik(),
            'widget' => $this->isDotWidget(),
            'message' => $message,
            'signature_url' => $signature_url,
        )));
        
        /**
         * html code
         */
        ob_start();
        WC_Gateway_Dotpay_Include('/includes/form-redirect.html.php', array(
            'mp' => $this->isDotMasterPass(),
            'blik' => $this->isDotBlik(),
            'widget' => $this->isDotWidget(),
            'h3' => __('Transaction Details', 'dotpay-payment-gateway'),
            'p' => $tagP,
            'submit' => __('Continue', 'dotpay-payment-gateway'),
            'action' => esc_attr($dotpay_url),
            'hiddenFields' => $hiddenFields,
        ));
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
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

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
*  @copyright PayPro S.A. (Dotpay)
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

/**
 * Transfer gateway channel
 */
class Gateway_Transfer extends Gateway_Gateway {

    /**
     * Create page
     */
    public static function install() {
        $page = new Dotpay_Page(DOTPAY_PAYINFO_PNAME);
        $page->setTitle(DOTPAY_PAYINFO_PTITLE)
             ->setGuid('/dotpay/payment/info')
             ->add();
    }

    /**
     * Remove page
     */
    public static function uninstall() {
        $page = new Dotpay_Page(DOTPAY_PAYINFO_PNAME);
        $page->remove();
    }

    /**
     * Return url to target API action
     * @param string $target target action for plugin API
     * @return string
     */
    protected function generateWcApiUrl($target) {
        $page = new Dotpay_Page(DOTPAY_PAYINFO_PNAME);
        return $page->getUrl();
    }

    /**
     * Return rendered information page (for transfer payments) or error meessage if an error occurred
     * @return string
     */
    public function getInformationPage() {

	    if(isset($_GET['order_id'])) {
		    $orderId = (int)$_GET['order_id'];
	    } else if($this->getOrder() != null && $this->getOrder()->get_id() != null) {
		    $orderId = $this->getOrder()->get_id();
	    } else {
            $cart_data = WC()->session;
            $items = WC()->cart->get_cart();
		    return __('Payment can not be created', 'dotpay-payment-gateway');
        }
        global $wpdb;
        $result = $wpdb->get_results('
            SELECT instruction_id as id
            FROM `'.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.'`
            WHERE order_id = '.(int)$orderId
            
        );
     
        $link_customer_panel = get_permalink( get_option('woocommerce_myaccount_page_id'));

        if(!is_array($result)  || count($result) <1) {
           // $instruction = null;
            $instruction = $this->processPayment();


            if($instruction == null || $instruction->getInstructionId() == null) 
            {


                return ("<div style=\"border: 1px solid #ec4d51; color: #9e191d;background-color: #ffe9e9;\"><h3 style=\"margin: 20px;color: #c11;\">❗️ ".__('Error occured: Payment can not be created', 'dotpay-payment-gateway').": #".$orderId."</h3><p style=\"text-align: center !important; margin: 24px 24px !important; padding: 10px;\">".__('Contact the seller and inform about the problem or place an order again and select a different payment method.', 'dotpay-payment-gateway')."<br><br><a href=\"".$link_customer_panel."\">".__('You can go to your account page', 'dotpay-payment-gateway')."</a></p></div><br>");
            }
            
        }else {
            $instruction = Dotpay_Instruction::getByOrderId($orderId);
        }

        if($instruction != null || $instruction->getInstructionId() != null) {
            $page = $instruction->getPage();
            

            return $page;
        } else {
            return ("<div style=\"border: 1px solid #ec4d51; color: #9e191d;background-color: #ffe9e9;\"><h3 style=\"margin: 20px;color: #c11;\">❌ ".__('Error occured: Payment not exist', 'dotpay-payment-gateway')."</h3><p style=\"text-align: center !important; margin: 24px 24px !important; padding: 10px;\">".__('Contact the seller and inform about the problem or place an order again and select a different payment method.', 'dotpay-payment-gateway')."<br><br><a href=\"".$link_customer_panel."\">".__('You can go to your account page', 'dotpay-payment-gateway')."</a></p></div><br>");
        }

	    $this->forgetChannel();
	    $this->forgetOrder();


    }

    /**
     * Create instruction or read it from database
     * @return \Dotpay_Instruction|null|false
     */
    private function processPayment() {
        if(isset($_GET['order_id'])) {
            $orderId = (int)$_GET['order_id'];
        } else if($this->getOrder() != null && $this->getOrder()->get_id() != null) {
            $orderId = $this->getOrder()->get_id();
        } else {
            return null;
        }
        Dotpay_RegisterOrder::init($this);
        $payment = Dotpay_RegisterOrder::create($this->getChannel());
        if($payment == null) {
            $instruction = Dotpay_Instruction::getByOrderId($orderId);
            if (!$instruction == null) {
                return null;
            }
        } else if($payment == false) {
            return false;
        } else {
            if($this->isChannelInGroup($payment['operation']['payment_method']['channel_id'], array(self::cashGroup))) {
                $isCash = true;
            } else {
                $isCash = false;
            }
            $instruction = new Dotpay_Instruction();
            $instruction->setAmount($payment['instruction']['amount']);
            $instruction->setCurrency($payment['instruction']['currency']);
            $instruction->setNumber($payment['operation']['number']);
            $instruction->setCash($isCash);
            $instruction->setHash(Dotpay_Instruction::gethashFromPayment($payment));
            $instruction->setOrderId($orderId);
            $instruction->setChannel($payment['operation']['payment_method']['channel_id']);
            if(isset($payment['instruction']['recipient'])) {
                $instruction->setBankAccount($payment['instruction']['recipient']['bank_account_number']);
                $instruction->setRecipientName($payment['instruction']['recipient']['name']);
                $instruction->setTitlep(substr($payment['instruction']['title'],0,100));
            }else{
            $instruction->setTitlep($payment['operation']['number']);
            }
            $instruction->save();
        }

        return $instruction;
    }

}

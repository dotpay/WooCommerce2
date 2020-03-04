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
		    return __('Payment can not be created', 'dotpay-payment-gateway');
        }

        global $wpdb;
        $result = $wpdb->get_results('
            SELECT instruction_id as id
            FROM `'.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.'`
            WHERE order_id = '.(int)$orderId
            
        );
     
        if(!is_array($result)  || count($result) <1) {
           // $instruction = null;
            $instruction = $this->processPayment();
            
            if($instruction ==null && $instruction->getInstructionId()== null) 
            {
                return __('Payment can not be created', 'dotpay-payment-gateway');
            }
            
        }else {
            $instruction = Dotpay_Instruction::getByOrderId($orderId);
        }

        if($instruction !=null && $instruction->getInstructionId()!= null) {
            $page = $instruction->getPage();
            

            return $page;
        } else {
            return __('Payment not exist', 'dotpay-payment-gateway');
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
                $instruction->setTitlep($payment['instruction']['title']);
            }else{
            $instruction->setTitlep($payment['operation']['number']);
            }
            $instruction->save();
        }
        return $instruction;
    }

}

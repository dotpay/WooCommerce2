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
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
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
     * Prepare gateway
     */
    public function __construct() {
        $this->title = 'Transfer and cash channels';
        parent::__construct();
        $this->id = 'Dotpay_tc';
        $this->addActions();
        $this->enabled = 'no';
        
    }
    
    /**
     * Create table for this model
     * @global type $wpdb WPDB object
     */
    public static function install() {
        $page = new Dotpay_Page(DOTPAY_PAYINFO_PNAME);
        $page->setTitle(DOTPAY_PAYINFO_PTITLE)
             ->setGuid('/dotpay/payment/info')
             ->add();
    }
    
    /**
     * Remove table
     * @global type $wpdb WPDB object
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
        $instruction = $this->processPayment();
        if($instruction!=null && $instruction->getInstructionId()!==NULL)
            return $instruction->getPage();
        $this->forgetChannel();
        $this->forgetOrder();
        return __('Payment not exist', 'dotpay-payment-gateway');
    }
    
    /**
     * Create instruction or read it from database
     * @return \Dotpay_Instruction
     */
    private function processPayment() {
        if(isset($_GET['order_id']))
            $orderId = (int)$_GET['order_id'];
        else if($this->getOrder()->id!=null)
            $orderId = $this->getOrder()->id;
        else
            return NULL;
        Dotpay_RegisterOrder::init($this);
        $payment = Dotpay_RegisterOrder::create($this->getChannel());
        if($payment === NULL) {
            $instruction = Dotpay_Instruction::getByOrderId($orderId);
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
            }
            $instruction->save();
        }
        return $instruction;
    }
}

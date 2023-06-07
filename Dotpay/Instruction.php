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
if (! class_exists('simple_html_dom_node'))
    require_once(dirname(__DIR__).'/vendor/simple_html_dom.php');

/**
 * Payment instruction model for Dotpay payment gateway for WooCommerce
 */
class Dotpay_Instruction extends Dotpay_Payment {
    const DOTPAY_NAME = 'PayPro S.A.';
    const DOTPAY_STREET = 'ul. Pastelowa 8';
    const DOTPAY_CITY = '60-198 Poznań';

    private $instructionId;
    private $orderId;
    private $number;
    private $titlep;
    private $hash;
    private $isCash;
    private $bankAccount;
    private $amount;
    private $currency;
    private $channel;
    private $RecipientName;

    /**
     * Return instruction id
     * @return int
     */
    public function getInstructionId() {
        return $this->instructionId;
    }

    /**
     * Return order id
     * @return int
     */
    public function getOrderId() {
        return $this->orderId;
    }

    /**
     * Return instruction number
     * @return string
     */
    public function getNumber() {
        return $this->number;
    }

    /**
     * Return instruction title
     * @return string
     */
    public function getTitlep() {
        return $this->titlep;
    }

    /**
     * Return instruction hash
     * @return string
     */
    public function getHash() {
        return $this->hash;
    }

    /**
     * Return flag, if instruction applies to cash method
     * @return bool
     */
    public function isCash() {
        return $this->isCash;
    }

    /**
     * Return bank account, if instruction applies to transfer method
     * @return string|null
     */
    public function getBankAccount() {
        return $this->bankAccount;
    }

   /**
     * Return bank account, if instruction applies to transfer method
     * @return string|null
     */
    public function getRecipientName() {
        return $this->RecipientName;
    }
    


    /**
     * Return amount
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * Return currency
     * @return string
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * Return payment channel id
     * @return int
     */
    public function getChannel() {
        return $this->channel;
    }

    /**
     * Set instruction id
     * @param int $instructionId instruction id
     * @return \Dotpay_Instruction
     */
    public function setInstructionId($instructionId) {
        $this->instructionId = $instructionId;
        return $this;
    }

    /**
     * Set order id
     * @param int $orderId order id
     * @return \Dotpay_Instruction
     */
    public function setOrderId($orderId) {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * Set instruction number
     * @param string $number instruction number
     * @return \Dotpay_Instruction
     */
    public function setNumber($number) {
        $this->number = $number;
        return $this;
    }

    /**
     * Set instruction title
     * @param string &titlep instruction title
     * @return \Dotpay_Instruction
     */
    public function setTitlep($titlep) {
        $this->titlep = $titlep;
        return $this;
    }

    /**
     * Set instruction hash
     * @param string $hash instruction hash
     * @return \Dotpay_Instruction
     */
    public function setHash($hash) {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Set true, if payment channel belongs to cash group
     * @param bool $cash cash flag
     * @return \Dotpay_Instruction
     */
    public function setCash($cash) {
        $this->isCash = $cash;
        return $this;
    }

    /**
     * Set bank account number
     * @param string $bankAccount bank account number
     * @return \Dotpay_Instruction
     */
    public function setBankAccount($bankAccount) {
        $this->bankAccount = $bankAccount;
        return $this;
    }

    /**
     * Set bank account recipient name
     * @param string $bankAccount bank account recipient name
     * @return \Dotpay_Instruction
     */
    public function setRecipientName($recipientName) {
        $this->RecipientName = $recipientName;
        return $this;
    }

    

    /**
     * Set amount
     * @param float $amount amount
     * @return \Dotpay_Instruction
     */
    public function setAmount($amount) {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Set currency
     * @param string $currency currency
     * @return \Dotpay_Instruction
     */
    public function setCurrency($currency) {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Set channel id
     * @param int $channel channel id
     * @return \Dotpay_Instruction
     */
    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    /**
     * Return recipient name
     * @return string
     */
    public function getRecipient() {
        return self::DOTPAY_NAME;
    }

    /**
     * Return street of recipient
     * @return string
     */
    public function getStreet() {
        return self::DOTPAY_STREET;
    }

    /**
     * Return city of recipient
     * @return string
     */
    public function getCity() {
        return self::DOTPAY_CITY;
    }

    /**
     * Return translated command for instruction button
     * @return string
     */
    public function getCommand() {
        if($this->isCash) {
            return __('Download blankiet', 'dotpay-payment-gateway');
        } else {
            return __('Make a money transfer', 'dotpay-payment-gateway');
        }
    }

    /**
     * Create table for this model
     * @global type $wpdb WPDB object
     */
    public static function install() {
        global $wpdb;
        $sql = 'CREATE TABLE IF NOT EXISTS `'.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.'` (
                    `instruction_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `order_id` INT UNSIGNED NOT NULL,
                    `number` varchar(64) NOT NULL,
                    `titlep` varchar(128) NOT NULL,
                    `hash` varchar(64) NOT NULL,
                    `is_cash` TINYINT NOT NULL,
                    `bank_account` VARCHAR(64),
                    `amount` decimal(10,2) NOT NULL,
                    `currency` varchar(3) NOT NULL,
                    `channel` INT UNSIGNED NOT NULL,
                    `name` varchar(128) NOT NULL,
                    PRIMARY KEY (`instruction_id`)
                ) DEFAULT CHARSET=utf8;';
        
       
     
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

            dbDelta( $sql );
            		
			/*@ Add name and titlep columns if not exist */
			$dbname = $wpdb->dbname;

			$marks_table_name = $wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME;

			 // for column 'titlep'	
			 $is_status_col1 = $wpdb->get_results(  "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `table_name` = '".$marks_table_name."' AND `TABLE_SCHEMA` = '".$dbname."' AND `COLUMN_NAME` = 'titlep'"  );

            if( empty($is_status_col1) )
            {
				$add_status_column1 = "ALTER TABLE `".$marks_table_name."` ADD `titlep` VARCHAR(128) NOT NULL AFTER `number`; ";
				$wpdb->query( $add_status_column1 );

            }

			
			// for column 'name' 
			$is_status_col2 = $wpdb->get_results(  "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `table_name` = '".$marks_table_name."' AND `TABLE_SCHEMA` = '".$dbname."' AND `COLUMN_NAME` = 'name'"  );

            if( empty($is_status_col2) )
            {
				$add_status_column2 = "ALTER TABLE `".$marks_table_name."` ADD `name` VARCHAR(128) NOT NULL AFTER `channel`; ";
				$wpdb->query( $add_status_column2 );
            }
			
			
    }


    /**
     * Remove table
     * @global type $wpdb WPDB object
     */
    public static function uninstall() {
        global $wpdb;
        $sql = 'DROP TABLE IF EXISTS `'.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.'`;';
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $wpdb->query( $sql );
    }

    /**
     * Return payment instruction by order id
     * @global type $wpdb WPDB object
     * @param int $orderId order id
     * @return \Dotpay_Instruction
     */
    public static function getByOrderId($orderId) {
        global $wpdb;
        $result = $wpdb->get_results('
            SELECT instruction_id as id
            FROM `'.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.'`
            WHERE order_id = '.(int)$orderId
        );

        // if (!is_array($result)) {
		if(!is_array($result)  || count($result) <1) {
            return null;
        }
            return new Dotpay_Instruction($result[count($result)-1]->id);          
  
    }

    /**
     * Return instruction hash from payment
     * @param array $payment payment
     * @return string
     */
    public static function gethashFromPayment($payment) {
        $parts = explode('/',$payment['instruction']['instruction_url']);
        return $parts[count($parts)-2];
    }

    /**
     * Return url to bank site
     * @return string
     */
    public function getBankPage() {
        $url = $this->buildInstructionUrl();
        $html = file_get_html($url);
        if($html==false) {
            return null;
        }
        return $html->getElementById('channel_container_')->firstChild()->getAttribute('href');
    }

    /**
     * Return url to pdf payment instruction
     * @return string
     */
    public function getPdfUrl() {
        return $this->getPaymentUrl().'instruction/pdf/'.$this->number.'/'.$this->hash.'/';
    }

    /**
     * Return url to the payment instruction on Dotpay server
     * @return string
     */
    protected function buildInstructionUrl() {
        return $this->getPaymentUrl().'instruction/'.$this->number.'/'.$this->hash.'/';
    }

    /**
     * Save changes in model to the database
     * @global type $wpdb WPDB object
     * @return boolean
     */
    public function save() {
        global $wpdb;
        $existedCard = $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.' WHERE instruction_id = '.(int)$this->instructionId);
        if(empty($existedCard)) {
            $wpdb->insert(
                $wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME,
                array(
                    'order_id' => $this->orderId,
                    'number' => substr($this->number,0,64),
                    'titlep' => substr($this->titlep,0,128),
                    'hash' => $this->hash,
                    'is_cash' => $this->isCash,
                    'bank_account' => $this->bankAccount,
                    'amount' => $this->amount,
                    'currency' => $this->currency,
                    'channel' => $this->channel,
                    'name' => substr($this->RecipientName,0,128)
                ),
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s'
                )
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME,
                array(
                    'order_id' => $this->orderId,
                    'number' => substr($this->number,0,64),
                    'titlep' => substr($this->titlep,0,128),
                    'hash' => $this->hash,
                    'is_cash' => $this->isCash,
                    'bank_account' => $this->bankAccount,
                    'amount' => $this->amount,
                    'currency' => $this->currency,
                    'channel' => $this->channel,
                    'name' => substr($this->RecipientName,0,128)
                ),
                array('instruction_id' => $this->instructionId),
                array(
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%s'
                ),
                array('%d')
            );
        }
        $instruction = $wpdb->get_row( 'SELECT * FROM '.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.' WHERE order_id = '.(int)$this->orderId.' AND hash = \''.$this->hash.'\' AND amount = \''.$this->amount.'\'');
        $this->instructionId = $instruction->instruction_id;
        return true;
    }

    /**
     * Prepare instruction object
     * @global type $wpdb WPDB object
     * @param int|null $id instruction id
     * @return type
     */
    public function __construct($id = null) {
        $this->id = 'dotpay';
        $this->has_fields = true;
        $this->init_settings();
        global $wpdb;
        if($id == null) {
            return;
        }
        $result = $wpdb->get_row('
            SELECT *
            FROM `'.$wpdb->prefix.DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME.'`
            WHERE instruction_id = '.(int)$id
        );
        if(empty($result)) {
            return;
        }
        $this->amount = $result->amount;
        $this->channel = $result->channel;
        $this->currency = $result->currency;
        $this->hash = $result->hash;
        $this->isCash = $result->is_cash;
        $this->number = $result->number;
        $this->titlep = $result->titlep;
        $this->orderId = $result->order_id;
        $this->bankAccount = $result->bank_account;
        $this->instructionId = $result->instruction_id;
        $this->RecipientName = $result->name;
    }
    /**
     * Return path to the image with channel logo
     * @return string
     */
    public function getChannelLogo() {
        $chData = $this->getChannelData($this->getChannel());
        return $chData['logo'];
    }

    /**
     * Return content of page with payment instruction
     * @return string
     */
    public function getPage() {
        return $this->render('payment_info.phtml');
    }

    /**
     * Return url to bank site or pdf payment instruction
     * @return string
     */
    public function getAddress() {
        if($this->isCash) {
            return $this->getPdfUrl();
        } else {
            return $this->getBankPage();
        }
    }

    /**
     * Return flag, if test mode is enabled
     * @return boolean
     */
    public function isTestMode()
    {
        $result = false;
        if ('yes' == $this->get_option('test')) {
            $result = true;
        }

        return $result;
    }


}

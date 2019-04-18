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
 * One Click gateway channel
 */
class Gateway_OneClick extends Gateway_Gateway {
    /**
     * Prepare gateway
     */
    public function __construct() {
        parent::__construct();
        $this->id = 'Dotpay_oc';
        $this->title = __('One Click via Dotpay', 'dotpay-payment-gateway');
        $this->method_description = __('All Dotpay settings can be adjusted', 'dotpay-payment-gateway').sprintf('<a href="%s"> ', admin_url( 'admin.php?page=wc-settings&tab=checkout&section=dotpay' ) ).__('here', 'dotpay-payment-gateway').'</a>.';
        $this->addActions();
        add_action('woocommerce_api_'.strtolower($this->id).'_rmcard', array($this, 'removeCard'));
    }

    /**
     * Return channel id
     * @return int
     */
    protected function getChannel() {
        return self::$ocChannel;
    }

    /**
     * Remove a specified card from database. Card id is sending by POST method
     */
    public function removeCard() {
        $card = Dotpay_Card::getCardById($_POST['cardId']);
        if($this->getCurrentUserId()==$card->customer_id) {
            Dotpay_Card::removeCard($_POST['cardId']);
            die('1');
        }
        die('0');
    }

    /**
     * Return url to icon file
     * @return string
     */
    protected function getIcon() {
        return WOOCOMMERCE_DOTPAY_GATEWAY_URL . 'resources/images/oneclick.png';
    }

    /**
     * Return data for payments form
     * @return array
     */
    protected function getDataForm() {
        $hiddenFields = parent::getDataForm();
        $hiddenFields['channel'] = $this->getChannel();
        $hiddenFields['ch_lock'] = 1;
        $hiddenFields['type'] = 4;

        if($_SESSION['dotpay_form_oc_type'] == 'choose') {
            $card = Dotpay_Card::getCardById($_SESSION['dotpay_form_saved_card']);
            $hiddenFields['credit_card_id'] = $card->card_id;
            $hash = $card->hash;
            unset($_SESSION['dotpay_form_saved_card']);
        } else {
            $hiddenFields['credit_card_store'] = '1';
            // $hash = Dotpay_Card::addCard($this->getCurrentUserId(), $this->getOrder()->id);
            $hash = Dotpay_Card::addCard($this->getCurrentUserId(), $this->getOrder()->get_id());
        }
        $hiddenFields['credit_card_customer_id'] = $hash;
        unset($_SESSION['dotpay_form_oc_type']);
        return $hiddenFields;
    }

    /**
     * Return list of credit cards for current customer
     * @return array
     */
    public function getCreditCards() {
        $cardModel = new Dotpay_Card();
        return $cardModel->getUsefulCardsForCustomer($this->getCurrentUserId());
    }

    /**
     * Return flag, if this channel is enabled
     * @return bool
     */
    protected function isEnabled() {
        return parent::isEnabled() && $this->isOneClickEnabled() && is_user_logged_in();
    }

    /**
     * Validate fields before creation of order
     * @return boolean
     */
    public function validate_fields() {
        if(!isset($_POST['oc_type'])) {
            wc_add_notice( __('Please select One Click option', 'dotpay-payment-gateway') , 'error' );
            return false;
        }
        if($this->getParam('oc_agreements')!= '1') {
            wc_add_notice( __('Please accept all agreements', 'dotpay-payment-gateway') , 'error' );
            return false;
        }

        $_SESSION['dotpay_form_oc_type'] = $_POST['oc_type'];
        if($_POST['oc_type'] == 'choose') {
            $_SESSION['dotpay_form_saved_card'] = $_POST['saved_card'];
        }
        return true;
    }

    /**
     * Return string with rendered oc manage page
     * @return string
     */
    public function getManagePage() {
        return $this->render('oc_manage.phtml');
    }

    /**
     * Perform order after confirmation
     * @param WC_Order $order order object
     * @return boolean
     */
	 protected function postConfirmOrder($order) {

    $cc = Dotpay_Card::getCardFromOrder($order->get_id());
        
    if($cc && is_object($cc) && $cc->cc_id)
			{
				if($cc->cc_id !== NULL && $cc->card_id == NULL) {
					$sellerApi = new Dotpay_SellerApi($this->getSellerApiUrl());
					$ccInfo = $sellerApi->getCreditCardInfo(
																$this->getApiUsername(),
																$this->getApiPassword(),
																$this->getParam('operation_number')
															);
					 if(isset($cc->cc_id) && isset($ccInfo->id) && isset($ccInfo->masked_number) && isset($ccInfo->brand->name) && isset($ccInfo->brand->logo) )
						 {
							Dotpay_Card::updateCard($cc->cc_id, $ccInfo->id, $ccInfo->masked_number, $ccInfo->brand->name, $ccInfo->brand->logo);
						 }
					}
			}

        return true;
    }

}

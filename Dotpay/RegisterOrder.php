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
 * Toolkit for processing payments by register order method
 */
abstract class Dotpay_RegisterOrder {
    /**
     *
     * @var DotpayController Controller object
     */
    public static $payment;
    
    /**
     *
     * @var string Target url for Register Order
     */
    private static $target = "payment_api/v1/register_order/";
    
    /**
     * Initialize Register Order mechanism
     * @param DotpayController $parent Owner of the object API.
     */
    public static function init(Dotpay_Payment $payment = NULL) {
        self::$payment = $payment;
    }
    
    /**
     * Create register order, if it not exist
     * @param type $channelId Channel identifier
     * @return null|array
     */
    public static function create($channelId) {
        $data = str_replace('\\/', '/', json_encode(self::prepareData($channelId), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if(!self::checkIfCompletedControlExist(self::$payment->getControl(), $channelId)) {
            return self::createRequest($data);
        }
        return NULL;
    }
    
    /**
     * Create request without checking conditions
     * @param array $data
     * @return boolean
     */
    private static function createRequest($data) {
        try {
            $curl = new Dotpay_Curl();
            $curl->addOption(CURLOPT_URL, self::$payment->getPaymentUrl().self::$target)
                 ->addOption(CURLOPT_SSL_VERIFYPEER, TRUE)
                 ->addOption(CURLOPT_SSL_VERIFYHOST, 2)
                 ->addOption(CURLOPT_RETURNTRANSFER, 1)
                 ->addOption(CURLOPT_TIMEOUT, 100)
                 ->addOption(CURLOPT_USERPWD, self::$payment->getApiUsername().':'.self::$payment->getApiPassword())
                 ->addOption(CURLOPT_POST, 1)
                 ->addOption(CURLOPT_POSTFIELDS, $data)
                 ->addOption(CURLOPT_HTTPHEADER, array(
                    'Accept: application/json; indent=4',
                    'content-type: application/json'));
            $resultJson = $curl->exec();
            $resultStatus = $curl->getInfo();
        } catch (Exception $exc) {
            $resultJson = false;
        }
        
        if($curl) {
            $curl->close();
        }
        
        if(false !== $resultJson && $resultStatus['http_code'] == 201) {
            return json_decode($resultJson, true);
        }
        
        return false;
    }
    
    /**
     * Check, if order id from control field is completed
     * @param int $control Order id from control field
     * @return boolean
     */
    private static function checkIfCompletedControlExist($control, $channel) {
        $api = new Dotpay_SellerApi(self::$payment->getSellerApiUrl());
        $payments = $api->getPaymentByOrderId(self::$payment->getApiUsername(), self::$payment->getApiPassword(), $control);
        foreach($payments as $payment) {
            $onePayment = $api->getPaymentByNumber(self::$payment->getApiUsername(), self::$payment->getApiPassword(), $payment->number);
            if($onePayment->control == $control && $onePayment->payment_method->channel_id == $channel && $payment->status == 'completed')
                return true;
        }
        return false;
    }

    /**
     * Prepares the data for query.
     * @param int $channelId
     * @return array
     */
    private static function prepareData($channelId) {
        $streetData = self::$payment->getStreetAndStreetN1();
        return array (
            'order' => array (
                'amount' => self::$payment->getOrderAmount(),
                'currency' => self::$payment->getCurrency(),
                'description' => self::$payment->getDescription(),
                'control' => self::$payment->getControl('full')

            ),

            'seller' => array (
                'account_id' => self::$payment->getSellerId(),
                'url' => self::$payment->getUrl(),
                'urlc' => self::$payment->getUrlc(),
                'p_info' => self::$payment->getPinfo()
            ),

            'payer' => array (
                'first_name' => self::$payment->getFirstname(),
                'last_name' => self::$payment->getLastname(),
                'email' => self::$payment->getEmail(),
                'address' => array(
                    'street' => $streetData['street'],
                    'building_number' => $streetData['street_n1'],
                    'postcode' => self::$payment->getPostcode(),
                    'city' => self::$payment->getCity(),
                    'country' => self::$payment->getCountry()
                )
            ),

            'payment_method' => array (
                'channel_id' => $channelId
            ),

            'request_context' => array (
                'ip' => self::$payment->getClientIp()
            )

        );
    }
}

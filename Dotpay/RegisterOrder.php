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
    public static function init(Dotpay_Payment $payment = null) {
        self::$payment = $payment;
    }

    /**
     * Create register order, if it not exist
     * @param type $channelId Channel identifier
     * @return null|array
     */
    public static function create($channelId) {
        $data = str_replace('\\/', '/', json_encode(self::prepareData($channelId), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if(!self::checkIfCompletedControlExist(self::$payment->getControl('full'), $channelId)) {
            return self::createRequest($data);
        }
        return null;
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
                    'Content-type: application/json; charset=utf-8',
                    'User-Agent: DotpayWooCommerce v:'. self::$payment->getModuleVersion().' (id:'.self::$payment->getSellerId().')'
                ));
            $resultJson = $curl->exec();
            $resultStatus = $curl->getInfo();
        } catch (Exception $exc) {
            $resultJson = false;
        }

        if($curl) {
            $curl->close();
        }
        if(false != $resultJson && $resultStatus['http_code'] == 201) {
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
        $Request = array (
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
                'email' => self::$payment->getEmail()
            ),

            'payment_method' => array (
                'channel_id' => $channelId
            ),

            'request_context' => array (
                'ip' => self::$payment->getClientIp()
            )

        );

        if ( null != $streetData['street'] && null != self::$payment->getPostcode() && null != self::$payment->getCity() && null != self::$payment->getCountry() ) 
        {
            $Request["payer"]["address"]["street"] = $streetData['street'];
            $Request["payer"]["address"]["building_number"] = $streetData['street_n1'];
            $Request["payer"]["address"]["postcode"] = self::$payment->getPostcode();
            $Request["payer"]["address"]["city"] = self::$payment->getCity();
            $Request["payer"]["address"]["country"] = self::$payment->getCountry();
        }


                // for PSD2
                if (isset($_SERVER['HTTP_ACCEPT'])) {
                    $Request["request_context"]["accept"] = $_SERVER['HTTP_ACCEPT'];
                }
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $Request["request_context"]["useragent"] = $_SERVER['HTTP_USER_AGENT']." DotpayWooCommerce/".self::$payment->getModuleVersion();
                }
                if (isset($_SERVER['HTTP_REFERER'])) {
                    $Request["request_context"]["referer"] = $_SERVER['HTTP_REFERER'];
                }
        
                if(isset( $_COOKIE['dp_browser_javaenabled'] )) {
                    $Request["request_context"]["browser"]["javaenabled"] = $_COOKIE['dp_browser_javaenabled'];
                } 
        
                if(isset( $_COOKIE['dp_browser_javascriptenabled'] ) && isset( $_COOKIE['dp_browser_language'] ) && isset( $_COOKIE['dp_browser_screencolordepth'] ) && isset( $_COOKIE['dp_browser_screenheight'] ) && isset( $_COOKIE['dp_browser_screenwidth'] ) && isset( $_COOKIE['dp_browser_timezone'] )) 
                {
                        
                        $Request["request_context"]["browser"]["javascriptenabled"] = $_COOKIE['dp_browser_javascriptenabled'];
                        $Request["request_context"]["browser"]["language"] = $_COOKIE['dp_browser_language'];
                        $Request["request_context"]["browser"]["screencolordepth"] = $_COOKIE['dp_browser_screencolordepth'];
                        $Request["request_context"]["browser"]["screenheight"] = $_COOKIE['dp_browser_screenheight'];        
                        $Request["request_context"]["browser"]["screenwidth"] = $_COOKIE['dp_browser_screenwidth'];
                        $Request["request_context"]["browser"]["timezone"] = $_COOKIE['dp_browser_timezone'];
        
                } 
        
        
        return $Request;


    }
}

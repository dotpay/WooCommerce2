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
 * Tool for support Curl
 */
class Dotpay_Curl {
    /**
     *
     * @var resource cURL resource
     */
    private $_resource;
    
    /**
     * 
     * @var mixed Information about last request
     */
    private $_info;
    
    /**
     * 
     * @var bool Information about whether cURL resource is active
     */
    private $_isActive;
    
    /**
     * Initialize a cURL session
     * @return \Curl
     */
    public function __construct() {
        $this->_resource = curl_init();
        $this->_isActive = true;
    }
    
    /**
     * Clear a cURL resource
     */
    public function __destruct() {
        if($this->_isActive)
            $this->close();
    }
    
    /**
     * Set an CA file for a cURL transfer
     * @param string $file
     * @return \Curl
     */
    public function addCaInfo($file) {
        $this->addOption(CURLOPT_CAINFO, $file);
        
        return $this;
    }
    
    /**
     * Set an option for a cURL transfer
     * @param mixed $option
     * @param mixed $value
     * @return \Curl
     */
    public function addOption($option, $value) {
        curl_setopt($this->_resource, $option, $value);
        return $this;
    }
    
    /**
     * Perform a cURL session
     * @return mixed
     */
    public function exec() {

        $response = curl_exec($this->_resource);
        $this->_info = curl_getinfo($this->_resource);
        return $response;
    }
    
    /**
     * Get information regarding a specific transfer
     * @return mixed
     */
    public function getInfo() {
        return $this->_info;
    }
    
    /**
     * Close a cURL session
     */
    public function close() {
        curl_close($this->_resource);
        $this->_isActive = false;
    }
}
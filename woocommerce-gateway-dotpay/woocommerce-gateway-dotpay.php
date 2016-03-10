<?php

/*
  Plugin Name: WooCommerce Gateway Dotpay
  Plugin URI: https://github.com/dotpay/WooCommerce2
  Description: Add a credit card payment gateway for Dotpay (Poland) to WooCommerce
  Version: 2.5
  Author: Dotpay (tech@dotpay.pl)
  Author URI: mailto:tech@dotpay.pl
  Text Domain: dotpay-payment-gateway
  Last modified: 2016-02-25 by tech@dotpay.pl in 'class-wc-gateway-dotpay.php'
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * payment gateway integration for WooCommerce
 * @ref http://www.woothemes.com/woocommerce/
 */
function init_woocommerce_gateway_dotpay() {
    define('WOOCOMMERCE_DOTPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('WOOCOMMERCE_DOTPAY_PLUGIN_URL', plugin_dir_url(__FILE__));

    WC_Gateway_Dotpay_Include('/includes/class-wc-gateway-dotpay.php');
}

function init_woocommerce_gateway_dotpay_session_start() {
    if(!session_id()) {
        session_start();
    }
}

function init_woocommerce_gateway_dotpay_session_end() {
    session_destroy();
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    load_plugin_textdomain('dotpay-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/langs/');

    add_action('init', 'init_woocommerce_gateway_dotpay');
    add_action('init', 'init_woocommerce_gateway_dotpay_session_start');
    add_action('wp_logout', 'init_woocommerce_gateway_dotpay_session_end');
    add_action('wp_login', 'init_woocommerce_gateway_dotpay_session_end');

    function add_dotpay_class($methods) {
        $methods[] = 'WC_Gateway_Dotpay';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_dotpay_class');
}

function WC_Gateway_Dotpay_Include($file, array $data = array()) {
    return include( WOOCOMMERCE_DOTPAY_PLUGIN_DIR . $file);
}

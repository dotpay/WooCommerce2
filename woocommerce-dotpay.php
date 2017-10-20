<?php

/*
  Plugin Name: WooCommerce Dotpay Gateway
  Plugin URI: https://github.com/dotpay/WooCommerce2
  Description: Fast and secure payment gateway for Dotpay (Poland) to WooCommerce
  Version: 3.0.12
  Author: Dotpay (tech@dotpay.pl)
  Author URI: mailto:tech@dotpay.pl
  Text Domain: dotpay-payment-gateway
  Last modified: 2017-10-09 by tech@dotpay.pl
 */

if (!defined('ABSPATH')) {
    exit;
}

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);

function is_session_started() {
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

if(!is_session_started()) {
    session_start();
}

function woocommerce_dotpay_autoload($className){
    $filename = plugin_dir_path(__FILE__).str_replace('_', '/', $className).'.php';
    if(file_exists($filename)) {
        include_once($filename);
    }
}

spl_autoload_register('woocommerce_dotpay_autoload');

function init_woocommerce_dotpay() {
    
}

function init_woocommerce_dotpay_session_start() {
    if(!session_id()) {
        session_start();
    }
}

function init_woocommerce_dotpay_session_end() {
    if (is_session_started() !== FALSE) {
        session_destroy();
    }
}

function dotpay_admin_enqueue_scripts($hook) {
    if($hook != 'woocommerce_page_wc-settings') {
        return;
    }
    wp_enqueue_script( 'admin-script', plugin_dir_url( __FILE__ ) . 'resources/js/admin.js' );
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    load_plugin_textdomain('dotpay-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/langs/');

    add_action('init', 'init_woocommerce_dotpay');
    add_action('init', 'init_woocommerce_dotpay_session_start');
    add_action('wp_logout', 'init_woocommerce_dotpay_session_end');
    add_action('wp_login', 'init_woocommerce_dotpay_session_end');

    function add_dotpay_payment_class($methods) {
        return array_merge($methods, Dotpay_Payment::getDotpayChannelsList());
    }

    add_filter('woocommerce_payment_gateways', 'add_dotpay_payment_class');
    
    add_action( 'admin_enqueue_scripts', 'dotpay_admin_enqueue_scripts' );
}

define('DOTPAY_CARD_MANAGE_PTITLE', __("My saved credit cards", 'dotpay-payment-gateway'));
define('DOTPAY_CARD_MANAGE_PNAME', "oc_manage_cards");

define('DOTPAY_STATUS_PTITLE', __("Checking payment status...", 'dotpay-payment-gateway'));
define('DOTPAY_STATUS_PNAME', "dotpay_order_status");

define('DOTPAY_PAYINFO_PTITLE', __("Details of your payment", 'dotpay-payment-gateway'));
define('DOTPAY_PAYINFO_PNAME', "dotpay_payment_info");

define('DOTPAY_GATEWAY_ONECLICK_TAB_NAME', 'dotpay_oneclick_cards');
define('DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME', 'dotpay_instructions');

define('WOOCOMMERCE_DOTPAY_GATEWAY_DIR', plugin_dir_path(__FILE__));
define('WOOCOMMERCE_DOTPAY_GATEWAY_URL', plugin_dir_url(__FILE__));

function wc_dotpay_gateway_activate(){
    $plugin_dir = basename(dirname(__FILE__)).'/langs';
    load_plugin_textdomain( 'dotpay-payment-gateway', false, $plugin_dir );
    Dotpay_Card::install();
    $page = new Dotpay_Page(DOTPAY_STATUS_PNAME);
    $page->setTitle(DOTPAY_STATUS_PTITLE)
         ->setGuid('/dotpay/order/status')
         ->add();
    Gateway_Transfer::install();
    Dotpay_Instruction::install();
}

function wc_dotpay_gateway_uninstall(){
    Dotpay_Card::uninstall();
    $page = new Dotpay_Page(DOTPAY_STATUS_PNAME);
    $page->remove();
    Gateway_Transfer::uninstall();
    Dotpay_Instruction::uninstall();
}

register_activation_hook( __FILE__, 'wc_dotpay_gateway_activate' );
register_deactivation_hook( __FILE__, 'wc_dotpay_gateway_uninstall' );

function wc_dotpay_gateway_hide_pages( $pages) {
    foreach ($pages as $index => $page) {
        if(!is_user_logged_in() && wc_dotpay_compare_page($page, Dotpay_Page::getPageId(DOTPAY_CARD_MANAGE_PNAME))) {
            unset($pages[$index]);
        } else if(wc_dotpay_compare_page($page, Dotpay_Page::getPageId(DOTPAY_STATUS_PNAME))) {
            unset($pages[$index]);
        } else if(wc_dotpay_compare_page($page, Dotpay_Page::getPageId(DOTPAY_PAYINFO_PNAME))) {
            unset($pages[$index]);
        }
    }
    return $pages;
}

function wc_dotpay_compare_page($page, $id) {
    if($page->ID == $id || $page->object_id == $id) {
        return true;
    } else {
        return false;
    }
}
add_filter('get_pages','wc_dotpay_gateway_hide_pages');
add_filter('wp_get_nav_menu_items','wc_dotpay_gateway_hide_pages');

add_filter('the_content','wc_dotpay_gateway_content');

function wc_dotpay_gateway_content($content) {
    global $wp_query;
    switch($wp_query->post->ID) {
        case Dotpay_Page::getPageId(DOTPAY_CARD_MANAGE_PNAME):
            $oc = new Gateway_OneClick();
            return $oc->getManagePage();
        case Dotpay_Page::getPageId(DOTPAY_STATUS_PNAME):
            $oc = new Gateway_Dotpay();
            return $oc->getStatusPage();
        case Dotpay_Page::getPageId(DOTPAY_PAYINFO_PNAME):
            $oc = new Gateway_Transfer();
            return $oc->getInformationPage();
        default:
            return $content;
    }
}

/**
 * Fix for PHP older than 7.0
 * @param string $dir
 * @param int $levels
 * @return string
 */
function mydirname($dir, $levels) {
    while(--$levels) {
        $dir = dirname($dir);
    }
    return $dir;
}

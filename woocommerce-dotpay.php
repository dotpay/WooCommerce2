<?php

/**
  * @wordpress-plugin
  * Plugin Name: WooCommerce Dotpay Gateway
  * Version: 3.6.4
  * Plugin URI: https://github.com/dotpay/WooCommerce2
  * Description: Fast and secure Dotpay payment gateway for WooCommerce
  * Author: Dotpay (tech@dotpay.pl)
  * Author URI: https://github.com/dotpay/WooCommerce2
  * Text Domain: woocommerce-dotpay
  * Domain Path: /langs/
  * License:     (AFL 3.0)
  *
  * WC requires at least: 3.2.0
  * WC tested up to: 3.6.1 -> 6.2.2
  *
  * NOTICE OF LICENSE
  *
  * This source file is subject to the Academic Free License (AFL 3.0)
  * that is bundled with this package in the file LICENSE.txt.
  * It is also available through the world-wide-web at this URL:
  * http://opensource.org/licenses/afl-3.0.php
  *
  * This program is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  *
 */


if (!defined('ABSPATH')) {
    exit;
}

	/*
	 * requirements, PHP min. 5.6, recommended: >7.0
	 * Woocommerce ver >= 3.2.0
    */

		$minPHP = '5.6';
		$minWC = '3.2';
		$operator = '>=';
		$thisVersionModule = '3.6.4';

	// PHP compare
        if (!version_compare(PHP_VERSION, $minPHP, $operator) ) {
            add_action( "admin_notices", "noticePHP" );
        }


    function noticePHP()
    {
    		global $minPHP, $operator;
            print(
                '<div class="error notice is-dismissible"><p>'.__('Warning! WooCommerce Dotpay Gateway requires PHP', 'dotpay-payment-gateway').' '.$operator.' '.$minPHP.' '.__('Currently in use','dotpay-payment-gateway').' '.PHP_VERSION.'</p></div>'
    			);
      }

// Woocommerece version compare
	function Check_WC_compare_for_Dotpay()
  {
		global $minWC,$operator;
		if ( ! class_exists( 'WooCommerce' ) ) return;
				if (!version_compare( WC_VERSION, $minWC, $operator ) )
				{
					print(
						'<div class="error notice is-dismissible"><p>'.__('Attention! WooCommerce Dotpay Gateway to function properly requires Woocommerce', 'dotpay-payment-gateway').' '.$operator.' '.$minWC.' '.__('Currently in use','dotpay-payment-gateway').' '.WOOCOMMERCE_VERSION.'</p></div>'
						);

				}
	}

add_action( 'admin_notices' , 'Check_WC_compare_for_Dotpay');



// Register new status for double payment for the order
function register_dp_custom_order_statuses() {

    register_post_status('wc-dp_double', array(
        'label' => __( 'Duplicate Payment - check!', 'dotpay-payment-gateway' ),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Duplicate Payment - check! <span class="count">(%s)</span>', 'Duplicate Payment - check! <span class="count">(%s)</span>')
    ));
}
add_action('init', 'register_dp_custom_order_statuses');


// Add to list of WC Order statuses
function add_dp_custom_order_statuses($order_statuses) {
    $new_order_statuses = array();

    // add new order status after processing
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-dp_double'] = __( 'Duplicate Payment - check!', 'dotpay-payment-gateway' );
        }
    }
    return $new_order_statuses;
}
add_filter('wc_order_statuses', 'add_dp_custom_order_statuses');


// Admin reports for custom order status
function wc_reports_get_order_custom_report_data_args( $args ) {
    $args['order_status'] = array( 'completed', 'processing', 'on-hold', 'dp_double' );
    return $args;
};


add_filter( 'woocommerce_reports_get_order_report_data_args', 'wc_reports_get_order_custom_report_data_args');


// Changing the status color on the order list:
add_action('admin_head', 'styling_dp_admin_order_list' );

function styling_dp_admin_order_list() {
    global $pagenow, $post;

    if( $pagenow != 'edit.php') { return; } // Exit
    if ( ! is_object($post) || ! is_a($post, 'WP_Post') ) { return; } // Exit 
    if (empty($post->ID)) { return; } //Exit
    if( get_post_type($post->ID) != 'shop_order' )  { return; } // Exit 

    $order_status = 'dp_double'; // 
    ?>
    <style>
        .order-status.status-<?php echo sanitize_title( $order_status ); ?> {
            background: #ff89e5;
            color: #5f0a37;
        }
    </style>
    <?php
}



// Add settings link on plugin page
function wc_dotpay_settings_link($links) {
    $settings_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=dotpay">'.__('Settings').'</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  $plugin = plugin_basename(__FILE__);
  add_filter("plugin_action_links_$plugin", 'wc_dotpay_settings_link' );


/**
 * check latest version this module from github
 */

 function getLatestVersionDotpayModule() {
		global $thisVersionModule;
 		$ch = curl_init();
 		curl_setopt_array($ch, array(
 			CURLOPT_URL => 'https://api.github.com/repos/dotpay/WooCommerce2/releases/latest',
 		    CURLOPT_USERAGENT => 'WoocommerceDotpayModule/'.$thisVersionModule,
 			CURLOPT_RETURNTRANSFER => 1,
 			CURLOPT_HTTPHEADER => array('Accept: application/vnd.github.v3+json','User-Agent: DotpayPluginForWoocommerce'),
 			CURLOPT_TIMEOUT => 1000,
 			CURLOPT_SSL_VERIFYHOST => 2,
 			CURLOPT_SSL_VERIFYPEER => true,
 			CURLOPT_CUSTOMREQUEST => 'GET'
 		));

 		$response = json_decode(curl_exec($ch));
		$response_code = curl_getinfo($ch);
 				$version = null;
 				$url = '';
 				if (isset($response->tag_name)) {
 					$version = str_replace('v', '', $response->tag_name);
 					if (isset($response->html_url)) {
 						$url = $response->assets[0]->browser_download_url;
 					}
 				}
 				return array(
 					'version' => $version,
 					'url' => $url,
 					'code_response' => $response_code['http_code']
 				);
 }


$git_latest_version_plugin = getLatestVersionDotpayModule();


/*
 *  Dotpay Module version compare
 */
       if (!version_compare($thisVersionModule, $git_latest_version_plugin['version'], $operator) ) {
           add_action( "admin_notices", "noticeDotModule" );
       }

/*
 *  notice to upgrade Dotpay module
 */
       function noticeDotModule()
       {
               global $thisVersionModule, $git_latest_version_plugin;
               

                    $git_code_response = $git_latest_version_plugin['code_response'];
                    $dp_module_new_version_git = $git_latest_version_plugin['version'];
                    $dp_module_git_url = $git_latest_version_plugin['url'];


                    if($git_code_response != 200){
                        print '<br><br><div class="notice is-dismissible notice-warning notice-alt"><p>'.__('Attention! There is a temporary problem with checking information about latest version of the Dotpay payment module','dotpay-payment-gateway').' (error: '.$git_code_response.').
                        <br>'.__('Currently in use','dotpay-payment-gateway').' <b>WooCommerce Dotpay Gateway v'.$thisVersionModule.'</b>
                        <br>'.__('You can check manually and download latest version and upgrade from this address:','dotpay-payment-gateway').' <a href="https://github.com/dotpay/WooCommerce2/releases/latest" title="'.__('check if there is a new version of Dotpay payment plugin','dotpay-payment-gateway').'" target="_blank">'.__('WooCommerce Dotpay payment module','dotpay-payment-gateway').'</a></p></div>';
                    }else{
                        print '<br><br><div class="update-message notice inline notice-warning notice-alt"><p>
                        '.__('Attention! A new version of the Dotpay payment module is available:','dotpay-payment-gateway').' '.$dp_module_new_version_git.'. '.__('Currently in use','dotpay-payment-gateway').' <b>WooCommerce Dotpay Gateway v'.$thisVersionModule.'</b>
                        <br><b>'.__('Download latest version and upgrade manually this:','dotpay-payment-gateway').' <a href="'.$dp_module_git_url.'" class="update-link" aria-label="'.__('Upgrade WooCommerce Dotpay Gateway','dotpay-payment-gateway').'">'.$dp_module_new_version_git.'</b></a></p></div>';
                    }
	   }

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__);


function woocommerce_dotpay_autoload($className){
    $filename = plugin_dir_path(__FILE__).str_replace('_', '/', $className).'.php';
    if(file_exists($filename)) {
        include_once($filename);
    }
}

spl_autoload_register('woocommerce_dotpay_autoload');


function is_session_started1() {
    if ( php_sapi_name() != 'cli' ) {
        if ( version_compare(phpversion(), '5.6', '>=') ) {
            return session_status() == PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() == '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}


function dotpay_admin_enqueue_scripts($hook) {
    if($hook != 'woocommerce_page_wc-settings') {
        return;
    }
    wp_enqueue_script( 'admin-script', plugin_dir_url( __FILE__ ) . 'resources/js/admin.js' );
}

function woocommerce_is_active() {
	if (!function_exists( 'is_plugin_active_for_network'))
		require_once(ABSPATH . '/wp-admin/includes/plugin.php');
	// Check if WooCommerce is active
	if (!in_array( 'woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		return (is_multisite() && is_plugin_active_for_network('woocommerce/woocommerce.php'));
  }
return true;
}


if (woocommerce_is_active() != FALSE) {
    load_plugin_textdomain('dotpay-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/langs/');


    function add_dotpay_payment_class($methods) {
        return array_merge($methods, Dotpay_Payment::getDotpayChannelsList());
    }

    add_filter('woocommerce_payment_gateways', 'add_dotpay_payment_class');

    add_action( 'admin_enqueue_scripts', 'dotpay_admin_enqueue_scripts' );
}

if( !defined( 'DOTPAY_CARD_MANAGE_PTITLE' ) ) { define('DOTPAY_CARD_MANAGE_PTITLE', __("My saved credit cards", 'dotpay-payment-gateway')); }
if( !defined( 'DOTPAY_CARD_MANAGE_PNAME' ) ) { define('DOTPAY_CARD_MANAGE_PNAME', "oc_manage_cards"); }
if( !defined( 'DOTPAY_STATUS_PTITLE' ) ) { define('DOTPAY_STATUS_PTITLE', __("Checking payment status...", 'dotpay-payment-gateway')); }
if( !defined( 'DOTPAY_STATUS_PNAME' ) ) { define('DOTPAY_STATUS_PNAME', "dotpay_order_status"); }
if( !defined( 'DOTPAY_PAYINFO_PTITLE' ) ) { define('DOTPAY_PAYINFO_PTITLE', __("Details of your payment", 'dotpay-payment-gateway')); }
if( !defined( 'DOTPAY_PAYINFO_PNAME' ) ) { define('DOTPAY_PAYINFO_PNAME', "dotpay_payment_info"); }
if( !defined( 'DOTPAY_GATEWAY_ONECLICK_TAB_NAME' ) ) { define('DOTPAY_GATEWAY_ONECLICK_TAB_NAME', 'dotpay_oneclick_cards'); }
if( !defined( 'DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME' ) ) { define('DOTPAY_GATEWAY_INSTRUCTIONS_TAB_NAME', 'dotpay_instructions'); }
if( !defined( 'WOOCOMMERCE_DOTPAY_GATEWAY_DIR' ) ) { define('WOOCOMMERCE_DOTPAY_GATEWAY_DIR', plugin_dir_path(__FILE__)); }
if( !defined( 'WOOCOMMERCE_DOTPAY_GATEWAY_URL' ) ) { define('WOOCOMMERCE_DOTPAY_GATEWAY_URL', plugin_dir_url(__FILE__)); }


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

	if (!empty($wp_query->post->ID)) {
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
	return $content;
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

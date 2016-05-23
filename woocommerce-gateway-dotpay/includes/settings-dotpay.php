<?php

if (!defined('ABSPATH')) {
    exit;
}

$nameArrayMasterPass = array(
    __('Show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
    __('Payment with a credit card by MasterPass', 'dotpay-payment-gateway'),
);
$nameArrayccPV = array(
    __('I have a separate account in Dotpay: show separately payment channel in a shop. ', 'dotpay-payment-gateway'),
    __('Credit Card for currencies (PLN, EUR, USD or GBP)', 'dotpay-payment-gateway'),
);
$nameArrayBLIK = array(
    __('Show separately payment channel in a shop', 'dotpay-payment-gateway'),
    __('<strong>(only PLN)</strong>', 'dotpay-payment-gateway'),
    ' - BLIK (Polski Standard Płatności Sp. z o.o.)',
);

/**
 * Settings for Dotpay Gateway.
 */
return array(
	    'opis' => array(
        'title' => '<img src="'.WOOCOMMERCE_DOTPAY_PLUGIN_URL . 'resources/images/dotpay.png" alt="Dotpay SA">',
        'type' => 'checkbox',
        'default' => '',
		'label' => '<u><strong>'.__('C O N F I G U R A T I O N  :', 'dotpay-payment-gateway').'</strong></u>',
		'css' => 'display: none'
    ),

    'enabled' => array(
        'title' => __('Enable/Disable', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => __('Enable Dotpay Payment', 'dotpay-payment-gateway'),
        'default' => 'yes'
    ),
    'dotpay_id' => array(
        'title' => __('Dotpay customer ID', 'dotpay-payment-gateway'),
        'type' => 'text',
        'default' => '',
    ),

    'dotpay_pin' => array(
        'title' => __('Dotpay customer PIN', 'dotpay-payment-gateway'),
        'type' => 'text',
        'default' => '',
    ),

    'dotpay_ccPV_show' => array(
        'title' => __('<span style="color: #0073AA;">Separate Dotpay account for currencies (EUR, USD or GBP)</span>', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => implode(' ', $nameArrayccPV),
        'default' => 'no',
        'class' => 'pv_switch',

    ),
    
    'dotpay_id2' => array(
        'title' => __('<span style="color: #0073AA;">Dotpay customer ID2 (for second account)</span>', 'dotpay-payment-gateway'),
        'type' => 'text',
        'default' => '',
        'class' => 'pv_option'
    ),
    
    'dotpay_pin2' => array(
        'title' => __('<span style="color: #0073AA;">Dotpay customer PIN2 (for second account)</span>', 'dotpay-payment-gateway'),
        'type' => 'text',
        'default' => '',
        'class' => 'pv_option'
    ),
    
    'dotpay_ccPV_currency' => array(
        'title' => __('<span style="color: #0073AA;">Channel visible only for currencies</span>', 'dotpay-payment-gateway'),
        'type' => 'text',
        'default' => 'EUR,USD,GBP',
        'class' => 'pv_option',
		'description' => __('Leave it blank or enter a currency separated by commas eg. (EUR, GBP).', 'dotpay-payment-gateway'),
        'desc_tip' => true,
    ),
	
	
    'dotpay_dontview_currency' => array(
        'title' => __('Currencies for disable main method', 'dotpay-payment-gateway'),
        'type' => 'text',
        'default' => 'EUR,USD,GBP',
		'description' => __('Leave it blank or enter a currency separated by commas eg. (EUR, GBP).', 'dotpay-payment-gateway'),
        'desc_tip' => true,
    ),
    
    'dotpay_masterpass_show' => array(
        'title' => __('MasterPass', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => implode(' ', $nameArrayMasterPass),
        'default' => 'no',
    ),

    'dotpay_blik_show' => array(
        'title' => __('BLIK', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => implode(' ', $nameArrayBLIK),
        'default' => 'no',
    ),
    
    'dotpay_channel_show' => array(
        'title' => __('Widget', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => __('Display payment channels in a shop', 'dotpay-payment-gateway'),
        'default' => 'yes',
    ),
    'dotpay_security' => array(
        'title' => __('Security', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => __('Protect data sent', 'dotpay-payment-gateway'),
        'default' => 'yes',
    ),
    'dotpay_test' => array(
        'title' => __('Testing environment', 'dotpay-payment-gateway'),
        'label' => __('Only payment simulation.', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'dotpay-payment-gateway'),
        'type' => 'text',
        'description' => __('This controls the title which user see during checkout.', 'dotpay-payment-gateway'),
        'default' => 'Dotpay',
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Customer Message', 'dotpay-payment-gateway'),
        'type' => 'textarea',
        'default' => '',
    )
);

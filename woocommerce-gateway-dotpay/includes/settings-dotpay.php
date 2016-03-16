<?php

if (!defined('ABSPATH')) {
    exit;
}

$nameArrayMasterPass = array(
    __('Show separately in a shop channel', 'dotpay-payment-gateway'),
    'MasterPass (First Data Polska S.A.)',
);
$nameArrayBLIK = array(
    __('Show separately in a shop channel', 'dotpay-payment-gateway'),
    'BLIK (Polski Standard Płatności Sp. z o.o.)',
);

/**
 * Settings for Dotpay Gateway.
 */
return array(
    'enabled' => array(
        'title' => __('Enable/Disable', 'woocommerce'),
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
    'dotpay_masterpass_show' => array(
        'title' => __('MasterPass', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => implode(' ', $nameArrayMasterPass),
        'default' => 'yes',
    ),
    'dotpay_blik_show' => array(
        'title' => __('BLIK', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'label' => implode(' ', $nameArrayBLIK),
        'default' => 'yes',
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
        'label' => __('Only payment simulation. Forced PLN.', 'dotpay-payment-gateway'),
        'type' => 'checkbox',
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Title', 'woocommerce'),
        'type' => 'text',
        'description' => __('This controls the title which user sees during checkout.', 'dotpay-payment-gateway'),
        'default' => 'Dotpay',
        'desc_tip' => true,
    ),
    'description' => array(
        'title' => __('Customer Message', 'woocommerce'),
        'type' => 'textarea',
        'default' => '',
    )
);

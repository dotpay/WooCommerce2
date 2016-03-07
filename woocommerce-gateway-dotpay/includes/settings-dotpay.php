<?php

if (!defined('ABSPATH')) {
    exit;
}

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
    'dotpay_channel_show' => array(
        'title' => __('Widget', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Display payment channels in a shop', 'dotpay-payment-gateway'),
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

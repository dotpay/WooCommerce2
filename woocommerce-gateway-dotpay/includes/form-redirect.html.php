<?php

if (!defined('ABSPATH')) {
    exit;
}
    
return <<<END
    <form method="post" action="{$data['action']}">
        <h3>{$data['h3']}</h3>
        <p>{$data['p']}</p>

        <p class="form-submit">
            <input type="hidden" value="{$data['id']}" name="id">
            <input type="hidden" value="{$data['control']}" name="control">
            <input type="hidden" value="{$data['p_info']}" name="p_info">
            <input type="hidden" value="{$data['amount']}" name="amount">
            <input type="hidden" value="{$data['currency']}" name="currency">
            <input type="hidden" value="{$data['description']}" name="description">
            <input type="hidden" value="{$data['lang']}" name="lang">
            <input type="hidden" value="{$data['URL']}" name="URL">
            <input type="hidden" value="{$data['URLC']}" name="URLC">
            <input type="hidden" value="{$data['api_version']}" name="api_version">
            <input type="hidden" value="{$data['type']}" name="type">
            <input type="hidden" value="{$data['firstname']}" name="firstname">
            <input type="hidden" value="{$data['lastname']}" name="lastname">
            <input type="hidden" value="{$data['email']}" name="email">
            <input id="submit_dotpay_payment_form" class="button" type="submit" value="{$data['submit']}">
        </p>
    </form>

END;

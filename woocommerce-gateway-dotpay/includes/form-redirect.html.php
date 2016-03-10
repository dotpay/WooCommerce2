<?php

if (!defined('ABSPATH')) {
    exit;
}

$hiddenFields = '';
foreach($data['hiddenFields'] as $k => $v) {
    $hiddenFields .= <<<END
        <input type="hidden" value="{$v}" name="{$k}">

END;
}

$widgetStyle = '';
if($data['widget'] === 'yes') {
    $widgetStyle = <<<END
        <link href="{$data['action']}widget/payment_widget.min.css" rel="stylesheet">

END;
}

$widgetScript = '';
if($data['widget'] === 'yes') {
    $widgetScript = <<<END
        <script type="text/javascript" id="dotpay-payment-script" src="{$data['action']}widget/payment_widget.js"></script>
        <script type="text/javascript">
            var dotpayWidgetConfig = {
                sellerAccountId: '{$data['hiddenFields']['id']}',
                amount: '{$data['hiddenFields']['amount']}',
                currency: '{$data['hiddenFields']['currency']}',
                lang: '{$data['hiddenFields']['lang']}',
                widgetFormContainerClass: 'my-form-widget-container',
                offlineChannel: 'mark',
                offlineChannelTooltip: true
            }
        </script>

END;
}

$widgetContainer = '';
if($data['widget'] === 'yes') {
    $widgetContainer = <<<END
        <p class="my-form-widget-container"></p>

END;
}

$widgetAgreement = '';
if($data['widget'] === 'yes') {
    $widgetAgreement = <<<END
        <p>
            <label>
                <input type="checkbox" id="bylaw" name="bylaw" value="1" checked>
                {$data['agreement_bylaw']}
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" id="personal_data" name="personal_data" value="1" checked>
                {$data['agreement_personal_data']}
            </label>
        </p>

END;
}
    
return <<<END
    <form id="dotpay_form_send" method="post" action="{$data['action']}">
        <h3>{$data['h3']}</h3>
        <p>{$data['p']}</p>
        
        {$widgetStyle}
        {$widgetScript}
        {$widgetContainer}
        {$widgetAgreement}

        <p class="form-submit">
            {$hiddenFields}
            <input id="submit_dotpay_payment_form" class="button" type="submit" value="{$data['submit']}">
        </p>
    </form>

END;

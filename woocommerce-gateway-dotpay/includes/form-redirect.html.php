<?php

if (!defined('ABSPATH')) {
    exit;
}

$widgetStyle = '';
if($data['widget']) {
    $widgetStyle = <<<END
        <link href="{$data['action']}widget/payment_widget.min.css" rel="stylesheet">

END;
}

$widgetScript = '';
if($data['widget']) {
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
                offlineChannelTooltip: true,
                disabledChannels: [71, 73]
            }
        </script>

END;
}

$formsHtmlArray = array();

foreach($data['hiddenFields'] as $keyR => $valR) {
    $form = '';
    if($valR['active']) {
        $form .= '<p>';
        $form .= '<label>';
        $form .= '<input type="radio" name="strategy" form-target="' . $keyR . '">';
        $form .= '<img class="' . $keyR . '" src="' . $valR['icon'] . '">';
        $form .= '</label>';
        $form .= '<form form-target="' . $keyR . '" method="post" action="' . $data['action'] . '">';
        
        foreach($valR['fields'] as $keyF => $valF) {
            $form .= '<input type="text" value="' . $valF . '" name="' . $keyF . '">';
        }
        
        foreach($valR['agreements'] as $keyA => $valA) {
             $form .= '<p>';
             $form .= '<label>';
             $form .= '<input type="checkbox" name="' . $keyA . '" value="1" checked>';
             $form .= $valA;
             $form .= '</label>';
             $form .= '</p>';
        }
        
        $form .= '</form>';
        $form .= '</p>';
        
        array_push($formsHtmlArray, $form);
    }
}

$formsHtml = implode(' <hr> ', $formsHtmlArray);



return <<<END
    <div>
        <h3>{$data['h3']}</h3>
        <p>{$data['p']}</p>

        <style type="text/css" scoped>
            form[form-target] {
                display:none;
            }
            img.mp {
                height: 60px;
            }
            img.blik {
                height: 35px;
            }
            label {
                cursor: pointer;
            }
        </style>
        {$formsHtml}
    </div>

END;

$widgetContainer = '';
if($data['widget']) {
    $widgetContainer = <<<END
        <p class="my-form-widget-container"></p>

END;
}
    
return <<<END
    <h3>{$data['h3']}</h3>
    <p>{$data['p']}</p>
    
    <style type="text/css" scoped>
        form[id^="dotpay_form"] {
            display:none;
        }
        img.master_pass {
            height: 60px;
        }
        img.blik {
            height: 35px;
        }
        label {
            cursor: pointer;
        }
    </style>
    
    <p>
        <label>
            <input type="radio" name="strategy" form-target="mp">
            <img class="master_pass" src="{$data['iconMasterPass']}">
        </label>
        <form id="dotpay_form_send_mp" method="post" action="{$data['action']}">
            {$widgetAgreement}
        </form>
    </p>
    <hr>
    <p>
        <label>
            <input type="radio" name="strategy" form-target="b">
            <img class="blik" src="{$data['iconBLIK']}">
        </label>
        <form id="dotpay_form_send_b" method="post" action="{$data['action']}">
            {$widgetAgreement}
        </form>
    </p>
    <hr>
    <p>
        <label>
            <input type="radio" name="strategy" form-target="d">
            <img src="{$data['iconDotpay']}">
        </label>
        <form id="dotpay_form_send_d" method="post" action="{$data['action']}">
            {$widgetStyle}
            {$widgetScript}
            {$widgetContainer}
            {$widgetAgreement}
        </form>
    </p>

END;

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

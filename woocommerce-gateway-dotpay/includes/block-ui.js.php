<?php

if (!defined('ABSPATH')) {
    exit;
}

if($data['widget'] === 'yes') {
    
}

return <<<END
    /*$.blockUI({
        message: "{$data['message']}",
        baseZ: 99999,
        overlayCSS: {
            background: "#fff",
            opacity: 0.6
        },
        css: {
            padding: "20px",
            zindex: "9999999",
            textAlign: "center",
            color: "#555",
            border: "3px solid #aaa",
            backgroundColor: "#fff",
            cursor: "wait",
            lineHeight: "24px"
        }
    });
    jQuery('#submit_dotpay_payment_form').click();*/

END;

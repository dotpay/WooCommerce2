<?php

if (!defined('ABSPATH')) {
    exit;
}

$blockUI = <<<END
$.blockUI({
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

END;

if($data['widget'] === 'yes') {
    $result = <<<END
        $('#submit_dotpay_payment_form').hide();
        
        $(document).ready(function() {
            var checkChannel = false;
        
            function submitHideShow() {
                var checkBylaw = $('#bylaw').is(':checked');
                var checkPersonalData = $('#personal_data').is(':checked')
        
                if(checkChannel && checkBylaw && checkPersonalData) {
                    $('#submit_dotpay_payment_form').show();
                } else {
                    $('#submit_dotpay_payment_form').hide();
                }
            }
            
            $('body').on('click', '.channel-container', function(){
                if($(this).hasClass('not-online')) {
                     checkChannel = false;
                } else {
                    checkChannel = true;
                }
                submitHideShow();
            });
            
            $('body').on('click', '#bylaw', submitHideShow);
            $('body').on('click', '#personal_data', submitHideShow);
            
            $('body').on('click', '#submit_dotpay_payment_form', function(){
                {$blockUI}
            });
        });

END;
} else {
    $result = <<<END
        {$blockUI}
        $('#submit_dotpay_payment_form').click();

END;
}

return $result;

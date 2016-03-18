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

if($data['widget']) {
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
                var channel;
                
                $('input[name="channel"]').each(function(key, val){
                    var checked = $(val).is(':checked');
                    if(checked) {
                        channel = $(val).val();
                        return false;
                    }
                });
                
                var data = {
                    channel: channel
                };
                
                if(channel) {
                    $.post('{$data['signature_url']}', data, function(result) {
                        $('input[name="CHK"]').val(result);
                        {$blockUI}
                        $('#dotpay_form_send').submit();
                    });
                }
                
                return false;
            });
                        
            $('input[name="strategy"]').on('click', function(){
                $('form[form-target]').hide();
                $(this).each(function(key, val){
                    var checked = $(val).is(':checked');
                    var target = $(val).attr('form-target');
                    if(checked) {
                       $('form[form-target="' + target + '"]').show();
                    }
                });
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

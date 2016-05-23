function switchPV(obj) {
    if(jQuery(obj).prop('checked'))
        jQuery('.pv_option').parents('tr').show();
    else
        jQuery('.pv_option').parents('tr').hide();
}

if(typeof jQuery!="undefined") {
    jQuery(document).ready(function(){
        switchPV(jQuery('.pv_switch'));
        jQuery('.pv_switch').change(function() {
            switchPV(this);
        });
    });
}

/**
*
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to tech@dotpay.pl so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade WooCommerce to newer
* versions in the future. If you wish to customize WooCommerce for your
* needs please refer to http://www.dotpay.pl for more information.
*
*  @author    Dotpay Team <tech@dotpay.pl>
*  @copyright Dotpay
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*
*/

function switchPV(obj) {
    if(jQuery(obj).prop('checked'))
        jQuery('.pv_option').parents('tr').show();
    else
        jQuery('.pv_option').parents('tr').hide();
}

function shownumberCHcc(obj) {
    if(jQuery(obj).prop('checked'))
        jQuery('.cc_option').parents('tr').show();
    else
        jQuery('.cc_option').parents('tr').hide();
}

if(typeof jQuery!="undefined") {
    var dotpayModules = ['blik', 'mp', 'oc', 'pv', 'tc'];
    jQuery(document).ready(function(){
        var regExpToRemove = new RegExp("Dotpay_");
        jQuery("form#mainform a").filter(function () {
            return regExpToRemove.test(jQuery(this).text()); 
        }).parents('li').remove();
        switchPV(jQuery('.pv_switch'));
        jQuery('.pv_switch').change(function() {
            switchPV(this);
        });
		shownumberCHcc(jQuery('.cc_switch'));
        jQuery('.cc_switch').change(function() {
            shownumberCHcc(this);
        });
	
		 // module setup: validate ID
		 jQuery("#woocommerce_dotpay_id").attr("pattern", "[0-9]{5,8}");
		 jQuery("#woocommerce_dotpay_id").attr("title", "Dozwolone tylko cyfry (6 cyfr)");
		 jQuery("#woocommerce_dotpay_id").attr("maxlength", "8");
		 jQuery("#woocommerce_dotpay_id").prop("placeholder", "np. 123456");

			 jQuery("#woocommerce_dotpay_id").bind('keyup paste keydown', function(e)
											{
			  if (/\D/g.test(this.value))
			  {
				// Filter non-digits from input value.
				this.value = this.value.replace(/\D/g, '');
			  }
			});
		
		//  module setup: validatte ID2
		 jQuery("#woocommerce_dotpay_id2").attr("pattern", "[0-9]{5,8}");
		 jQuery("#woocommerce_dotpay_id2").attr("title", "Dozwolone tylko cyfry (6 cyfr)");
		 jQuery("#woocommerce_dotpay_id2").attr("maxlength", "8");
		 jQuery("#woocommerce_dotpay_id2").prop("placeholder", "np. 123456");

			 jQuery("#woocommerce_dotpay_id2").bind('keyup paste keydown', function(e)
											{
			  if (/\D/g.test(this.value))
			  {
				// Filter non-digits from input value.
				this.value = this.value.replace(/\D/g, '');
			  }
			});	
		
    });
	

}



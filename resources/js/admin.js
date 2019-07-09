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
  if (jQuery(obj).prop('checked')) {
    jQuery('.pv_option').parents('tr').fadeIn();
    jQuery("#woocommerce_dotpay_pin2").attr("pattern", "[a-zA-Z0-9]{16,32}");
    jQuery("#woocommerce_dotpay_pin2").prop('required', true);
    jQuery("#woocommerce_dotpay_pin2").attr("title", "Pin składa się przynajmniej z 16 a maksymalnie z 32 znaków alfanumerycznych!");
  } else {
    jQuery('.pv_option').parents('tr').fadeOut();
    jQuery("#woocommerce_dotpay_pin2").prop('required', false);
  }
}

function shownumberCHcc(obj) {
  if (jQuery(obj).prop('checked'))
    jQuery('.cc_option').parents('tr').fadeIn();
  else
    jQuery('.cc_option').parents('tr').fadeOut();
}
function showChannelNames(obj) {
  if (jQuery(obj).prop('checked'))
    jQuery('.widget_channel_names').parents('tr').fadeIn();
  else
    jQuery('.widget_channel_names').parents('tr').fadeOut();
}

function isenambledDotpaymodule(obj) {
  if (jQuery(obj).prop('checked')) {
    jQuery("#woocommerce_dotpay_id").prop('required', true);
    jQuery("#woocommerce_dotpay_pin").prop('required', true);
  } else {
    jQuery("#woocommerce_dotpay_id").prop('required', false);
    jQuery("#woocommerce_dotpay_pin").prop('required', false);
  }
}



if (typeof jQuery != "undefined") {
  var dotpayModules = ['blik', 'mp', 'oc', 'pv', 'tc'];
  jQuery(document).ready(function() {
    var regExpToRemove = new RegExp("Dotpay_");
    jQuery("form#mainform a").filter(function() {
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
    showChannelNames(jQuery('.widget_show'));
    jQuery('.widget_show').change(function() {
      showChannelNames(this);
    });

    isenambledDotpaymodule(jQuery('.dotpay_module_enable'));

    // module setup: validate ID
    jQuery("#woocommerce_dotpay_id").attr("pattern", "[0-9]{5,6}");
    jQuery("#woocommerce_dotpay_id").attr("title", "Dozwolone tylko cyfry (6 cyfr)");
    jQuery("#woocommerce_dotpay_id").attr("maxlength", "6");
    jQuery("#woocommerce_dotpay_id").prop("placeholder", "np. 123456");

    jQuery("#woocommerce_dotpay_id").bind('keyup paste keydown', function(e) {
      if (/\D/g.test(this.value)) {
        // Filter non-digits from input value.
        this.value = this.value.replace(/\D/g, '');
      }
    });

    //remove spaces from PIN input
			jQuery("#woocommerce_dotpay_pin").bind('keyup paste keydown', function(e) {
				jQuery(this).val(function(_, v){
					return v.replace(/\s+/g, '');
				});
    		});

    //  module setup: validatte ID2
    jQuery("#woocommerce_dotpay_id2").attr("pattern", "[0-9]{5,6}");
    jQuery("#woocommerce_dotpay_id2").attr("title", "Dozwolone tylko cyfry (6 cyfr)");
    jQuery("#woocommerce_dotpay_id2").attr("maxlength", "6");
    jQuery("#woocommerce_dotpay_id2").prop("placeholder", "np. 123456");

    jQuery("#woocommerce_dotpay_id2").bind('keyup paste keydown', function(e) {
      if (/\D/g.test(this.value)) {
        // Filter non-digits from input value.
        this.value = this.value.replace(/\D/g, '');
      }
    });

    // add pattern to configuration inputs
    jQuery("#woocommerce_dotpay_credit_card_channel_number").attr("pattern", "[0-9]{2,4}");
    jQuery("#woocommerce_dotpay_credit_card_channel_number").attr("title", "Dozwolone tylko cyfry");

    jQuery("#woocommerce_dotpay_pin").attr("minlength", "16");
    jQuery("#woocommerce_dotpay_pin").attr("pattern", "[a-zA-Z0-9]{16,32}");
    jQuery("#woocommerce_dotpay_pin").attr("title", "Pin składa się przynajmniej z 16 a maksymalnie z 32 znaków alfanumerycznych!");


    // decorate elements for configuration module
    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery("#woocommerce_dotpay_api_username"));
    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_api_username"]'));

    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_oneclick_show"]'));
    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_credit_card_show"]'));
    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_masterpass_show"]'));
    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_blik_show"]'));
    jQuery("<hr style='height: 3px; background: #439c91;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_channels_show"]'));

    jQuery("<hr style='height: 3px; background: #c5ccd6;'><br>").insertBefore(jQuery('#woocommerce_dotpay_dontview_currency'));
    jQuery("<hr style='height: 3px; background: #c5ccd6;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_ccPV_show"]'));
    jQuery("<hr style='height: 3px; background: #c5ccd6;'><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_test"]'));
    jQuery("<br><hr style='height: 3px; background: #c5ccd6;'>").insertAfter(jQuery('label[for="woocommerce_dotpay_enabled"]'));
    jQuery("<br><hr style='height: 3px; background: #c5ccd6;'><p style='font-weight: bold;'>Dostępne metody wysyłki</p><p>&nbsp;</p><br>").insertBefore(jQuery('label[for="woocommerce_dotpay_shipping_mapping_1"]'));
    jQuery("<br><hr style='height: 3px; background: #c5ccd6;'><p style='font-weight: bold;'>Dodatkowe ustawienia dla płatności odroczonych</p><p class='description'>Wymagana dodatkowa Umowa w celu uruchomienia kanałów płatności obsługujących tę formę płatności.</p><br>").insertBefore(jQuery('select#woocommerce_dotpay_shipping_mapping_1'));


    //  jQuery("#woocommerce_dotpay_dontview_currency").attr("pattern", "^(([A-Z]{3})\\s?,?\\s?)+");
    jQuery("#woocommerce_dotpay_dontview_currency").attr("pattern", "^(((AED|AFN|ALL|AMD|ANG|AOA|ARS|AUD|AWG|AZN|BAM|BBD|BDT|BGN|BHD|BIF|BMD|BND|BOB|BOV|BRL|BSD|BTN|BWP|BYN|BZD|CAD|CDF|CHE|CHF|CHW|CLF|CLP|CNY|COP|COU|CRC|CUC|CUP|CVE|CZK|DJF|DKK|DOP|DZD|EGP|ERN|ETB|EUR|FJD|FKP|GBP|GEL|GHS|GIP|GMD|GNF|GTQ|GYD|HKD|HNL|HRK|HTG|HUF|IDR|ILS|INR|IQD|IRR|ISK|JMD|JOD|JPY|KES|KGS|KHR|KMF|KPW|KRW|KWD|KYD|KZT|LAK|LBP|LKR|LRD|LSL|LYD|MAD|MDL|MGA|MKD|MMK|MNT|MOP|MRU|MUR|MVR|MWK|MXN|MXV|MYR|MZN|NAD|NGN|NIO|NOK|NPR|NZD|OMR|PAB|PEN|PGK|PHP|PKR|PLN|PYG|QAR|RON|RSD|RUB|RWF|SAR|SBD|SCR|SDG|SEK|SGD|SHP|SLL|SOS|SRD|SSP|STN|SVC|SYP|SZL|THB|TJS|TMT|TND|TOP|TRY|TTD|TWD|TZS|UAH|UGX|USD|USN|UYI|UYU|UZS|VEF|VND|VUV|WST|XAF|XAG|XAU|XBA|XBB|XBC|XBD|XCD|XDR|XOF|XPD|XPF|XPT|XSU|XTS|XUA|XXX|YER|ZAR|ZMW|ZWL))\\s?,?\\s?)+");
    jQuery("#woocommerce_dotpay_dontview_currency").attr("title", "Zostaw pole puste lub podaj walutę w formacie ISO 4217, np: EUR lub EUR,USD");

    jQuery('label[for="woocommerce_dotpay_id"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_pin"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_ccPV_currency"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");

    jQuery('label[for^="woocommerce_dotpay_shipping_mapping_"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_dontview_currency"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_credit_card_show"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_oneclick_show"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_api_username"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_api_password"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");
    jQuery('label[for="woocommerce_dotpay_masterpass_show"] > span.woocommerce-help-tip').attr("style", "color: #2aaeed;font-size: 22px;");

    jQuery('p:contains("Online payment")').remove();
    jQuery('p:contains("Płatności online")').remove();
    jQuery('tr').each(function(element) {
      var dotpayGatewayid = jQuery(this).attr('data-gateway_id');
      if (dotpayGatewayid !== undefined) {
        if (dotpayGatewayid.indexOf('Dotpay_') !== -1) {
          jQuery(this).find('a[class$="button alignright"]').remove();
          jQuery(this).find('a[class$="wc-payment-gateway-method-title"]').contents().unwrap();
          jQuery(this).find('a[class$="wc-payment-gateway-method-toggle-enabled"]').attr('href', '').css({
            'cursor': 'pointer',
            'pointer-events': 'none'
          });

        }
        if (dotpayGatewayid === 'dotpay') {
          jQuery(this).find('td[class$="description"]').prepend("<img src='data:image/png;base64,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' width='65' height='19' alt='Dotpay' style='vertical-align: text-bottom'>");
        }

      }
    });



  });


}

/**
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License version 3.0
* that is bundled with this package in the file LICENSE.txt
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade this module to a newer
* versions in the future. If you wish to customize this module for your needs
* please refer to CustomizationPolicy.txt file inside our module for more information.
*
* @author Webkul IN
* @copyright Since 2010 Webkul
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
*/

$(document).off('click', '#preordercustom').on('click', '#preordercustom', function(e) {
    e.preventDefault();
    //$('#preordercustom').attr('disabled', 'disabled');
    var id_prod = $(this).attr('data-id-product');
    var id_cust = $(this).attr('data-id-customer');
    var id_attr = $(this).attr('data-id-product-attr');
    var wk_custom_price = $('#wk_custom_price').val();
    $('#'+id_prod+'-'+id_attr+'-loader').removeClass();

    if (id_cust == 0) {
        $.growl.warning({message:loginreq, duration:5000});
        $('#preordercustom').removeAttr('disabled');
        $('#'+id_prod+'-'+id_attr+'-loader').css('display', 'none');
        $('#'+id_prod+'-'+id_attr+'-loader').addClass('customloader');
        return false;
    }

    if (id_prod && id_cust) {
        $.ajax({
            type: 'POST',
            headers: {
                "cache-control": "no-cache"
            },
            url: specificProcess +'?rand=' + new Date().getTime(),
            async: false,
            cache: false,
            data: {
                id_product :  id_prod,
                id_cust : id_cust,
                id_attr : id_attr,
                custom_price : wk_custom_price
            },
            success: function(data) {
                $('#preordercustom').removeAttr('disabled');
                $('#'+id_prod+'-'+id_attr+'-loader').addClass('customloader');
                if (data == 1) {
                    //ajaxCart.add(id_prod, id_attr, true, null, 1, null);
                    var wk_quantity_wanted = $('#quantity_wanted').attr('min');
                    window.location.href = specificProcess + '?addpreorder=1&qty='+wk_quantity_wanted+'&id_product='+id_prod+'&ipa='+id_attr;
                } else if (data == 2) {
                    $.growl.warning({message:invalidPrice, duration:5000});
                } else if (data == 4) {
                    $.growl.warning({message:noLonger, duration:5000});
                } else if (data == 5) {
                    $.growl.warning({message:minPrice, duration:5000});
                } else if (data == 6) {
                    $.growl.warning({message:customPrice, duration:5000});
                } else if (data == 7) {
                    $.growl.warning({message:customPriceLower, duration:5000});
                }
            },
        });
    }
});

$(document).ready(function(){
    prestashop.on('updateCart',function (event) {
        if (typeof event.reason != 'undefined') {
          var id_product = event.reason.idProduct;
          var id_product_attribute = event.reason.idProductAttribute;
          if(id_product){
            $.ajax({
                url: specificProcess +'?rand=' + new Date().getTime(),
                method:'POST',
                async: false,
                cache: false,
                data:{
                    id_product : id_product,
                    id_cust : id_customer,
                    custom_price_add : 1,
                    id_attr: id_product_attribute,
                },
              success:function(data){
              }
            })
          }
        }
    });
})
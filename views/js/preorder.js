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

$(document).ready(function() {
    //complete before expected date preorder
    var beforeExpected = 0;
    $('input[name=complete_preorder]').on('change', function(){
        if ($(this).val() == 1) {
            beforeExpected = 1;
        } else {
            beforeExpected = 0;
        }
    });

    // search product for making preorder
    var suggestpageurl = $('#suggestpageurl').val();

   function productFormatResult(item) {
        if(!item){
            return
        }
        itemTemplate = "<div class='media wksearchresult' data-id-product='"+item.id_product+"'>";
        itemTemplate += "<div class='pull-left'>";
        itemTemplate += "<img class='media-object' width='50' src='" + item.image + "' alt='" + no_image_text + "'>";
        itemTemplate += "</div>";
        itemTemplate += "<div class='media-body'>";
        itemTemplate += "<h4 class='media-heading'>" + item.name + ' (' + item.id_product + ')' + "</h4>";
        itemTemplate += "</div>";
        itemTemplate += "</div>";
        $('.wksearchresult').css('padding','8px');
        $('.wksearchresult').css('margin-top','0px');
        $('.wksearchresult').css('border-bottom','1px solid #ccc');
        $('.wksearchresult').parents('li').removeClass('select2-result-unselectable').addClass('select2-result-selectable')
        $('#select2-results-1 li:first').addClass('select2-highlighted')
        setTimeout(function(){
            $('.wksearchresult').parents('li').removeClass('select2-result-unselectable').addClass('select2-result-selectable')
            $('#select2-results-1 li:first').addClass('select2-highlighted')
        },200)
        return itemTemplate;
    }

    function productFormatSelection(item) {
        if(!item){
            return
        }
        return item.name;
    }

    var selectedProduct;
    $('#pre_product').select2({
        minimumInputLength: 3,
        width: '100%',
        dropdownCssClass: "bootstrap",
        ajax: {
            url: suggestpageurl,
            dataType: 'json',
            data: function (term) {
                return {
                    query: term,
                    ajax: true,
                    action: 'getProducts',
                };
            },
            results: function (data) {
                if(data){
                    return {
                        results: data
                    }
                } else{
                    return {
                        results: []
                    }
                }
            }
        },
        formatResult: productFormatResult,
        formatSelection: productFormatSelection,
    })
    .on("select2-selecting", function(e) {
        selectedProduct = e.object
        addPreorderItem(selectedProduct)
    });
    $('.wksearchresult').click(addPreorderItem(selectedProduct))

    function addPreorderItem(ele) {
        if(typeof ele == 'undefined'){
            return
        }
        var id = ele.id_product
        $('#pre_enable').val('1');
        $(':hidden').filter('#pre_product_id').val(id);
        $('#products_ul').html('');
        $('#get_product_name_div').html(ele.name);
        $('#get_product_name_div').css('padding', '3px 7px');
        $('#getproduct_name').val(ele.name);
        $('#pre_product').val(ele.name);
        $('#loadingajax').html('<img style="padding-left: 11px;width: 3%;" src=' + status_img_ps_dir + 'loader.gif>');
        $('#pre_product_comb').html('')
        $('#preorder_enabled').show('slow');
        $.ajax({
            url: suggestpageurl,
            type: 'POST',
            data: {
                ajax: true,
                action: 'getProducts',
                id_product: id,
            },
            async: false,
            success: function(result) {
                $('#loadingajax').html('');
                if (result) {
                    var data = $.parseJSON(result);
                    var i = 0;
                    if (data != '') {
                    // console.log(Object.keys(data).length)
                        //added for all combinations option on ne preorder page
                        if(Object.keys(data).length > 1){
                            $.each(data, function(key, item) {
                                if (typeof item.id_product_attribute != "undefined") {
                                    $('#pre_product_comb').parents('.form-group').show();
                                    $('#pre_product_comb').append('<option value="all">' + all_combs_text + '</option>');
                                    return false;
                                }
                            });
                        }
                        $.each(data, function(key, item) {
                            $('#payment-method').val('1')
                            callFullPaymentEvent();
                            $('#wk_amount_preorder').hide();
                            $('#wk_precentage_preorder').hide();
                            if (typeof item.id_product_attribute === "undefined") {
                                default_price = '';
                                default_qty = '';
                                $('#pre_product_comb').parents('.form-group').hide();
                                $('#original_price, #preorder_price').val(item.price_tax_incl);
                                $('#pre-quantity').val(item.qty_in_stock);
                            } else if (item.default_on == 1) {
                                $('#pre_product_comb').append("<option selected='selected' value='" + item.id_product_attribute + "' rel='" + item.qty_in_stock + "'>" + item.attributes.slice(0, -1) + "</option>");
                                default_price = item.price_tax_excl;
                                default_qty = item.qty_in_stock;
                                $('#original_price, #preorder_price').val(item.price_tax_excl);
                                $('#pre-quantity').val(item.qty_in_stock);
                            } else {
                                $('#pre_product_comb').append("<option value='" + item.id_product_attribute + "' rel='" + item.qty_in_stock + "'>" + item.attributes.slice(0, -1) + "</option>");
                                if (i == 0) {
                                    $('#original_price, #preorder_price').val(item.price_tax_excl);
                                    $('#pre-quantity').val(item.qty_in_stock);
                                }
                            }
                            i++;
                        });
                        if (typeof default_price !== "undefined" && default_price != '') {
                            $('#original_price, #preorder_price').val(default_price);
                        }
                        if (typeof default_qty !== "undefined" && default_qty != '') {
                            $('#pre-quantity').val(default_qty);
                        }
                    }
                } else {
                    $('#pre_product_comb').parents('.form-group').hide();
                    // $('#pre_product_comb').append("<option value='0' rel='0'>" + no_combs_text + "</option>");
                }
            }
        });
        selectedProduct = null;
        $('#pre_product').select2("val", "");
    }
    // change original price with combination price
    $('#pre_product_comb').on('change', function() {
        var id_attr = $("#pre_product_comb option:selected").val();
        var id_prod = $('#pre_product_id').val();
        if (id_attr == 'all') {
            id_attr = 0;
        }
        if (id_prod) {
            $.ajax({
                url: suggestpageurl,
                type: 'POST',
                data: {
                    ajax: true,
                    action: 'getProductPrice',
                    id_product: id_prod,
                    id_attribute: id_attr
                },
                async: false,
                success: function(result) {
                    $('#loadingajax').html('');
                    var data = $.parseJSON(result);
                    if (data != '') {
                        $('#original_price, #preorder_price').val(data.price);
                        $('#pre-quantity').val(data.qty);
                    }
                }
            });
        }
    });

    $(document).on('click', 'input[name="complete_preorder"]', function(){
        if($('#complete_preorder_on').prop('checked')){
            $('#wk_enable_preorder').hide();
        }else{
            $('#wk_enable_preorder').show();
        }
    })
    // before submitting preorder form validate the fields

    $(document).on('submit', '#wk_preorder_product_form', function() {
        var original_price = parseFloat($('#original_price').val());
        var id_product = $('#pre_product_id').val();
        var preorderprice = parseFloat($('#preorder_price').val());
        var pre_quantity = $('#pre-quantity').val();
        var pre_maxquantity = $('#pre-maxquantity').val();
        var preorder_date = $('#preorder_date').val();
        var payment_type = $('#payment_type').val();
        var payment_method = $('#payment-method').val();
        var CurrentDate = $.now();
        var t = preorder_date.split(/[- :]/);
        var preordetimestamp = new Date(t[0], t[1] - 1, t[2], t[3], t[4], t[5]).getTime();

        if (!id_product) {
            $.growl.error({title:error_title, message:productid, duration:5000});
            return false;
        }
        if (!$('#preorder_price').val()) {
            $.growl.error({title:error_title, message:prieceempty, duration:5000});
            return false;
        }else if(!original_price){
            $.growl.error({title:error_title, message:zeroPriceProduct, duration:5000});
            return false;
        }

        if (payment_method == 1) {
            if (original_price != preorderprice) {
                $.growl.error({title:error_title, message:priceoriginal, duration:5000});
                return false;
            }
        } else if (payment_method == 2 || payment_method == 3) {
            if (payment_type == 1) {
	            if (preorderprice >= 100) {
                    $.growl.error({title:error_title, message:percentageup, duration:5000});
	                return false;
	            } else if (preorderprice < 0) {
                    $.growl.error({title:error_title, message:percentagedown, duration:5000});
	                return false;
	            }
	        } else if (payment_type == 2) {
	        	if (preorderprice >= original_price) {
                    $.growl.error({title:error_title, message:priceoriginalgreater, duration:5000});
	                return false;
	            } else if (preorderprice < 0) {
                    $.growl.error({title:error_title, message:percentagedown, duration:5000});
	                return false;
	            }
	        }
        }

        if (!pre_quantity) {
            $.growl.error({title:error_title, message:quantityempty, duration:5000});
            return false;
        } else if (pre_quantity <= 0) {
            $.growl.error({title:error_title, message:quantitylow, duration:5000});
            return false;
        } else if (isNaN(pre_quantity)) {
            $.growl.error({title:error_title, message:quantityerror, duration:5000});
            return false;
        }

        if (!pre_maxquantity) {
            $.growl.error({title:error_title, message:maxquantityempty, duration:5000});
            return false;
        } else if (pre_maxquantity <= 0) {
            $.growl.error({title:error_title, message:maxquantitylow, duration:5000});
            return false;
        } else if (isNaN(pre_maxquantity)) {
            $.growl.error({title:error_title, message:quantityerror, duration:5000});
            return false;
        }
        if (parseInt(pre_maxquantity) > parseInt(pre_quantity)) {
            $.growl.error({title:error_title, message:max_pre_quantityerror, duration:5000});
            return false;
        }
        if(preorder_date.trim() == ''){
            $.growl.error({title:error_title, message:dateempty, duration:5000});
            return false;
        }
        if (!beforeExpected) {
            if (preordetimestamp <= CurrentDate) {
                $.growl.error({title:error_title, message:dateerror, duration:5000});
                return false;
            }
        }

    });

    // On ready check whether pre-order enable or disable
    if ($("input[name=preorder_enable]").val() == '1') {
        $('#preorder_enabled').show();
    } else {
        $('#preorder_enabled').hide();
    }

    // on click Enable or Disable Pre-Order feature
    $('.enable_preorder').on('change', function() {
        if ($("input[name=preorder_enable]:checked").val() == '1') {
            $('#preorder_on').attr('checked', 'checked');
            $('#preorder_off').removeAttr('checked');
        } else {
            $('#preorder_on').removeAttr('checked');
            $('#preorder_off').attr('checked', 'checked');
        }
    });

    // ----------------------------------------------------------------------//
    // on ready check whether pre-order feature selected full payment or partial
    if ($("#payment-method option:selected").val() == '1') {
        callFullPaymentEvent();
        $('#wk_amount_preorder').hide();
        $('#wk_precentage_preorder').hide();
    } else {
        callPartialPaymentEvent();
        $('#wk_amount_preorder').hide();
        $('#wk_precentage_preorder').show();
    }

    //on change check payment type
    $('#payment-method').on('change', function() {
        if ($('#payment-method option:selected').val() == '1') {
            callFullPaymentEvent();
            $('#preorder_price').val($('#original_price').val());
            $('#wk_amount_preorder').hide();
            $('#wk_precentage_preorder').hide();
        } else if ($('#payment-method option:selected').val() == '2' || $('#payment-method option:selected').val() == '3') {
            callPartialPaymentEvent();
            if ($('#payment_type option:selected').val() == '1') {
                $('#percentage_symbol').show();
                $('#currency_symbol').hide();
                $('#wk_amount_preorder').hide();
                $('#wk_precentage_preorder').show();
            } else if ($('#payment_type option:selected').val() == '2') {
                $('#percentage_symbol').hide();
                $('#currency_symbol').show();
                $('#wk_amount_preorder').show();
                $('#wk_precentage_preorder').hide();
            }
            $('#preorder_price').val('');
        } else {
            $('#partial-method').hide();
        }
    });
    // ---------------------------------------------------------------------//

    //on ready check partial payment option (amount/percentage)
    if ($('#payment_type option:selected').val() == '1') {
        if ($("#payment-method option:selected").val() == '1') {
            $('#currency_symbol').show();
            $('#percentage_symbol').hide();
            $('#wk_amount_preorder').hide();
            $('#wk_precentage_preorder').hide();
        } else {
            $('#currency_symbol').hide();
            $('#percentage_symbol').show();
            $('#wk_amount_preorder').hide();
            $('#wk_precentage_preorder').hide();
        }
    } else {
        $('#currency_symbol').show();
        $('#percentage_symbol').hide();
    }

    //on change check partial payment option (amount/percentage)
    $('#payment_type').on('change', function() {
        if ($('#payment_type option:selected').val() == '1') {
            $('#currency_symbol').hide();
            $('#percentage_symbol').show();
            $('#wk_amount_preorder').hide();
            $('#wk_precentage_preorder').show();
            $('#preorder_price').val('').removeAttr('readonly');
        } else {
            $('#currency_symbol').show();
            $('#wk_amount_preorder').show();
            $('#wk_precentage_preorder').hide();
            $('#percentage_symbol').hide();
        }
    });

    // ---------------------------------------------------------------------//

    // datetimepicker of the bootstrap/jquery
    $('.datetimepicker').datetimepicker({
        dateFormat: 'yy-mm-dd',
        showSecond: true,
        todayBtn: true,
        timeFormat: "hh:mm:ss",
        minDate: 0,
        changeYear: true
    });

    // adding z-index property on datetime picker for position
    $('#preorder_date').on('click', function() {
        $('#ui-datepicker-div').css("z-index", "1000");
    });
});

function callFullPaymentEvent() {
    $('#preorder_price_box').removeClass('col-lg-6');
    $('#preorder_price_box').addClass('col-lg-12');
    $('#preorder_price').attr('readonly', 'readonly');
    $('#partial-method').hide();
    $('#percentage_symbol').hide();
    $('#currency_symbol').show();
}

function callPartialPaymentEvent() {
    $('#preorder_price_box').removeClass('col-lg-12');
    $('#preorder_price_box').addClass('col-lg-6');
    $('#preorder_price').removeAttr('readonly');
    $('#partial-method').show();
}

function removeExistProduct() {
    $('#preorder_enabled').hide('slow');
    $('#pre_enable').val('0');
    $('#get_product_name_div').html('');
    $('#pre_product_comb').empty();
    $('#pre-quantity').val('');
    $('#original_price, #preorder_price').val('');
    $('#get_product_name_div').css('padding', '0px 0px');
    $(':hidden').filter('#pre_product_id').val('');
}

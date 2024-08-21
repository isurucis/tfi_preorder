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

$(document).ready(function(){
    $('#table-wk_preorder_product tr').hover(function(){
        if($('.wk_preview_open').length){
        }else{
            $('.preview-toggle').css('visibility','hidden');
            // $(this).find('.preview-toggle').show();
        }
        $(this).find('.preview-toggle').css('visibility','unset');
        if($(this).find('.preview-toggle').hasClass('wk_preview_open')){
        }else{
            $(this).find('.preview-toggle').find('.js-expand').show();
            $(this).find('.preview-toggle').find('.js-collapse').hide();
        }
    })

    if($('input[name="WK_LIMITED_TIME"]:checked').val() == 1){
        $('.wk-limited-time').parents('.form-group').show();
        $('input[name="WK_STOCK_ROLLBACK"]').parents('.form-group').show();
    } else {
        $('.wk-limited-time').parents('.form-group').hide();
        $('input[name="WK_STOCK_ROLLBACK"]').parents('.form-group').hide();
    }

    $('#WK_SHOW_PAYMENT_TYPE_off').click(function () {
        $('.wk_show_payment_content').hide();
    });
    $('#WK_SHOW_PAYMENT_TYPE_on').click(function () {
        $('.wk_show_payment_content').show();
    });
    $( "#table-wk_preorder_product tr" ).mouseleave(function(){
        $('.preview-toggle').css('visibility','hidden');
        $('.wk_preview_open').css('visibility','unset');
    })

    $(document).on('click','.preview-toggle',function(){
        if($(this).find('.js-collapse').css('display') != 'none'){
            $(this).find('.js-expand').show();
            $(this).find('.js-collapse').hide();
            $(this).removeClass('wk_preview_open')
            $('#wk_preorder-preview-content').remove()
        }else{
            $('.preview-toggle').css('visibility','hidden');
            $(this).css('visibility','unset');
            $('.preview-toggle').removeClass('wk_preview_open')
            $('#wk_preorder-preview-content').remove()
            $(this).addClass('wk_preview_open');
            $(this).find('.js-expand').hide();
            $(this).find('.js-collapse').show();
            $.ajax({
                url:$(this).attr('data-url'),
                method:'POST',
                data:{
                    id: $(this).attr('data-id'),
                    ajax:true,
                    action:'displayPreorderPreview'
                },
                success: function(data){
                    $('<tr id="wk_preorder-preview-content"><td colspan="10">'+data+'<td></tr>').insertAfter($('.wk_preview_open').parent().parent())
                }
            })
        }
    })

    $(document).on('change', 'input[name="WK_LIMITED_TIME"]', function(){
        if($(this).val() == 1){
            $('.wk-limited-time').parents('.form-group').show();
            $('input[name="WK_STOCK_ROLLBACK"]').parents('.form-group').show();
        } else {
            $('.wk-limited-time').parents('.form-group').hide();
            $('input[name="WK_STOCK_ROLLBACK"]').parents('.form-group').hide();
        }
    })
})
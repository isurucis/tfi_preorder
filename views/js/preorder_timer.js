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
    if(typeof not_allowed != 'undefined' && not_allowed == true){
        setTimeout(function(){
            $('button.add-to-cart').html(add_to_cart);
            $('button.add-to-cart').attr('disabled','disabled');
            $('#product-availability').html('<span><i class="material-icons product-unavailable">&#xE14B;</i>'+outofstock+'</span>');
        }, 2);
    } else {
        if (typeof clockTime === 'undefined') {
            clockTime = 00000000;
        } else {
            setTimeout(function(){
                $('button.add-to-cart').html(preorder_now);
                var product_page_product_qty = parseInt($('#quantity_wanted').val());
                var wk_remaining_qty = parseInt(remaining_qty);
                if(wk_remaining_qty == '0' || wk_remaining_qty < product_page_product_qty) {
                    $('button.add-to-cart').attr('disabled','disabled');
                    $('#product-availability').html('<i class="material-icons product-unavailable"></i>'+outofstock);
                }
            }, 2);
        }
    }

    var time_left_time_stamp = clockTime;
    var timeRemaining = clockTime*1000;
    setTimeout(function(){

    },timeRemaining);

    var clock = $('.expected-date').FlipClock(time_left_time_stamp, {
        clockFace: 'DailyCounter',
        countdown: true,
        language : iso_code,
        callbacks: {
            stop: function () {
                window.location.reload(true);
                $('.expected-date').hide('slow', function(){
                    window.location.reload(true);
                });
            }
        }
    });
});
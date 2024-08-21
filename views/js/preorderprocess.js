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
// make preorder complete button inside the order details
$(document).ready(function() {
    // Check if order has not preorder product
    if ($('[name="pre_id_product"]').length == 0) {
        return;
    }
    var i;
    var total_product = $("table#order-products").find('tbody').find('tr').length;
    var tableHead = $("table#order-products").find('thead').find('tr').find('th:last-child');
    var id_order = $('#preorder_id_order').val();
    $("<th>" + preordertitle + "</th>").insertAfter(tableHead);
    for (i = 0; i < total_product; i++) {
        var id_product = $('#pre_id_product_' + i).val();
        var id_attr = $('#pre_id_product_' + i).attr('pre-id-attr');
        var pre_quant = $('#pre_id_product_' + i).attr('pre-quantity');
        var tableBody = $("table#order-products").find('tbody').find('tr:eq(' + i + ')').find('td:last-child');
        if (typeof id_product === 'undefined') {
            $("<td><span></span></td>").insertAfter(tableBody);
            continue;
        }

        if($(document).width()>=768){
            if (id_product > 0) {
                $("<td><a class='btn btn-primary hidden-xs-down' href='"+preorder_process_url+"&qty=" + pre_quant + "&id_product=" + id_product + "&ipa=" + id_attr + "&token=" + static_token + "'>" + complete_preorder + "</a></td>").insertAfter(tableBody);
            }  else if (id_product == 'complete') { // preorder complated
                $("<td><span>" + completed_preorder + "</span></td>").insertAfter(tableBody);
            } else if (id_product == 'no') {
                $("<td><span>-----</span></td>").insertAfter(tableBody);
            } else {
                $("<td><span>" + notavail + "</span></td>").insertAfter(tableBody);
            }
        } else {
            if (id_product > 0) {
                // var tableBody = $("table#order-products").find('tbody').find('tr:eq(' + i + ')').find('td:last-child');
                var tableMobBody = $('.order-item:eq(' + i + ')');
                $("<th><a class='btn btn btn-primary' style='margin:8px;' href='"+preorder_process_url+"&qty=" + pre_quant + "&id_product=" + id_product + "&ipa=" + id_attr + "&token=" + static_token + "'>" + complete_preorder + "</a></th>").insertAfter(tableMobBody);
            }
        }
    }
});
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
	$('td a').on('click', function() {
        var href = $(this).attr('href');
        href = href.split('?');
        if (typeof href[1] !== 'undefined') {
            href = href[1].split('=');
            if (href[0] == 'submitReorder') {
                var id_order = href[2];
                if (id_order > 0) {
                    is_preorder = checkPreorderProduct(id_order);
                    if (is_preorder == 0){      // quantity is low to reorder preorder product
						$.growl.warning({message:notenoughstock, duration:5000});
                        return false;
                    } else if (is_preorder == 1){
                        //return false;
                    } else if (is_preorder == 2){       // customer has to login to buy preorder product
						$.growl.warning({message:loginerror, duration:5000});
                        return false;
                    } else if (is_preorder == 3){       // customer has to not specific price
						$.growl.warning({message:specificerror, duration:5000});
                        return false;
                    }
                }
            }
        }
    });
});

// reorder from inside the particular order
$(document).on('click', 'div a.button-primary', function(e) {
	var href = $(this).attr('href');
    href = href.split('?');
    href = href[1].split('=');
    if (href[0] == 'submitReorder') {
    	var id_order = href[2];
		var is_preorder = checkPreorderProduct(id_order);
		if (is_preorder == 0){		// quantity is low to reorder preorder product
			$.growl.warning({message:notenoughstock, duration:5000});
			return false;
		} else if (is_preorder == 1){
			//return false;
		} else if (is_preorder == 2){		// customer has to login to buy preorder product
			$.growl.warning({message:loginerror, duration:5000});
			return false;
		} else if (is_preorder == 3){		// customer has to login to buy preorder product
			$.growl.warning({message:specificerror, duration:5000});
			return false;
		}
	}
});

function checkPreorderProduct(id_order)
{
	window.res = 0;
	$.ajax({
		url 	: checkpreorder_url,
		type 	: 'POST',
		cache 	: false,
		async	: false,
		data 	: {
			id_order : id_order,
			reorder : 1,
		},
		success : function(data) {
			if (data == 1) {
				window.res = 1;
			} else if (data == 0) {
				window.res = 0;
			} else if (data == 2) {
				window.res = 2;
			} else if (data == 3) {
				window.res = 3;
			}
		}
	});
	return res;
}

$(".preorder_order_row").on("click", function() {
    var id_order = $(this).attr('is_id_order');
    window.location.href = mporderdetail + "?id_order=" + id_order;
});

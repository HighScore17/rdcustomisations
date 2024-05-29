/**
*
* JavaScript file that controls global admin notices (enables permanent dismissal)
*
*/
(function($){

	"use strict";

	$( document ).ready(function() {

		/* Admin notice permanent dismissal */
		$('.b2bking_activate_woocommerce_notice button').on('click', function(){
			// Run ajax function that permanently dismisses notice
			var datavar = {
	            action: 'b2bking_dismiss_activate_woocommerce_admin_notice',
	            security: b2bking_notice.security,
	        };

			$.post(ajaxurl, datavar, function(response){
				// do nothing
			});

		});

	});

})(jQuery);
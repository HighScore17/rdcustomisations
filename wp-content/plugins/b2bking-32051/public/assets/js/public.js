/**
*
* JavaScript file that handles public side JS
*
*/
(function($){

	"use strict";

	$( document ).ready(function() {
		
		/* Fix for country selector SCROLL ISSUE in popup (e.g. login in Flatsome theme) */
		$('.b2bking_country_field_selector select').on('select2:open', function (e) {
	        const evt = "scroll.select2";
	        $(e.target).parents().off(evt);
	        $(window).off(evt);
	      });

		/* Conversations START */

		// On load conversation, scroll to conversation end
		// if conversation exists
		if ($('#b2bking_conversation_messages_container').length){
			$("#b2bking_conversation_messages_container").scrollTop($("#b2bking_conversation_messages_container")[0].scrollHeight);
		}

		// On clicking "Send message" inside conversation in My account
		$('#b2bking_conversation_message_submit').on('click', function(){
			// Run ajax request
			var datavar = {
	            action: 'b2bkingconversationmessage',
	            security: b2bking_display_settings.security,
	            message: $('#b2bking_conversation_user_new_message').val(),
	            conversationid: $('#b2bking_conversation_id').val(),
	        };

			$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
				location.reload();
			});
		});

		// On clicking "New conversation" button
		$('#b2bking_myaccount_make_inquiry_button').on('click', function(){
			// hide make inquiry button
			$('#b2bking_myaccount_make_inquiry_button').css('display','none');
			// hide conversations
			$('.b2bking_myaccount_individual_conversation_container').css('display','none');
			// hide conversations pagination
			$('.b2bking_myaccount_conversations_pagination_container').css('display','none');
			// show new conversation panel
			$('.b2bking_myaccount_new_conversation_container').css('display','block');
		});

		// On clicking "Close X" button
		$('.b2bking_myaccount_new_conversation_close').on('click', function(){
			// hide new conversation panel
			$('.b2bking_myaccount_new_conversation_container').css('display','none');
			// show new conversation button
			$('#b2bking_myaccount_make_inquiry_button').css('display','inline-flex');
			// show conversations
			$('.b2bking_myaccount_individual_conversation_container').css('display','block');
			// show pagination
			$('.b2bking_myaccount_conversations_pagination_container').css('display','flex');
			
		});

		// On clicking "Send inquiry" button
		$('#b2bking_myaccount_send_inquiry_button').on('click', function(){
			// if textarea empty OR title empty
			if (!$.trim($("#b2bking_myaccount_textarea_conversation_start").val()) || !$.trim($("#b2bking_myaccount_title_conversation_start").val())) {
				// Show "Text area or title is empty" message
			} else {
				// Run ajax request
				var datavar = {
		            action: 'b2bkingsendinquiry',
		            security: b2bking_display_settings.security,
		            message: $('#b2bking_myaccount_textarea_conversation_start').val(),
		            title: $('#b2bking_myaccount_title_conversation_start').val(),
		            type: $("#b2bking_myaccount_conversation_type").children("option:selected").val(),
		        };

		        // If DOKAN addon exists, pass vendor
		        if (typeof b2bkingdokan_display_settings !== 'undefined') {
		        	datavar.vendor = $('#b2bking_myaccount_conversation_vendor').val();
		        }

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					// redirect to conversation
					window.location = response;
				});
			}
		});

		/* Conversations END */

		/* Request a custom quote START*/

		// On clicking "Request a Custom Quote" button
		$('body').on('click', '#b2bking_request_custom_quote_button', function(){

			// If DOKAN addon exists
			if (typeof b2bkingdokan_display_settings !== 'undefined') {
				// check number of vendors
				var vendors = [];
				$('.variation dd').each(function(){
					let value = $(this).text();
					if (!vendors.includes(value)){
						vendors.push(value);
					}
				});
				var vendorsNr = vendors.length;
				if (parseInt(vendorsNr) > 1){
					alert(b2bkingdokan_display_settings.request_many_vendors);
					return;
				}
			}

			// show hidden elements above the button
			$('#b2bking_request_custom_quote_textarea, #b2bking_request_custom_quote_textarea_abovetext, .b2bking_request_custom_quote_text_label, #b2bking_request_custom_quote_name, #b2bking_request_custom_quote_email').css('display','block');
			// replace the button text with "Send custom quote request"
			$('#b2bking_request_custom_quote_button').text(b2bking_display_settings.send_quote_request);

			// On clicking "Send custom quote request"
			$('#b2bking_request_custom_quote_button').addClass('b2bking_send_custom_quote_button');
		});

		$('body').on('click', '.b2bking_send_custom_quote_button', function(){

			// if no fields are empty
			let empty = 'no';
			if ($('#b2bking_request_custom_quote_name').val() === '' || $('#b2bking_request_custom_quote_email').val() === ''){
				empty = 'yes';
			}

			if (empty === 'no'){

				// validate email
				if (validateEmail($('#b2bking_request_custom_quote_email').val())){
					// run ajax request
					var datavar = {
			            action: 'b2bkingrequestquotecart',
			            security: b2bking_display_settings.security,
			            message: $('#b2bking_request_custom_quote_textarea').val(),
			            name: $('#b2bking_request_custom_quote_name').val(),
			            email: $('#b2bking_request_custom_quote_email').val(),
			            title: b2bking_display_settings.custom_quote_request,
			            type: 'quote',
			        };

			        // If DOKAN addon exists, pass vendor
			        if (typeof b2bkingdokan_display_settings !== 'undefined') {
			        	var vendors = [];
			        	$('.variation dd').each(function(){
			        		let value = $(this).text();
			        		if (!vendors.includes(value)){
			        			vendors.push(value);
			        		}
			        	});
			        	datavar.vendor = vendors[0];
			        }

					$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
						let conversationurl = response;

						// if user is logged in redirect to conversation, else show alert
						if(jQuery('#b2bking_request_custom_quote_name').length){
							alert(b2bking_display_settings.quote_request_success);
							$('#b2bking_request_custom_quote_button').css('display','none');
							location.reload();
						} else {
						    window.location = conversationurl;
						}
						
					});

				} else {
					alert(b2bking_display_settings.quote_request_invalid_email);
				}
				
			} else {
				alert(b2bking_display_settings.quote_request_empty_fields);
			}
		});

		function validateEmail(email) {
			if (jQuery('#b2bking_request_custom_quote_email').val() !== undefined){
				var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
				return regex.test(email);
			} else {
				return true;
			}
		}

		/* Request a custom quote END*/

		/* Offers START*/

		// On clicking "add offer to cart"
		$('.b2bking_offer_add').on('click',function(){
			if (b2bking_display_settings.disableofferadd !== 1){
				let offerId = $(this).val();

				// run ajax request
				var datavar = {
			            action: 'b2bkingaddoffer',
			            security: b2bking_display_settings.security,
			            offer: offerId,
			        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					// redirect to cart
					window.location = b2bking_display_settings.carturl;
				});
			}
		});
		

		/* Offers END */


		/* Custom Registration Fields START */
		// Dropdown
		addCountryRequired(); // woocommerce_form_field does not allow required for country, so we add it here
		// On load, show hide fields depending on dropdown option
		showHideRegistrationFields();

		$('.country_to_state').trigger('change');
		$('#b2bking_registration_roles_dropdown').change(showHideRegistrationFields);
		$('.b2bking_country_field_selector select').change(showHideRegistrationFields);
		$('select#billing_country').change(showHideRegistrationFields);
		function addCountryRequired(){
			$('.b2bking_country_field_req_required').prop('required','true');
			$('.b2bking_custom_field_req_required select').prop('required','true');
		}
		// on state change, reapply required
		$('body').on('DOMSubtreeModified', '#billing_state_field', function(){
			//let selectedValue = $('#b2bking_registration_roles_dropdown').val();
			//$('.b2bking_custom_registration_'+selectedValue+' #billing_state_field.b2bking_custom_field_req_required #billing_state').prop('required','true');
			//$('.b2bking_custom_registration_allroles #billing_state_field.b2bking_custom_field_req_required #billing_state').prop('required','true');
		});

		function showHideRegistrationFields(){

			// Hide all custom fields. Remove 'required' for hidden fields with required
			$('.b2bking_custom_registration_container').css('display','none');
			$('.b2bking_custom_field_req_required').removeAttr('required');
			$('.b2bking_custom_field_req_required select').removeAttr('required');
			$('.b2bking_custom_field_req_required #billing_state').removeAttr('required');
			
			// Show fields of all roles. Set required
			$('.b2bking_custom_registration_allroles').css('display','block');
			$('.b2bking_custom_registration_allroles .b2bking_custom_field_req_required').prop('required','true');
			$('.b2bking_custom_registration_allroles .b2bking_custom_field_req_required select').prop('required','true');
			setTimeout(function(){
				$('.b2bking_custom_registration_allroles .b2bking_custom_field_req_required #billing_state').prop('required','true');
	        },125);

			// Show all fields of the selected role. Set required
			let selectedValue = $('#b2bking_registration_roles_dropdown').val();
			$('.b2bking_custom_registration_'+selectedValue).css('display','block');
			$('.b2bking_custom_registration_'+selectedValue+' .b2bking_custom_field_req_required').prop('required','true');
			$('.b2bking_custom_registration_'+selectedValue+' .b2bking_custom_field_req_required select').prop('required','true');
			setTimeout(function(){
	        	$('.b2bking_custom_registration_'+selectedValue+' .b2bking_custom_field_req_required #billing_state').prop('required','true');
	        },225);

			// if there is more than 1 country
			if(parseInt(b2bking_display_settings.number_of_countries) !== 1){
				// check VAT available countries and selected country. If vat not available, remove vat and required
				let vatCountries = $('#b2bking_vat_number_registration_field_countries').val();
				let selectedCountry = $('.b2bking_country_field_selector select').val();
				if (selectedCountry === undefined){
					selectedCountry = $('select#billing_country').val();
				}
				if (vatCountries !== undefined){
					if ( (! (vatCountries.includes(selectedCountry))) || selectedCountry.trim().length === 0 ){
						// hide and remove required
						$('.b2bking_vat_number_registration_field_container').css('display','none');
						$('#b2bking_vat_number_registration_field').removeAttr('required');
					}
				}
			}

			// New for My Account VAT
			if (parseInt(b2bking_display_settings.myaccountloggedin) === 1){
				// check VAT countries
				let vatCountries = $('#b2bking_custom_billing_vat_countries_field input').prop('placeholder');
				let billingCountry = $('#billing_country').val();
				if (vatCountries !== undefined){
					if ( (! (vatCountries.includes(billingCountry))) || billingCountry.trim().length === 0){
						$('.b2bking_vat_field_container, #b2bking_checkout_registration_validate_vat_button').removeClass('b2bking_vat_visible, b2bking_vat_hidden').addClass('b2bking_vat_hidden');
						$('.b2bking_vat_field_required_1 input').removeAttr('required');
					} else {
						$('.b2bking_vat_field_container, #b2bking_checkout_registration_validate_vat_button').removeClass('b2bking_vat_visible, b2bking_vat_hidden').addClass('b2bking_vat_visible');
						$('.b2bking_vat_field_required_1 .optional').after('<abbr class="required" title="required">*</abbr>');
						$('.b2bking_vat_field_required_1 .optional').remove();
						$('.b2bking_vat_field_required_1 input').prop('required','true');
					}
				}
			}
			
		}

		// when billing country is changed , trigger update checkout. Seems to be a change in how WooCommerce refreshes the page. In order for this to work well with tax exemptions, run update checkout
		$('#billing_country').on('change', function() {
			setTimeout(function(){
				jQuery(document.body).trigger("update_checkout");
			},250);
		});

		// Hook into updated checkout for WooCommerce
		jQuery( document ).on( 'updated_checkout', function() {

		    // check VAT countries
		    let vatCountries = $('#b2bking_custom_billing_vat_countries_field input').val();
		    let billingCountry = $('#billing_country').val();
		    if (vatCountries !== undefined){
		    	if ( (! (vatCountries.includes(billingCountry))) || billingCountry.trim().length === 0){
		    		$('.b2bking_vat_field_container').removeClass('b2bking_vat_visible, b2bking_vat_hidden').addClass('b2bking_vat_hidden');
		    		$('.b2bking_vat_field_required_1 input').removeAttr('required');
		    	} else {
		    		$('.b2bking_vat_field_container').removeClass('b2bking_vat_visible, b2bking_vat_hidden').addClass('b2bking_vat_visible');
		    		$('.b2bking_vat_field_required_1 .optional').after('<abbr class="required" title="required">*</abbr>');
		    		$('.b2bking_vat_field_required_1 .optional').remove();
		    		$('.b2bking_vat_field_required_1 input').prop('required','true');
		    	}
		    }
		} );

		// VALIDATE VAT AT CHECKOUT REGISTRATION
		$('#b2bking_checkout_registration_validate_vat_button').on('click', function(){

			$('#b2bking_checkout_registration_validate_vat_button').text(b2bking_display_settings.validating);
			var vatnumber = $('#b2bking_vat_number_registration_field').val();
			if (vatnumber === undefined){
				vatnumber = $('.b2bking_vat_field_container input[type="text"]').val().trim();
			} else {
				vatnumber = $('#b2bking_vat_number_registration_field').val().trim();
			}
			
			var datavar = {
	            action: 'b2bkingvalidatevat',
	            security: b2bking_display_settings.security,
	            vat: vatnumber,
	            country: $('#billing_country').val(),
	        };

			$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
				if (response === 'valid'){
					createCookie('b2bking_validated_vat_status','validated_vat', false);
					createCookie('b2bking_validated_vat_number', vatnumber, false);
					$('#b2bking_vat_number_registration_field').prop('readonly', true);
					$('#b2bking_checkout_registration_validate_vat_button').prop('disabled', true);
					$('#b2bking_checkout_registration_validate_vat_button').text(b2bking_display_settings.vatvalid);
					// refresh checkout for prices
					jQuery(document.body).trigger("update_checkout");
				} else if (response === 'invalid'){

					eraseCookie('b2bking_validated_vat_status');

					$('#b2bking_checkout_registration_validate_vat_button').text(b2bking_display_settings.vatinvalid);
				}
			});
		});

		function createCookie(name, value, days) {
		    var expires;

		    if (days) {
		        var date = new Date();
		        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		        expires = "; expires=" + date.toGMTString();
		    } else {
		        expires = "";
		    }
		    document.cookie = encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + "; path=/";
		}

		function eraseCookie(name) {
		    createCookie(name, "", -1);
		}

		// if country is changed, re-run validation
		$('.woocommerce-checkout #billing_country').change(function(){
			eraseCookie('b2bking_validated_vat_status');
			$('#b2bking_checkout_registration_validate_vat_button').text(b2bking_display_settings.validatevat);
			$('#b2bking_vat_number_registration_field').prop('readonly', false);
			$('#b2bking_vat_number_registration_field').val('');
			$('#b2bking_checkout_registration_validate_vat_button').prop('disabled', false);
			// refresh checkout for prices
			jQuery(document.body).trigger("update_checkout");
		});

		// Check if delivery country is different than shop country
		if (parseInt(b2bking_display_settings.differentdeliverycountrysetting) === 1){
			// if setting is enabled
			$('#shipping_country').change(exempt_vat_delivery_country);
		}
		function exempt_vat_delivery_country(){
			var datavar = {
	            action: 'b2bkingcheckdeliverycountryvat',
	            security: b2bking_display_settings.security,
	            deliverycountry: $('#shipping_country').val(),
	        };

			$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
				setTimeout(function(){
					jQuery(document.body).trigger("update_checkout");
				}, 250);
			});
		}

		// add validation via JS to checkout
		$('body').on('click', '#place_order', function(e){
			var fields = $(".b2bking_custom_field_req_required");
			$.each(fields, function(i, field) {
				if ($(field).css('display') !== 'none' && $(field).parent().parent().css('display') !== 'none'){
					if (!field.value || field.type === 'checkbox'){
						let parent = $(field).parent();
						alert(parent.find('label').text().slice(0,-2) + ' ' + b2bking_display_settings.is_required);
						e.preventDefault();
					}
				}
			}); 
		});

		// force select a country on registration
		$('button.woocommerce-form-register__submit').on('click',function(e){
			if ($('.b2bking_country_field_selector').parent().css('display') !== 'none'){
				if ($('.b2bking_country_field_selector select').val() === 'default'){
					e.preventDefault();
					alert(b2bking_display_settings.must_select_country);
				}
			}
		});




		/* Custom Registration Fields END */

		/* Subaccounts START */
		// On clicking 'New Subaccount'
		$('.b2bking_subaccounts_container_top_button').on('click',function(){
			// Hide subaccounts, show new subaccount
			$('.b2bking_subaccounts_new_account_container').css('display','block');
			$('.b2bking_subaccounts_account_container').css('display','none');
			$('.b2bking_subaccounts_container_top_button').css('display','none');
		});
		// On clicking 'Close X', reverse
		$('.b2bking_subaccounts_new_account_container_top_close').on('click',function(){
			$('.b2bking_subaccounts_new_account_container').css('display','none');
			$('.b2bking_subaccounts_account_container').css('display','block');
			$('.b2bking_subaccounts_container_top_button').css('display','inline-flex');
		});

		// On clicking "Create new subaccount"
		$('.b2bking_subaccounts_new_account_container_content_bottom_button').on('click',function(){
			// clear displayed validation errors
			$('.b2bking_subaccounts_new_account_container_content_bottom_validation_errors').html('');
			let validationErrors = '';
			// get username and email and password
			let username = $('input[name="b2bking_subaccounts_new_account_username"]').val().trim();
			let email = $('input[name="b2bking_subaccounts_new_account_email_address"]').val().trim();
			let password = $('input[name="b2bking_subaccounts_new_account_password"]').val().trim();

			// check against regex
			if (/^(?!.*[_.]$)(?=.{8,30}$)(?![_.])(?!.*[_.]{2})[a-zA-Z0-9._-]+$/.test(username) === false){
				validationErrors += b2bking_display_settings.newSubaccountUsernameError;
			}
			if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) === false){
				validationErrors += b2bking_display_settings.newSubaccountEmailError;
			}
			if (/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d!$%@#£€*?&]{8,}$/.test(password) === false){
				validationErrors += b2bking_display_settings.newSubaccountPasswordError;
			}

			if (validationErrors !== ''){
				// show errors
				$('.b2bking_subaccounts_new_account_container_content_bottom_validation_errors').html(validationErrors);
			} else {
				// proceed with AJAX account registration request

				// get all other data
				let name = $('input[name="b2bking_subaccounts_new_account_name"]').val().trim();
				let jobTitle = $('input[name="b2bking_subaccounts_new_account_job_title"]').val().trim();
				let phone = $('input[name="b2bking_subaccounts_new_account_phone_number"]').val().trim();

				// checkboxes are true or false
				let checkboxBuy = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_buy"]').prop('checked'); 
				let checkboxViewOrders = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_orders"]').prop('checked');
				let checkboxViewOffers = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_offers"]').prop('checked');
				let checkboxViewConversations = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_conversations"]').prop('checked');
				let checkboxViewLists = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_lists"]').prop('checked');

				// send AJAX account creation request
				var datavar = {
		            action: 'b2bking_create_subaccount',
		            security: b2bking_display_settings.security,
		            username: username,
		            password: password, 
		            name: name,
		            jobTitle: jobTitle,
		            email: email,
		            phone: phone,
		            permissionBuy: checkboxBuy,
		            permissionViewOrders: checkboxViewOrders,
		            permissionViewOffers: checkboxViewOffers,
		            permissionViewConversations: checkboxViewConversations,
		            permissionViewLists: checkboxViewLists,
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					if (response.startsWith('error')){
						$('.b2bking_subaccounts_new_account_container_content_bottom_validation_errors').html(b2bking_display_settings.newSubaccountAccountError+':'+response);
					} else if (response === 'error_maximum_subaccounts'){
						$('.b2bking_subaccounts_new_account_container_content_bottom_validation_errors').html(b2bking_display_settings.newSubaccountMaximumSubaccountsError);
					} else {
						// go to subaccounts endpoint
						window.location = b2bking_display_settings.subaccountsurl;
					}
				});
			}
		});

		// On clicking "Update subaccount"
		$('.b2bking_subaccounts_edit_account_container_content_bottom_button').on('click',function(){
			// get details and permissions
			let subaccountId = $('.b2bking_subaccounts_edit_account_container_content_bottom_button').val().trim();
			let name = $('input[name="b2bking_subaccounts_new_account_name"]').val().trim();
			let jobTitle = $('input[name="b2bking_subaccounts_new_account_job_title"]').val().trim();
			let phone = $('input[name="b2bking_subaccounts_new_account_phone_number"]').val().trim();

			// checkboxes are true or false
			let checkboxBuy = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_buy"]').prop('checked'); 
			let checkboxViewOrders = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_orders"]').prop('checked');
			let checkboxViewOffers = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_offers"]').prop('checked');
			let checkboxViewConversations = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_conversations"]').prop('checked');
			let checkboxViewLists = $('input[name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_lists"]').prop('checked');

			// send AJAX account creation request
			var datavar = {
	            action: 'b2bking_update_subaccount',
	            security: b2bking_display_settings.security,
	            subaccountId: subaccountId,
	            name: name,
	            jobTitle: jobTitle,
	            phone: phone,
	            permissionBuy: checkboxBuy,
	            permissionViewOrders: checkboxViewOrders,
	            permissionViewOffers: checkboxViewOffers,
	            permissionViewConversations: checkboxViewConversations,
	            permissionViewLists: checkboxViewLists,
	        };

	        $.post(b2bking_display_settings.ajaxurl, datavar, function(response){
				// go to subaccounts endpoint
				window.location = b2bking_display_settings.subaccountsurl;
			});
		});

		// on clicking close inside subaccount edit
		$('.b2bking_subaccounts_edit_account_container_top_close').on('click',function(){
			// go to subaccounts endpoint
			window.location = b2bking_display_settings.subaccountsurl;
		});

		// on clicking delete user, run same function as reject user
		$('.b2bking_subaccounts_edit_account_container_content_bottom_button_delete').on('click', function(){
			if (confirm(b2bking_display_settings.are_you_sure_delete)){
				var datavar = {
		            action: 'b2bkingrejectuser',
		            security: b2bking_display_settings.security,
		            user: $('.b2bking_subaccounts_edit_account_container_content_bottom_button').val().trim(),
		            issubaccount: 'yes',
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					// go to subaccounts endpoint
					window.location = b2bking_display_settings.subaccountsurl;
				});
			}
		});

		/* Subaccounts END */

		/* Bulk order form START */
		// On clicking "new line", prepend newline to button container
		$('.b2bking_bulkorder_form_container_newline_button').on('click',function(){
			// add line
			$('.b2bking_bulkorder_form_container_newline_container').before('<div class="b2bking_bulkorder_form_container_content_line">'+
           			'<input type="text" class="b2bking_bulkorder_form_container_content_line_product" >'+
           			'<input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty">'+
            		'<div class="b2bking_bulkorder_form_container_content_line_subtotal">'+
            			b2bking_display_settings.currency_symbol+'0'+
            		'</div>'+
            		'<div class="b2bking_bulkorder_form_container_content_line_livesearch">'+
            		'</div>'+
            	'</div>');
		});

		// on click 'save list' in bulk order form
		$('.b2bking_bulkorder_form_container_bottom_save_button').on('click', function(){
			let title = window.prompt(b2bking_display_settings.save_list_name, "");

			if (title !== '' && title !== null){

				let productString = ''; 
				// loop through all bulk order form lines
				document.querySelectorAll('.b2bking_bulkorder_form_container_content_line_product').forEach(function(textinput) {
					var classList = $(textinput).attr('class').split(/\s+/);
					$.each(classList, function(index, item) {
						// foreach line if it has selected class, get selected product ID 
					    if (item.includes('b2bking_selected_product_id_')) {
					    	let productID = item.split('_')[4];
					    	let quantity = $(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_qty').val();
					    	if (quantity > 0){
					    		// set product
					    		productString+=productID+':'+quantity+'|';
					    	}
					    }
					});
				});
				// if not empty, send
				if (productString !== ''){
					var datavar = {
			            action: 'b2bking_bulkorder_save_list',
			            security: b2bking_display_settings.security,
			            productstring: productString,
			            title: title,
			        };

					$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
						alert(b2bking_display_settings.list_saved);
					});
				}
			}
		});

		$('body').on('input', '.b2bking_bulkorder_form_container_content_line_product', function(){

			let parent = $(this).parent();
			let inputValue = $(this).val();
			let searchbyval = $('#b2bking_bulkorder_searchby_select').val();
			if (typeof(searchbyval) === "undefined"){
				searchbyval = 'productname';
			}
			parent.find('.b2bking_bulkorder_form_container_content_line_livesearch').html('<img class="b2bking_loader_img" src="'+b2bking_display_settings.loaderurl+'">');
			parent.find('.b2bking_bulkorder_form_container_content_line_livesearch').css('display','block');
			if (inputValue.length > 0){ // min x chars
				// run search AJAX function 
				var datavar = {
		            action: 'b2bking_ajax_search',
		            security: b2bking_display_settings.security,
		            searchValue: inputValue,
		            searchby: searchbyval,
		            dataType: 'json'
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					
					let display = '';
					let results = response;
					if (parseInt(results) !== 1234){ // 1234 Integer for Empty
						let resultsObject = JSON.parse(results);
						Object.keys(resultsObject).forEach(function (index) {
							if (index.includes('B2BKINGPRICE')){
								prices[index] = resultsObject[index];
							} else {
								display += '<div class="b2bking_livesearch_product_result productid_'+index+'">'+resultsObject[index]+'</div>';
							}
						});
					} else {
						display = b2bking_display_settings.no_products_found;
					}
					
					parent.find('.b2bking_bulkorder_form_container_content_line_livesearch').html(display);
				});
			} else {
				parent.find('.b2bking_bulkorder_form_container_content_line_livesearch').css('display','none');
			}
		});

		var prices = Object;

		// on clicking on search result, set result in field
		$('body').on('click', '.b2bking_livesearch_product_result', function(){
			let title = $(this).text();
			let parent = $(this).parent().parent();
			var classList = $(this).attr('class').split(/\s+/);
			$.each(classList, function(index, item) {
			    if (item.includes('productid')) {

			        let productID = item.split('_')[1];
	        		// set input disabled
			        parent.find('.b2bking_bulkorder_form_container_content_line_product').val(title);
			        parent.find('.b2bking_bulkorder_form_container_content_line_product').css('color', b2bking_display_settings.colorsetting );
			        parent.find('.b2bking_bulkorder_form_container_content_line_product').css('font-weight', 'bold');
			        parent.find('.b2bking_bulkorder_form_container_content_line_product').addClass('b2bking_selected_product_id_'+productID);
			        parent.find('.b2bking_bulkorder_form_container_content_line_product').after('<button class="b2bking_bulkorder_clear">'+b2bking_display_settings.clearx+'</button>');
			        setTimeout(function(){
			        	parent.find('.b2bking_bulkorder_form_container_content_line_product').prop('disabled', true);
			        	parent.find('.b2bking_bulkorder_form_container_content_line_livesearch').css('display','none');
			        },125);
			       
			    }
			});
			calculateBulkOrderTotals();
		});

		$('body').on('click', '.b2bking_bulkorder_clear', function(){
			let parent = $(this).parent();
			let line = parent.find('.b2bking_bulkorder_form_container_content_line_product');
			let qty = parent.find('.b2bking_bulkorder_form_container_content_line_qty');
			line.prop('disabled', false);
			line.removeAttr("style");
			line.val('');
			qty.val('');
			var classList = line.attr('class').split(/\s+/);
			$.each(classList, function(index, item) {
			    if (item.includes('b2bking_selected_product_id_')) {
			    	line.removeClass(item);
			    }
			});

			calculateBulkOrderTotals();
			$(this).remove();

		});

		// on click add to cart
		$('.b2bking_bulkorder_form_container_bottom_add_button').on('click', function(){

			let productString = ''; 
			// loop through all bulk order form lines
			document.querySelectorAll('.b2bking_bulkorder_form_container_content_line_product').forEach(function(textinput) {
				var classList = $(textinput).attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					// foreach line if it has selected class, get selected product ID 
				    if (item.includes('b2bking_selected_product_id_')) {
				    	let productID = item.split('_')[4];
				    	let quantity = $(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_qty').val();
				    	if (quantity > 0){
				    		// set product
				    		productString+=productID+':'+quantity+'|';
				    	}
				    }
				});
			});
			// if not empty, send
			if (productString !== ''){
				var datavar = {
		            action: 'b2bking_bulkorder_add_cart',
		            security: b2bking_display_settings.security,
		            productstring: productString,
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					window.location = b2bking_display_settings.carturl;
				});
			}
		});

		// on product or quantity change, calculate totals
		$('body').on('input', '.b2bking_bulkorder_form_container_content_line_qty', function(){
			calculateBulkOrderTotals();
		});

		function calculateBulkOrderTotals(){
			let total = 0;
			// loop through all bulk order form lines
			document.querySelectorAll('.b2bking_bulkorder_form_container_content_line_product').forEach(function(textinput) {
				var classList = $(textinput).attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					// foreach line if it has selected class, get selected product ID 
				    if (item.includes('b2bking_selected_product_id_')) {
				    	let productID = item.split('_')[4];
				    	let quantity = $(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_qty').val();
				    	if (quantity > 0){
		    				let index = productID + 'B2BKINGPRICE';
		    				let price = parseFloat(prices[index]);

		    				let subtotal = price * quantity;
		    				subtotal = parseFloat(subtotal.toFixed(2));
		    				setTimeout(function(){
		    					if (parseInt(b2bking_display_settings.accountingsubtotals) === 1){
		    						// get price html via WC PRICE
    								var datavar = {
    						            action: 'b2bking_accountingsubtotals',
    						            security: b2bking_display_settings.security,
    						            pricesent: subtotal,
    						        };

    								$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
    									$(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_subtotal').html(response);
    								});

		    					} else {
		    						$(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_subtotal').text(b2bking_display_settings.currency_symbol+subtotal);
		    					}
		    				}, 100);
		    				total = total + subtotal;
		    				total = parseFloat(total.toFixed(2));
				    	} else {
				    		$(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_subtotal').text(b2bking_display_settings.currency_symbol+0);
				    	}
				    } else {
				    	$(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_subtotal').text(b2bking_display_settings.currency_symbol+0);	
				    }
				});

			});

			if (parseInt(b2bking_display_settings.accountingsubtotals) === 1){
				// get price html via WC PRICE
				var datavar = {
		            action: 'b2bking_accountingsubtotals',
		            security: b2bking_display_settings.security,
		            pricesent: total,
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					$('.b2bking_bulkorder_form_container_bottom_total .woocommerce-Price-amount').html(response);
				});

			} else {
				$('.b2bking_bulkorder_form_container_bottom_total .woocommerce-Price-amount').text(b2bking_display_settings.currency_symbol+total);	
			}

		}

		/* Bulk order form END */

		/* Purchase Lists START */

		// Download Purchase Lists
		// On clicking download price list
		$('.b2bking_download_list_button').on('click', function() {
			// get list id
			var classList = $(this).attr('class').split(/\s+/);
			$.each(classList, function(index, item) {
				// foreach line if it has selected class, get selected product ID 
			    if (item.includes('id_')) {
			    	let listid = item.split('_')[1];
			    	window.location = b2bking_display_settings.ajaxurl + '?action=b2bkingdownloadpurchaselist&list='+listid+'&security=' + b2bking_display_settings.security;
			    }
			});
	    });

		// purchase lists data table
		$('#b2bking_purchase_lists_table').dataTable({
            "language": {
                "url": b2bking_display_settings.datatables_folder+b2bking_display_settings.purchase_lists_language_option+'.json'
            }
        });

		// on click 'trash' in purchase list
		$('.b2bking_bulkorder_form_container_bottom_delete_button').on('click', function(){
			if(confirm(b2bking_display_settings.are_you_sure_delete_list)){
				let listId = $(this).val();

				var datavar = {
		            action: 'b2bking_purchase_list_delete',
		            security: b2bking_display_settings.security,
		            listid: listId
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					window.location = b2bking_display_settings.purchaselistsurl;
				});
			}
		});

		// on click 'update' in purchase list
		$('.b2bking_bulkorder_form_container_bottom_update_button').on('click', function(){
			let listId = $(this).val();

			let productString = ''; 
			// loop through all bulk order form lines
			document.querySelectorAll('.b2bking_bulkorder_form_container_content_line_product').forEach(function(textinput) {
				var classList = $(textinput).attr('class').split(/\s+/);
				$.each(classList, function(index, item) {
					// foreach line if it has selected class, get selected product ID 
				    if (item.includes('b2bking_selected_product_id_')) {
				    	let productID = item.split('_')[4];
				    	let quantity = $(textinput).parent().find('.b2bking_bulkorder_form_container_content_line_qty').val();
				    	if (quantity > 0){
				    		// set product
				    		productString+=productID+':'+quantity+'|';
				    	}
				    }
				});
			});
			// if not empty, send
			if (productString !== ''){
				var datavar = {
		            action: 'b2bking_purchase_list_update',
		            security: b2bking_display_settings.security,
		            productstring: productString,
		            listid: listId
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					window.location = b2bking_display_settings.purchaselistsurl;
				});
			}
		});

		// if this is a purchase list
		let isPurchaseList = $('#b2bking_purchase_list_page').val();
		if (isPurchaseList !== undefined){
			// add "selected" style to list items
			$('.b2bking_bulkorder_form_container_content_line_product').css('color', b2bking_display_settings.colorsetting);

			$('.b2bking_bulkorder_form_container_content_line_product').css('font-weight', 'bold' );
			// get pricing details that will allow to calculate subtotals
			document.querySelectorAll('.b2bking_bulkorder_form_container_content_line_product').forEach(function(textinput) {
				let inputValue = $(textinput).val();
				var datavar = {
		            action: 'b2bking_ajax_search',
		            security: b2bking_display_settings.security,
		            searchValue: inputValue,
		            searchType: 'purchaseListLoading',
		            dataType: 'json'
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					let results = response;
					if (results !== '"empty"'){
						let resultsObject = JSON.parse(results);
						Object.keys(resultsObject).forEach(function (index) {
							if (index.includes('B2BKINGPRICE')){
								prices[index] = resultsObject[index];
							}
						});
					}
				});
			});

		}

		$('body').on('click', '.b2bking_add_cart_to_purchase_list_button', function(){

			let title = window.prompt(b2bking_display_settings.save_list_name, "");
			if (title !== '' && title !== null){

				var datavar = {
		            action: 'b2bking_save_cart_to_purchase_list',
		            security: b2bking_display_settings.security,
		            title: title,
		            dataType: 'json'
		        };

				$.post(b2bking_display_settings.ajaxurl, datavar, function(response){
					$('.b2bking_add_cart_to_purchase_list_button').text(b2bking_display_settings.list_saved);
					$('.b2bking_add_cart_to_purchase_list_button').prop('disabled', true);
				});
			}
		});

		/* Purchase Lists END */

		/* Checkout Registration Fields Checkbox*/
		/*
		if (parseInt(b2bking_display_settings.ischeckout) === 1){
			showHideCheckout();
			$('#createaccount').change(showHideCheckout);
			function showHideCheckout(){
				if($('#createaccount').prop('checked')) {
			    	$('.b2bking_custom_registration_allroles, .b2bking_registration_roles_dropdown_section').css('display','block');
			    	$('.b2bking_custom_field_req_required').prop('required','true');

			    } else {      
			    	$('.b2bking_custom_registration_allroles, .b2bking_registration_roles_dropdown_section').css('display','none');
			    	$('.b2bking_custom_field_req_required').removeAttr('required');
			    }
			};
		}*/

	});

})(jQuery);

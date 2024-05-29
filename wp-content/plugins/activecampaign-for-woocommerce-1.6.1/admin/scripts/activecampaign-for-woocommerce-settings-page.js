document.addEventListener("DOMContentLoaded", function () {
    const $ = window.jQuery;

    $( "#activecampaign-update-api-button" ).click(function(e) {
        e.preventDefault();
        const form = $('#activecampaign-for-woocommerce-options-form');
        const nonceVal = $('#activecampaign_for_woocommerce_settings_nonce_field');

        let data = {};
        data.api_url = form.find('input[name="api_url"]').val();
        data.api_key = form.find('input[name="api_key"]').val();
        data.activecampaign_for_woocommerce_settings_nonce_field = nonceVal.attr('value');
        data.action = 'api_test';

        let url = $(this).attr("data-value");
        let type = 'POST';

        $.ajax({
            url: url,
            type: type,
            data:data
        }).done(response => {
            if (response.data.notices && response.data.notices.length > 0) {
                alert(response.data.notices[0].message);
            }
        }).fail(response => {
            if (response.responseJSON.data.errors && response.responseJSON.data.errors.length > 0) {
                alert(response.responseJSON.data.errors[0].message);
            }
        });
    });

    $("#activecampaign-run-fix-connection").click(function(e) {
        if (confirm("Please confirm that you would like to reset your connection ID.")) {
            const nonceVal = jQuery('#activecampaign_for_woocommerce_settings_nonce_field');
            jQuery.ajax({
                url: ajaxurl,
                data: {
                    action: "activecampaign_for_woocommerce_reset_connection_id",
                    activecampaign_for_woocommerce_settings_nonce_field: nonceVal.attr('value')
                }
            }).done(response => {
                jQuery('#activecampaign-run-fix-connection-status').html( response.data );
            }).fail(response => {
                jQuery('#activecampaign-run-fix-connection-status').html( response.data );
            });
        }
    });

    $("#activecampaign-run-clear-plugin-settings").click(function(e) {
        if (confirm("Are you sure you want to erase all settings? This plugin will not function until proper API settings have been set again.")) {
            const nonceVal = jQuery('#activecampaign_for_woocommerce_settings_nonce_field');
            jQuery.ajax({
                url: ajaxurl,
                data: {
                    action: "activecampaign_for_woocommerce_clear_all_settings",
                    activecampaign_for_woocommerce_settings_nonce_field: nonceVal.attr('value')
                }
            }).done(response => {
                jQuery('#activecampaign-run-clear-plugin-settings-status').html( response.data );
            }).fail(response => {
                jQuery('#activecampaign-run-clear-plugin-settings-status').html( response.data );
            });
        }else{
            return false;
        }
    });

    $("#activecampaign-manual-mode").click(function(e){
        $("#manualsetup").show().addClass('scale-up-top');
    });

    const form = $('#activecampaign-for-woocommerce-options-form');

    form.submit(function(e) {
        let url = form.attr("action");
        let type = form.attr("method");
        let data = form.serialize();
        $('.notice').remove();
        e.preventDefault();
        $.ajax({
            url,
            type,
            data
        }).done(response => {
            form.after('<div class="notice-success notice is-dismissiblee"><p>Settings saved</p><button id="my-dismiss-admin-message" class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
            window.location.search += '&manual_setup=1';
        }).fail(response => {
            if (response.responseJSON.data.errors && response.responseJSON.data.errors.length > 0) {
                let errors = response.responseJSON.data.errors;
                $.each(errors, function( key, error) {
                  form.after('<div class="error notice is-dismissible"><p>' + error.message + '</p><button id="my-dismiss-admin-message" class="notice-dismiss" type="button"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
                });
            }
        });
    });
});
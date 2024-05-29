jQuery(document).ready(function($) {
    $('#activecampaign-run-abandoned-cart').click(function (e) {
        if ( ! $(e.target).hasClass('disabled')) {
            var data = {
                'action': 'activecampaign_for_woocommerce_manual_abandonment_sync',
                'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
            };

            $('#activecampaign-run-abandoned-cart-status').html('Running abandoned sync process...');
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: data
                }).done(response => {
                    $('#activecampaign-run-abandoned-cart-status').html(response.data);
                    resolve(response.data);
                }).fail(response => {
                    $('#activecampaign-run-abandoned-cart-status').html(response.responseJSON.data);
                    reject(response.responseJSON.data)
                });
            });
        }
    });

    $('.activecampaign-sync-abandoned-cart').click(function (e) {
        var row = $(e.target).parents('tr');
        var rowId = row.attr('rowid');
        var t = e.target;
        $(t).addClass('ac-spinner');

        var data = {
            'action': 'activecampaign_for_woocommerce_sync_abandoned_cart_row',
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val(),
            'rowId': rowId,
        };
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data
        }).done(response => {
            $(t).addClass('success').removeClass('ac-spinner');
        }).fail(response => {
            $(t).addClass('fail').removeClass('ac-spinner');
        });

        return false;
    });

    $('.activecampaign-delete-abandoned-cart').click(function (e) {

        if (confirm('Are you sure you want to delete this abandoned cart entry?')) {
            var row = $(e.target).parents('tr');
            var rowId = row.attr('rowid');
            var data = {
                'action': 'activecampaign_for_woocommerce_delete_abandoned_cart_row',
                'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val(),
                'rowId': rowId,
            };

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data
            }).done(response => {
                row.remove();
            });
            
            return false;
        } else {
            return false;
        }
    });
});

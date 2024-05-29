jQuery(document).ready(function($) {
    var scheduled = false;
    var wasRunning = false;
    var wasStopped = false;

    $('#activecampaign-run-historical-sync').click(function (e) {
        if ( ! $(e.target).hasClass('disabled')) {
            $('#activecampaign-run-historical-sync').addClass('disabled');
            $('#activecampaign-run-historical-sync-status').html('Historical sync will start shortly...').show();
            var action = 'activecampaign_for_woocommerce_schedule_bulk_historical_sync';

            if ($('input[name="activecampaign_for_woocommerce_settings_sync_type"]:checked').val() === 'single') {
                action = 'activecampaign_for_woocommerce_schedule_single_historical_sync';
            }

            runAjax({
                'action': action,
                'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
            });
            scheduled = true;
            wasStopped = false;
            wasRunning = false;
            startUpdateCheck();
        }
    });

    $('#activecampaign-cancel-historical-sync').click(function (e) {
        runAjax({
            'action': 'activecampaign_for_woocommerce_cancel_historical_sync',
            'type': 1,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
        });
        wasStopped = true;
        $('#sync-start-section').show();
        disableStopButtons();
    });

    $('#activecampaign-pause-historical-sync').click(function (e) {
        runAjax({
            'action': 'activecampaign_for_woocommerce_pause_historical_sync',
            'type': 2,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
        });
        wasStopped = true;
        $('#sync-start-section').show();
        disableStopButtons();

    });
    $('#activecampaign-reset-historical-sync').click(function (e) {
        runAjax({
            'action': 'activecampaign_for_woocommerce_reset_historical_sync',
            'type': 2,
            'activecampaign_for_woocommerce_settings_nonce_field': $('#activecampaign_for_woocommerce_settings_nonce_field').val()
        });
        enableStopButtons();
        hideRunSection();
    });

    updateStatus();
    // Check sync status
    var statInt = setInterval(updateStatus, 3000);

    function startUpdateCheck() {
        statInt = setInterval(updateStatus, 3000);
    }

    function cancelUpdateCheck() {
        clearInterval(statInt);
    }

    function runAjax(data) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data
            }).done(response => {
                console.log('runAjax done response', response);
                $('#activecampaign-run-historical-sync-status').html(response.data);
                resolve(response.data);
            }).fail(response => {
                console.log('runAjax fail response', response);
                $('#activecampaign-run-historical-sync-status').html(response.responseJSON.data);
                reject(response.responseJSON.data)
            });
        });
    }

    function showRunSection() {
        $('#sync-run-section').show();
    }

    function hideRunSection() {
        $('#sync-run-section').hide();
    }

    function disableStopButtons(){
        $('#activecampaign-cancel-historical-sync').addClass('disabled');
        $('#activecampaign-pause-historical-sync').addClass('disabled');
    }

    function enableStopButtons(){
        $('#activecampaign-cancel-historical-sync').removeClass('disabled');
        $('#activecampaign-pause-historical-sync').removeClass('disabled');
    }

    function enableStartSection() {
        $('#activecampaign-run-historical-sync').removeClass('disabled');
        $('#sync-start-section').show();

    }

    function disableStartSection() {
        $('#sync-start-section').hide();
        $('#activecampaign-run-historical-sync').addClass('disabled');
    }

    function updateStatus() {
        var data = {
            'action': 'activecampaign_for_woocommerce_check_historical_sync_status'
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data
        }).done(response => {
            if (response.data <= 0) {
                if (!scheduled) {
                    hideRunSection();
                    enableStartSection();
                    cancelUpdateCheck();
                }
                if (wasRunning && wasStopped) {
                    $('#activecampaign-run-historical-sync-status').html('Historical sync was stopped.' ).show();
                }else if(wasRunning) {
                    $('#activecampaign-run-historical-sync-status').html('Historical sync finished.').show();
                }
            } else if(response.data === 1){
                scheduled = true;
            } else if(response.data.is_paused && !scheduled){
                scheduled = false;

                cancelUpdateCheck();
                enableStartSection();
                showRunSection();
                disableStopButtons();

                $('#activecampaign-run-historical-sync-running-status').html('Paused on record:' + response.data.current_record + '/' + response.data.total_orders).show();
                $('#activecampaign-run-historical-sync-status-progress').css('width', response.data.percentage + '%');
            } else {
                scheduled = false;
                wasRunning = true;

                if (!wasStopped) {
                    showRunSection();
                    disableStartSection();
                    enableStopButtons();
                    $('#activecampaign-run-historical-sync-running-status').html('Processing record:' + response.data.current_record + '/' + response.data.total_orders).show();
                    $('#activecampaign-run-historical-sync-status-progress').css('width', response.data.percentage + '%');
                }
            }
        }).fail(response => {
            $('#activecampaign-run-historical-sync-status').html(response.responseJSON.data);
            cancelUpdateCheck();
        });
    }
});

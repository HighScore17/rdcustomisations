<?php
$order = wc_get_order( get_the_ID() );
$order_key = $order->get_meta("shipstation_order_key");
$parentOrderId = $order->get_meta("shipstation_parent_order_id");
?>

<button class="ss-button" id="ss-sync-order-loading" style="display: none">Creating...</button>
<button class="ss-button <?php echo $parentOrderId ? "success" : "" ?>" id="ss-sync-order" <?php echo $parentOrderId ? "disabled" : "" ?>>
  <img src="https://www.shipstation.com/img/shipstation-logo-white.png" class="ss-logo"/>
  <br/>
  <?php if( !$parentOrderId ) : ?>
  Sync Order With ShipStation
  <?php else : ?>
  Order ID: <?php echo $parentOrderId ?> 
  <?php endif; ?>
</button>

<?php require_once __DIR__ . "/Label/OrderLabel.php" ?>

<script>


  (($) => {
    const needSync = <?php echo $parentOrderId ? "false" : "true" ?>;
    if( needSync ) {
      $('#ss-sync-order').click(syncSSOrder);
    }

    function syncSSOrder(e) {
      e.preventDefault();
      startLoading();
      const data = new FormData();
      data.append("action", "admin_sync_ss_order");
      data.append("order_id", "<?php echo get_the_ID(); ?>");
      $.ajax({
        type: "post",
        url: ajaxurl,
        data,
        cache: false,
        contentType: false,
        processData: false,
        success: function( result ) {
          if( result.success ) {
            window.location.reload();
          } else {
            alert( result.data[0].message );
          }
          console.log(result);
          endLoading();
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
          console.error({ XMLHttpRequest, textStatus, errorThrown });
          alert("Order not created");
          endLoading();
        },
      })
    }

    function startLoading() {
      jQuery('#ss-sync-order').css("display", "none");
      jQuery('#ss-sync-order-loading').css("display", "block");
    }

    function endLoading() {
      jQuery('#ss-sync-order').css("display", "block");
      jQuery('#ss-sync-order-loading').css("display", "none");
    }

  })(jQuery);
</script>

<style>
  .ss-button {
    background-color: rgba(41, 119, 104, 0.7);
    border: 1px solid rgba(41, 119, 104, 1 );
    color: #fff;
    width: 100%;
    padding: 10px 20px;
    border-radius: 3px;
    text-align: center;
    cursor: pointer;
  }
  .ss-button.success {
    background-color: rgba(41, 119, 104, 1);
    cursor: auto;
    
  }
  .ss-logo {
    width: 70%;
  }
</style>
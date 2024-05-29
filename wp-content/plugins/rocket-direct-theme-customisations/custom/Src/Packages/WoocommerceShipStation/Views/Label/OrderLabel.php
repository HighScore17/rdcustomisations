<?php
  $order = wc_get_order( get_the_ID() );
  $shipments = $order->get_meta( 'shipstation_shipments' );
?>

<button class="ss-button  <?php echo $shipments ? "success" : "" ?>" style="margin-top: 20px;" id="ss-create-label" <?php echo $shipments ? "disabled" : "" ?>>
  <img src="https://www.shipstation.com/img/shipstation-logo-white.png" class="ss-logo"/>
  <br/>
  <div id="ss-create-label-content">
    <?php echo $shipments ? "Labels Generated" : "Create Labels In ShipStation"; ?>
  </div>
</button>



<script>

  (($) => {
    class ShipStationLabel {

      constructor() {
        this.button = $('#ss-create-label');
        this.buttonContent = $('#ss-create-label-content');
        this.buttonContentHtml = this.buttonContent.html();
        this.handleCreateLabel = this.handleCreateLabel.bind(this);
        this.addListeners();
      }

      addListeners() {
        if( <?php echo $shipments ? "false" : "true"; ?> ) {
          this.button.click( this.handleCreateLabel );
        }
      }

      setLoading( loading ) {
        this.buttonContent.html( loading ? 'Creating...' : this.buttonContentHtml);
      }

      handleCreateLabel(e) {
        e.preventDefault();
        this.setLoading(true);
        const data = this.getLabelBody();
        $.ajax({
          ...this.getAjaxParams(data),
          success: function( result ) {
            if( result.success ) 
              return window.location.reload();
            else 
              alert( "Label cannot be created" );
            this.setLoading(false);
          }.bind(this),
          error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.error({ XMLHttpRequest, textStatus, errorThrown });
            alert("Label cannot be created");
            this.setLoading(false);
          }.bind(this),
        })
      }

      getLabelBody() {
        const data = new FormData();
        data.append("action", "admin_shipstation_create_label");
        data.append("order_id", "<?php echo get_the_ID(); ?>");
        return data;
      }

      getAjaxParams( data ) {
        return {
          data,
          type: "post",
          url: ajaxurl,
          cache: false,
          contentType: false,
          processData: false,
        }
      }


    }

    $(document).ready(() => {
      const label = new ShipStationLabel();
    });
  })(jQuery);


</script>
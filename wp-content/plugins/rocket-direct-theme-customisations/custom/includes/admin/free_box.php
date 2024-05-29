<?php
  class Admin_Free_Box {
    function get_tab()
    {
      return array(
        'name' => 'Free Box',
        'group' => 'free-box',
        'type' => array (
          'code' => 'custom_page',
          'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/free_box.php"
        )
      );
    }
  }


add_action('rest_api_init', function() {
  register_rest_route('horizon/v1', 'free-box/email', array(
    'methods' => ['POST', 'GET'],
    'callback' => function(WP_REST_Request $request) {
      $orders = $request->get_param("orders");
      $ids = explode(",", $orders);

      foreach( $ids as $id ) {
        $order = wc_get_order( intval($id) );

        if( !$order ) {
          continue;
        }

        $context = array(
          "first_name" => $order->get_shipping_first_name(),
          "fullname" => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
          "address" => $order->get_shipping_address_1(),
          "city" => $order->get_shipping_city(),
          "zip" => $order->get_shipping_postcode(),
          "state" => $order->get_shipping_state(),
          "country" => $order->get_shipping_country(),
        );
        postmark_send_email("rd-dentist-request-sample", $context, "customerservice@rocket.direct", $order->get_billing_email());
      }
      return [
        "success" => true,
      ];
    },
    'permission_callback' => '__is_admin'
  ));
});  
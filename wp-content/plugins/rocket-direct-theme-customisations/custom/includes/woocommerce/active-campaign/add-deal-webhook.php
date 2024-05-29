<?php  

add_action('rest_api_init', function() {
  register_rest_route('horizon/v1', 'customer/first-buy', array(
    'methods' => ['POST', 'GET'],
    'callback' => function(WP_REST_Request $request ) {
      $params = $request->get_params();

      if( !is_array( $params["deal"] ) ) {
        return new WP_Error( 'invalid_request', 'Invalid request', array( 'status' => 400 ) );
      }
      do_action( 'horizon_ac_new_deal_added', $params["deal"], $request );
    },
    'permission_callback' => '__return_true'
  ));
});
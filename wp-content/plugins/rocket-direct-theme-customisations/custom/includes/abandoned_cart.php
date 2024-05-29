<?php


function clear_ac_session_on_update_prices()
{
  if( class_exists( 'Wcal_Delete_Handler' ) )
  {
    $class = new Wcal_Delete_Handler();
    $class->wcal_bulk_action_delete_registered_carts_handler();
    wp_send_json_success( 'Session unset' );
  }else{
    wp_send_json_error('Abandoned Cart Plugin not exists');
  }
  die();
}
add_action('wp_ajax_clear_ac_session_on_update_prices', 'clear_ac_session_on_update_prices');
add_action('wp_ajax_nopriv_clear_ac_session_on_update_prices', 'clear_ac_session_on_update_prices');

?>
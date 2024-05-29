<?php
class Horizon_Customer {

  public static $instance = null;

  public static function instantiate() {
    if( !self::$instance instanceof Horizon_Customer ) {
      self::$instance = new Horizon_Customer();
      self::$instance->run();
    }
  }

  private function run() {
    add_action( 'account_activation', [ $this, 'on_activate_account'] , 10, 1 );
  }

  public function on_activate_account( $user_id ) {
    $user = get_userdata( $user_id );
    $firstname = get_user_meta( $user_id, 'first_name', TRUE );
    $lastname = get_user_meta( $user_id, 'last_name', TRUE );
    $ac_list_new_users = 7;

    // Add contact to Active campaign
    Active_Campaign_SDK::sync_user( $user->user_email, $firstname, $lastname, $ac_list_new_users );
  }
}

Horizon_Customer::instantiate();
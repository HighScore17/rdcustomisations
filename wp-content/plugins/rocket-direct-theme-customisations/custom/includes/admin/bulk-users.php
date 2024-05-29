<?php

use function AcVendor\DI\object;

  class Admin_Bulk_User {
    static $MAX_FIELDS = 18;
    static function get_tab()
    {
      return array(
        'name' => 'Bulk Users',
        'group' => 'bulk-users',
        'type' => array (
          'code' => 'custom_page',
          'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/users.php"
        )
      );
    }

    static function create_user( $email ) {
      $password = wp_generate_password();
      $user = wp_create_user( $email, $password, $email );
      if( is_wp_error( $user ) ) {
        return $user;
      }
      return (object) array(
        "id" => $user,
        "password" => $password
      );
    }
  }


add_action( 'wp_ajax_admin_create_users_by_batch', function() {

  if( !__is_admin() ) {
    wp_send_json_error(new WP_Error('users_batch', 'Permission denied'));
  }

  if( !isset( $_FILES['users'] ) ) {
    wp_send_json_error( new WP_Error('users_batch', 'File not found') );
    wp_die();
  }

  $users_csv = fopen( $_FILES["users"]["tmp_name"], "r" );

  if( $users_csv === false ) {
    wp_send_json_error( new WP_Error('users_batch', 'Can\'t read the file') );
  }

  $_POST['disable_new_user_notifications'] = "on";
  $users = array();

  // Remove header
  fgetcsv( $users_csv, 1000 );

  while( ( $data = fgetcsv( $users_csv, 300, "," ) ) !== FALSE) {
    
    if( count($data) < Admin_Bulk_User::$MAX_FIELDS ) {
      continue;
    }

    $user_data = user_arr_to_object( $data);
    $user_info = $user_data->billing;

    if( empty($user_info->email) || !filter_var( $user_info->email, FILTER_VALIDATE_EMAIL ) ) {
      continue;
    }

    

    $user = Admin_Bulk_User::create_user( $user_info->email );
    if( is_wp_error( $user ) ) {
      continue;
    }

    update_user_meta($user->id, 'active_email_account', "1");
    wp_update_user(array(
      "ID" => $user->id,
      "first_name" => $user_info->fname,
      "last_name" => $user_info->lname,
    ));

    if( 
      !empty( $user_data->shipping->fname )  &&
      !empty( $user_data->shipping->lname )  &&
      !empty( $user_data->shipping->address1 )  &&
      !empty( $user_data->shipping->city )  &&
      !empty( $user_data->shipping->state )  &&
      !empty( $user_data->shipping->zip )  &&
      !empty( $user_data->shipping->phone )  &&
      !empty( $user_data->shipping->email ) 
    )
      wcma_create_shipping_address( $user->id, "Main Address", array(
        "firstName" => $user_data->shipping->fname,
        "lastName" => $user_data->shipping->lname,
        "country" => "US",
        "address1" => $user_data->shipping->address1,
        "address2" => $user_data->shipping->address2,
        "city" => $user_data->shipping->city,
        "state" => $user_data->shipping->state,
        "postcode" => $user_data->shipping->zip,
        "phone" => $user_data->shipping->phone,
        "email" => $user_data->shipping->email,
      ), "residential", true );

    $users[] = array(
      "email" => $user_info->email,
      "password" => $user->password,
    );

    $b2b_group = get_page_by_title( trim($user_data->group) , OBJECT, 'b2bking_group' );

    if( !is_a(  $b2b_group, 'WP_Post' ) ) {
      continue;
    }

    $_POST['company'] = "test";
    if( $b2b_group->ID ) {
      update_user_meta( $user->id, 'b2bking_customergroup', $b2b_group->ID);
      update_user_meta( $user->id, 'b2bking_b2buser', 'yes');
    }
  }
  wp_send_json_success( $users );
  wp_die();
  return;
});


function user_arr_to_object( $arr ) {
  $billing_name = explode(" ", $arr[3]);
  $shipping_name = explode(" ", $arr[11]);
  $billing = array(
    "email" => sanitize_text_field($arr[2]),
    "fname" => sanitize_text_field($billing_name[0]),
    "lname" => sanitize_text_field($billing_name[1]),
    "address1" => sanitize_text_field($arr[4]),
    "address2" => sanitize_text_field($arr[5]),
    "city" => sanitize_text_field($arr[6]),
    "state" => sanitize_text_field($arr[7]),
    "zip" => sanitize_text_field($arr[8]),
    "phone" => preg_replace("/[^0-9]/", "",  sanitize_text_field($arr[9])),
  );

  if( strtolower( $arr[0] ) === "yes" ) {
    $shipping = $billing;
  } else {
    $shipping = array(
      "email" => sanitize_text_field($arr[10]),
      "fname" => sanitize_text_field($shipping_name[0]),
      "lname" => sanitize_text_field($shipping_name[1]),
      "address1" => sanitize_text_field($arr[12]),
      "address2" => sanitize_text_field($arr[13]),
      "city" => sanitize_text_field($arr[14]),
      "state" => sanitize_text_field($arr[15]),
      "zip" => sanitize_text_field($arr[16]),
      "phone" => preg_replace("/[^0-9]/", "",  sanitize_text_field($arr[17])),
    );
  }

  return (object) array(
    "group" => sanitize_text_field($arr[1]),
    "billing" => (object) $billing,
    "shipping" => (object) $shipping
  );
}


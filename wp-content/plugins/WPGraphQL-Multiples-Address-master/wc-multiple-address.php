<?php
    /**
    * Plugin Name: Woocommerce Multiples Address.
    * Description: Save Multiples Shipping & Billing Address.
    * Author:  Jesus Zacarias Cervantes Garcia
    * Author URI: https://github.com/CervarlCG
    * Version: 2.1.1
    */
    
if( !defined( 'ABSPATH' ))
    exit;
if( 
  in_array( 'woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')) ) 
  && !class_exists( 'WC_Customer_Multiple_Address' ) 
) {
  define( 'WC_MULTIPLE_ADDRESS_VERSION', '1.1.0' );
  require_once 'setup.php';
  require_once 'actions.php';
  require_once __DIR__ . "/graphql/autoload.php";

  /**
   * Main actions
   */
  register_activation_hook( __FILE__, 'wma_init_database' );

  class WC_Customer_Multiple_Address {
    static function get_table_name() {
      global $wpdb;
      return $wpdb->prefix . "wc_multiples_address";
    }

    static function create_address( $address, $user_id ) {
      global $wpdb;
      $table_name = self::get_table_name();

      $toInsert = self::copy_and_sanitize_address_data($address);
      $toInsert["user_id"] = $user_id;

      $addresses = self::get_addresses( $address["type"], $user_id );

      if( count( $address ) >= 20 ) {
        return new WP_Error('wcma-limit', 'You can only have 20 address saved.');
      }
      $address = wcma_array_find( $addresses["nodes"], function( $address ) use( $toInsert ) {
        return WC_Customer_Multiple_Address::are_addresses_equal( $address, $toInsert );
      }  );

      if( $address !== null ) {
        return new WP_Error('wcma-exists', 'The current address alredy exists');
      }

      $inserted = $wpdb->insert( $table_name, $toInsert);
      return $inserted ? [ "id" => $wpdb->insert_id] : NULL;
    }

    static function update_address( $address, $user_id ) {
      global $wpdb;
      $table_name = self::get_table_name();
      $toUpdate = self::copy_and_sanitize_address_data($address, ["user_id"]);
      $updated = $wpdb->update(
        $table_name, 
        $toUpdate, 
        ["id" => intval($address["id"]), "user_id" => intval( $user_id )
      ]);
      return ["updated" => $updated ? 1 : 0];
    }

    static function delete_address( $address_id, $user_id ) {
      global $wpdb;
      $table_name = self::get_table_name();
      $deleted = $wpdb->delete($table_name, ["id" => $address_id, "user_id" => $user_id]);
      return ["deleted" => $deleted ? 1 : 0];
    }

    static function set_primary( $address_id, $user_id, $type = "shipping" ) {
      global $wpdb;
      $table_name = self::get_table_name();
      $updated = $wpdb->update(
          $table_name, 
          ["isPrimary" => 0], ["user_id" => $user_id, "type" => $type]);
      $updated = $wpdb->update(
          $table_name, 
          ["isPrimary" => 1], ["id" => $address_id, "user_id" => $user_id, "type" => $type]);
      return ["isPrimary" => $updated];
    }

    static function get_primary_address( $type, $user_id ) {
      global $wpdb;
      $table_name = self::get_table_name();
      return $wpdb->get_row("SELECT * FROM $table_name where user_id=$user_id and type='$type' and isPrimary=1");
    }

    static function get_addresses( $type, $user_id ) {
      global $wpdb;
      $table_name = self::get_table_name();
      $result =  $wpdb->get_results( "SELECT * FROM $table_name where user_id=$user_id and type=\"$type\"", ARRAY_A);
      return [
          'nodes' => $result
      ];
    }

    static function copy_and_sanitize_address_data( $origin, $exclude = [], $includeID = FALSE ) {
      $data = ["alias", "type", "address_type", "firstName", "lastName", "company", "country", "address1", "address2", "city", "state", "postcode", "phone", "email", "user_id"];
      $newData = [];
      
      if($includeID) {
        array_push($data, "id");
      }

      foreach($origin as $key => $value)
      {
        if(in_array($key, $data) && !in_array($key, $exclude)) {
          $newData[$key] = sanitize_text_field( $value );
        }
      }
      return $newData;
    }

    static function are_addresses_equal( $address1, $address2 ) {
      $toCompare = [ "address_type", "firstName", "lastName", "country", "address1", "address2", "city", "state", "postcode", "phone", "email" ];
      foreach( $toCompare as $key ) {
        if( !self::compare_values_equality( $address1[$key], $address2[$key] ) ) {
          return false;
        }
      }
      return true;
    }

    static function compare_values_equality( $val1, $val2 ) {
      return trim( strtolower( $val1 ) ) === trim( strtolower( $val2 ) );
    }
  }

    function wcma_array_find(array $arr, callable $callback) {
      foreach($arr as $item) {
        if($callback($item)) {
          return $item;
        }
      }
      return null;
    }
}
?>
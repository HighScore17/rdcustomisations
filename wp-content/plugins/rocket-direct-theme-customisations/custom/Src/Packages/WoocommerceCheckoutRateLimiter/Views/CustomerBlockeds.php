<?php

if(!class_exists('WP_List_Table')){
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


if( 
  isset( $_GET["action"], $_GET["ip"] ) &&
  $_GET["action"] === "delete" &&
  wp_verify_nonce( $_GET["_wpnonce"], "delete-checkout-blocked-ip" )
) {
  $limitier = get_woocommerce_checkout_rate_limitier();
  $limitier->deleteRate( $_GET["ip"] );
  wp_redirect( '/wp-admin/admin.php?page=checkout_rate_limitier' );
}


class WC_Checkout_Rate_Attemps_Table extends WP_List_Table {
  function __construct() {
    parent::__construct( array(
    'singular'=> 'wp_list_text_link', //Singular label
    'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
    'ajax'   => false //We won't support Ajax for this table
    ) );
  }

  /**
   * Add extra markup in the toolbars before or after the list
   * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
   */
  function extra_tablenav( $which ) {
    if ( $which == "top" ){
      //The code that goes before the table is here
      echo '<h1>Blocked Customers</h1>';
    }
    if ( $which == "bottom" ){
      //The code that goes after the table is there
    }
  }

  /**
 * Define the columns that are going to be used in the table
 * @return array $columns, the array of columns to use with the table
 */
  function get_columns() {
    return array(
      'col_ip'=>__('IP'),
      'col_remaining_attemps'=>__('Remaining Attempts'),
    );
  }

  /**
   * Decide which columns to activate the sorting functionality on
   * @return array $sortable, the array of columns that can be sorted by the user
   */
  public function get_sortable_columns() {
    return array(
      'col_remaining_attemps'=>'remaining_attemps',
    );
  }

  /**
   * Prepare the table with different parameters, pagination, columns and table elements
   */
  function prepare_items() {
    global $wpdb, $_wp_column_headers;
    $screen = get_current_screen();
    $limitier = get_woocommerce_checkout_rate_limitier();
    $attemps = $limitier->getAllAttemps();
    
    $results = [];
    foreach ( $attemps as $attemp ) {
      $results[] = array(
        "col_ip" => $attemp["ip"],
        "col_remaining_attemps" => $attemp["attemps"]      
      );
    }

      $columns = $this->get_columns();
      $_wp_column_headers[$screen->id] = $columns;
      $this->_column_headers = [
        $this->get_columns(),
        [], // hidden columns
        $this->get_sortable_columns(),
        $this->get_primary_column_name(),
    ];
    /* -- Fetch the items -- */
      $this->items = $results;
  }




/**
 * Display the rows of records in the table
 * @return string, echo the markup of the rows
 */
function display_rows() {

  //Get the records registered in the prepare_items method
  $records = $this->items;

  //Get the columns registered in the get_columns and get_sortable_columns methods
  list( $columns, $hidden ) = $this->get_column_info();

  //Loop for each record
  if(!empty($records)){foreach($records as $rec){
     //Open the line
       echo '<tr id="record_'.$rec["col_ip"].'">';
       echo "<td>" . $this->print_ip_column( $rec["col_ip"] ) . "</td>";
       echo "<td>" . (!is_array($rec["col_remaining_attemps"]  ) ? $rec["col_remaining_attemps"] : "")  . "</td>";

     //Close the line
     echo'</tr>';
  }}
  }

  function print_ip_column( $ip ) {
    $nonce = wp_create_nonce("delete-checkout-blocked-ip");
    $actions = array(
      'delete'    => sprintf('<a href="?page=%s&action=%s&ip=%s&_wpnonce=%s">Delete</a>',$_REQUEST['page'],'delete',$ip, $nonce),
    );
    return sprintf('%1$s %2$s', $ip, $this->row_actions($actions) );
  }

}

$table = new WC_Checkout_Rate_Attemps_Table();
$table->prepare_items();
$table->display();
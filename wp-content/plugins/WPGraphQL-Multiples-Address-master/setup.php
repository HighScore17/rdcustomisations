<?php
  define( 'WC_MULTIPLE_ADDRESS_TABLE_NAME', 'wc_multiples_address' );

    function wma_get_table_name() {
      global $wpdb;
      return $wpdb->prefix . WC_MULTIPLE_ADDRESS_TABLE_NAME;
    }
    function wma_init_database()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . WC_MULTIPLE_ADDRESS_TABLE_NAME;
        $table_users = $wpdb->prefix . "users";
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE if not exists $table_name (
                id int not null auto_increment primary key,
                alias varchar(50) not null,
                type ENUM('shipping', 'billing') not null,
                address_type ENUM('residential', 'commercial') not null,
                firstName varchar(50) not null,
                lastName varchar(50) not null,
                company varchar(100) null,
                country varchar(2) not null,
                address1 varchar(100) not null,
                address2 varchar(20),
                city varchar(50) not null,
                state varchar(2) not null,
                postcode varchar(5) not null,
                phone varchar(15) not null,
                email varchar(70) not null,
                isPrimary int(1) not null,
                user_id bigint(20) unsigned not null,
                constraint fk_address_users foreign key (user_id) references $table_users (ID) on update cascade on delete cascade
            )$charset_collate;";
        //require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        //dbDelta( $sql );
        $wpdb->query($sql);
        $table_columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name;", ARRAY_A);
        do_action('wma_table_columns', $table_columns);
    }


    /*
    * Add address type column
    * @since 1.1.0
    */
    function wma_add_address_type_column( $columns )
    {
      if(!wma_search_column($columns, 'address_type'))
        wma_add_new_column("address_type ENUM('commercial', 'residential') not null");
    }
    add_action( 'wma_table_columns', 'wma_add_address_type_column', 10, 1 );

    function wma_add_new_column( $details )
    {
      global $wpdb;
      $table_name = wma_get_table_name();
      $sql = "ALTER TABLE $table_name ADD $details;";
      $wpdb->query($sql);
    }

    function wma_search_column( $result, $column ) {
      return array_search( $column, array_column( $result, "Field" ) ) === false ? false : true;
    }
?>

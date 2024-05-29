<?php

function ac_free_sample_deals_create_tables() {
  global $wpdb;
  $table_name = $wpdb->prefix . "ac_free_sample_deal";
  $charset_collate = $wpdb->get_charset_collate();
  $sql = "CREATE TABLE if not exists $table_name (
    id int not null auto_increment primary key,
    deal_id int not null,
    stage_id int not null,
    pipeline_id int not null,
    contact_id int,
    deal_title varchar(50),
    stage_title varchar(50),
    value_raw int,
    email varchar(100) not null,
    firstname varchar(50),
    lastname varchar(50),
    tracking_number varchar(30)
  )$charset_collate;";
  $wpdb->query($sql);
}
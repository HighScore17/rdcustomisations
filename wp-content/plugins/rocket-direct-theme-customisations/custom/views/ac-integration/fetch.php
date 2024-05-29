<?php
$ac = new Active_Campaign_SDK();
$ac_cfields = [];
$ac_cfields = wp_remote_retrieve_body($ac->call("fields?limit=100", null, "GET"));

if( !$ac_cfields ) {
  return;
}

$ac_cfields = json_decode( $ac_cfields, true );

if(!is_array( $ac_cfields["fields"] ) ) {
  return;
}
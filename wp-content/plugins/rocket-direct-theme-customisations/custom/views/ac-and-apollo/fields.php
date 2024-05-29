<?php
$apollo = new Apollo_SDK();
$ac = new Active_Campaign_SDK();
  
$cfields = json_decode( $apollo->make_request( "contact_stages" ), true );

if( !$cfields || is_wp_error( $cfields ) || !is_array( $cfields["contact_stages"] ) ) {
  return;
}
$cfields = $cfields["contact_stages"];


$ac_cfields = [];
$ac_cfields = wp_remote_retrieve_body($ac->call("fields?limit=100", null, "GET"));

if( !$ac_cfields ) {
  return;
}

$ac_cfields = json_decode( $ac_cfields, true );

if(!is_array( $ac_cfields["fields"] ) ) {
  return;
}
<?php

function wc_horizon_get_address_keys( $type = 'billing' ) {
  return [
    $type .'_first_name',
    $type .'_last_name',
    $type .'_company',
    $type .'_address_1',
    $type .'_address_2',
    $type .'_city',
    $type .'_state',
    $type .'_postcode',
    $type .'_country',
    $type .'_phone',
    $type .'_email',
  ];
}
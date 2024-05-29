<?php

class FLT_Carrier extends Shipping_Carrier {
  function do_api_request( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    return array(
      "accessorials" => array(),
      "residential_address" => $ship_to['Address']['ResidentialAddress'],
      "rates" => array(
        array(
          'total' => '0.0',
          'service' => array(
            'code' => 'flt',
            'description' => 'FLT Cost not included',
          ),
          'estimated_delivery' =>  Date('Y-m-d', strtotime('+35 days'))
        )
      )
    );
  }
}
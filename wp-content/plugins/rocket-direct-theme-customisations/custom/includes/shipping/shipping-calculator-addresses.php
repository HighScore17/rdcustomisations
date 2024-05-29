<?php 

class Shipping_Calculator_Addresses {

  static function get() {
    $addresses = new Shipping_Calculator_Addresses();
    return array(
      'from' => $addresses->get_from_address(),
      'to' => $addresses->get_to_address()
    );
  }

  function get_from_address() {
    $options = get_option( 'shipping_calculator' );
    return array(
      'Name' => $options['shipper_name'],
      'Address' => array(
        'AddressLine' => $options['shipper_address'],
        'City' => $options['shipper_city'],
        'PostalCode' => $options['shipper_postcode'],
        'StateProvinceCode' => $options['shipper_state'],
      )
    );
  }

  function get_to_address() {
    return $_POST["shipTo"];
  }
}
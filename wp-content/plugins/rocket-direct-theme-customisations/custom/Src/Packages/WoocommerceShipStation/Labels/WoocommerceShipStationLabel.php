<?php
require_once __DIR__ . "/WoocommerceShipStationLabelAdmin.php";
class Woocommerce_Shipstation_Label {
  static function createLabelFromOrder( $orderId ) {
    $order = __get_wc_order( $orderId );
    $body = self::getLabelBody( $order );
    $trackings = [];
    $shipments = [];

    if( is_wp_error( $body ) )
      return $body;

    foreach( $body as $labelBody ) {
      $label = self::generateLabel( $labelBody );
      if ( is_wp_error( $label ) ) {
        do_action( 'shipstation_label_error', $label, $orderId );
        continue;
      }

      $shipments[] = $label;
      $trackings[] = $label["trackingNumber"];
    }

    if( count( $trackings ) ) {
      $order->update_meta_data( 'shipment_tracking_number', implode( ",", $trackings ) );
      $order->update_meta_data( 'shipment_provider', 'FedEx' );
      $order->update_meta_data( 'shipment_date', date('Y-m-d', strtotime(' +5 weekdays')) );
    }

    $order->update_meta_data( 'shipstation_shipments', $shipments );
    $order->save();

    do_action( 'shipstation_labels_created', $shipments, $orderId );

    return true;
  }

  /**
   * Make a HTTP request to the SS API to create a label
   * @param Array $label Label body requiered by the API, see https://www.shipstation.com/docs/api/orders/create-label/
   * @return WP_Error|Array WP_Error if the request failed or the array json if success
   */
  static function generateLabel( $label ) {
    $response = Woocommerce_ShipStation_SDK::request( 'orders/createlabelfororder', "POST", $label );
    $code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    if( ($code !== 200 && $code !== 201) || empty( $body ) ) {
      self::log( $response );
      return new WP_Error('ss-label-error', "ShipStation label request: " . $code . " " . $body);
    }
    
    return json_decode( $body, true );
  }

  /**
   * Generate the labels body for a HTTP API Request
   * @param WC_Order|Int $orderId The order
   * @param String $ssOrderId Order ID in shipstation
   * @return WP_Error|Array A WP_Error if the service code can't be found in the order or the body array if success
   */
  static function getLabelBody( $orderId ) {
    $order = __get_wc_order( $orderId );
    $parentOrder = $order->get_meta("shipstation_parent_order_id");
    $childOrders = $order->get_meta("shipstation_child_orders_ids");

    if( !$parentOrder )
      return new WP_Error( 'ss-order', 'Parent order cannot be found on ShipStation' );
    
    if( !is_array( $childOrders ) )
      $childOrders = [];

    $ssOrdersIds = array_merge( array( $parentOrder), $childOrders );
    $serviceCode = self::getServiceCodeFromOrder( $order );

    if( !$serviceCode )       
      return new WP_Error( 'ss-order', 'Shipping service code cannot be found for this order.' );

    return array_map( function( $ssOrderId ) use ($serviceCode ) {
      return array(
        "orderId" => $ssOrderId,
        "carrierCode" => Woocommerce_ShipStation_SDK::getCarrierCode(),
        "serviceCode" => $serviceCode,
        "packageCode" => "package",
        "confirmation" => "delivery",
        "shipDate" => date('Y-m-d', strtotime(' +1 weekdays')),
        /*"weight" => array(
          "value" => $package->get_weight(),
          "units" => "pounds"
        ),
        "dimensions" => array_merge(
          $package->get_dimensions(),
          array(
            "units" => "inches"
          ),
        ),*/
      );
    }, $ssOrdersIds );
  }

  /**
   * Get the selected service of an order based on its shipping method, if the selected shipping method isn't valid
   * or doesn't exists then the service with the most lower price is returned
   * 
   * @param WC_Order|Int $order The order object or id
   * @return String Valid Shipstation service code
   */
  static function getServiceCodeFromOrder( $order ) {
    try {
      $order = __get_wc_order( $order );
      $rates = self::calculateRates( $order );
      $rates = $rates["rates"];
      $shipping_methods = $order->get_shipping_methods();

      if( is_array( $shipping_methods ) && count( $shipping_methods ) ) {
        $shipping_methods = array_values( $shipping_methods );
        $shipping_method = $shipping_methods[0]->get_method_id();
      }

      $rate = $shipping_method ? __array_find( $rates, function($rate ) use ($shipping_method) {
        return $rate["service"]["code"] === $shipping_method;
      }) : null;

      $rate = !$rate ?  array_reduce( $rates, function( $acc, $current ) {
        if( !$acc ) return $current;
        if( floatval( $acc["total"] ) > floatval( $current["total"] ) ) return $current;
        return $acc;
      }, null ) : $rate;
      return $rate["service"]["code"];
    } catch( Exception $e ) {
      self::log($e);
      return null;
    }
  }

  /**
   * Calculated shipping rates from the order line items
   * @param WC_Order|Int The order
   * @return Array The rates
   */
  static function calculateRates( $order ) {
    $order = __get_wc_order( $order );

    $_POST["shipTo"] = array(
      "Address" => array(
        'City' => $order->get_shipping_city(),
        'PostalCode' => $order->get_shipping_postcode(),
        'StateProvinceCode' => $order->get_shipping_state(),
      )
    );
    $calculator = new Shipping_Calculator_V2();
    $cart = self::orderItemsToCart( $order );
    return $calculator->get_rates( $cart );
  }

  /**
   * Get a cart based on the order line items
   * @param WC_Order|Int the order
   * @return Woocommerce_Packages_Cart A cart
   */
  static function orderItemsToCart( $order ) {
    $order = __get_wc_order( $order );
    $cart = new Woocommerce_Packages_Cart();

    foreach( $order->get_items() as $item ) {
      $cart->add_item( 
        $item->get_product_id(),
        $item->get_variation_id(),
        $item->get_quantity(),
        $item->get_total()
      );
    }

    return $cart;
  }

  static function log( $msg ) {
    $logger = wc_get_logger();
    $logger->info( wc_print_r( $msg, true ), array(
      "source" => "shipstation-labels"
    ) );
  }
}
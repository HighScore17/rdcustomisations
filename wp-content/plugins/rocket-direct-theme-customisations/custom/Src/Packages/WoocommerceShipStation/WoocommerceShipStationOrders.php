<?php


class Woocommerce_ShipStation_Orders_Integration {
  const AWAITING_PAYMENT_STATUS = "awaiting_payment";
  const AWAITING_SHIPMENT_STATUS = "awaiting_shipment";

  static function sync_order( $order_id, $status = self::AWAITING_PAYMENT_STATUS ) {
    $order = __get_wc_order( $order_id );
    $packages = self::get_shipments( $order );

    if( !count( $packages ) ) 
      return new WP_Error("packages-empty", 'Packages not found');
    
    $parentPackage = $packages[0];
    $childPackages = array_slice( $packages, 1 );
    $childOrdersIds = [];
    $parentOrder = self::create_parent_order( $order_id, $status, $parentPackage );

    if( !$parentOrder || !is_array( $parentOrder ) || !$parentOrder["orderId"] )
      return new WP_Error('ss-parent-order', 'Parent order cannot be created');

    if( count( $childPackages ) ) {
      $childOrders = self::create_childs_orders( $order_id, $status, $parentOrder["orderId"], $childPackages ); 

      if( !$childOrders || !is_array( $childOrders ) || !$childOrders["results"]  ) 
        return new WP_Error('ss-child-orders', 'Children orders cannot be created');
      
      foreach( $childOrders["results"] as $childOrder ) {
        if( $childOrder["orderId"] ) {
          $childOrdersIds[] = $childOrder["orderId"];
        }
      }
    }

    $order->update_meta_data( "shipstation_parent_order_id", $parentOrder["orderId"] );
    $order->update_meta_data("shipstation_child_orders_ids", $childOrdersIds);
    $order->update_meta_data( "cart_packages_built", $packages );
    $order->save();
    
    return true;
  }

  static function create_parent_order( $order, $status, $package ) {
    $dimensions = $package->get_dimensions();
    $ss_order = Woocommerce_ShipStation_SDK::request("orders/createorder", "POST", Woocommerce_ShipStation_Orders_Integration::get_order_body($order, $status, array(
      "items" => self::package_to_item( $package ),
      "width" => $dimensions["width"],
      "height" => $dimensions["height"],
      "length" => $dimensions["length"],
      "weight" => $package->get_weight()
    )));
    self::log($ss_order);

    $body = wp_remote_retrieve_body( $ss_order );

    if( !$body ) {
      self::log($ss_order);
      return null;
    }
    
    return json_decode( $body, true );
  }

  static function create_childs_orders( $order, $status, $parentOrder, $childPackages ) {
    $childsBody = array();
    $idx = 1;

    foreach( $childPackages as $childPackage ) {
      $dimensions = $childPackage->get_dimensions();
      $childsBody[] = self::get_order_body( $order, $status, array(
        "items" => self::package_to_item( $childPackage ),
        "parentId" => $parentOrder,
        "width" => $dimensions["width"],
        "height" => $dimensions["height"],
        "length" => $dimensions["length"],
        "weight" => $childPackage->get_weight()
      ));
      $idx++;
    }
    
    $ss_orders = Woocommerce_ShipStation_SDK::request("orders/createorders", "POST", $childsBody);
    $body = wp_remote_retrieve_body( $ss_orders );
    self::log($ss_orders);
    
    if( !$body ) {
      self::log( $ss_orders );
      return null;
    }

    return json_decode( $body, true );
  }

  static function get_shipments( $orderId ) {
    $packages = Woocommerce_Package_Builder::buildFromCart( Woocommerce_Shipstation_Label::orderItemsToCart( $orderId ) );
    return Woocommerce_Shipstation_Packages::build( $packages );
  }

  static function get_order_body( $order_id, $status, $args = array() ) {
    $order = __get_wc_order( $order_id );
    $name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
    
    $ss_order =  array (
      "orderNumber" => "rd-" . $order->get_id() . ( $args["postfix"] ?? "" ),
      "orderStatus" => $status,
      "orderDate" => date("c"),
      "customerUsername" => $name,
      "customerEmail" => $order->get_billing_email(),
      "amountPaid" => $order->get_total(),
      "billTo" => array(
        "name" => $name,
        "street1" => $order->get_billing_address_1(),
        "street2" => $order->get_billing_address_2(),
        "city" => $order->get_billing_city(),
        "state" => $order->get_billing_state(),
        "postalCode" => $order->get_billing_postcode(),
        "country" => $order->get_billing_country(),
        "phone" => $order->get_billing_phone(),
      ),
      "shipTo" => array(
        "name" => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
        "street1" => $order->get_shipping_address_1(),
        "street2" => $order->get_shipping_address_2(),
        "city" => $order->get_shipping_city(),
        "state" => $order->get_shipping_state(),
        "postalCode" => $order->get_shipping_postcode(),
        "country" => $order->get_shipping_country(),
        "phone" => $order->get_shipping_phone() ?? $order->get_billing_phone(),
        
      ),
      "weight" => array(
        "value" => $args["weight"],
        "units" => "pounds"
      ),
      "dimensions" => array(
        "length" => $args["length"],
        "width" => $args["width"],
        "height" => $args["height"],
        "units" => "inches"
      ),
      "items" => $args["items"] ?? $args["items"],
      "advancedOptions" => array(
        "parentId" => $args["parentId"] ?? null
      ),
    );
    return $ss_order;
  }

  static function package_to_item(ShipStation_Box_Package $package ) {
    $items = $package->getItems();
    return array_map( function($item) {
      $package = $item["package"];
      $product = wc_get_product($package->get_id());
      $name = $product ? $product->get_name() . " - " . strtoupper( $product->get_attribute("pa_presentation") ) . " " . strtoupper($product->get_attribute("pa_size")) : "Unknown product";
      return array(
        "sku" => $product ? $product->get_sku() : "",
        "name" => $name,
        "quantity" => $package->get_quantity(),
        "unitPrice" => floatval( $package->get_price() ) / (intval( $package->get_quantity() ) ?? 1),
      );
    }, $items );
  }

  static function log( $msg ) {
    $logger = wc_get_logger();
    $logger->info( wc_print_r( $msg, true ), array(
      "source" => "shipstation-order-sync"
    ) );
  }
}

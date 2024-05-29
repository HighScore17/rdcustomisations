<?php 

class ShipStation_Orders {
  const AWAITING_PAYMENT_STATUS = "awaiting_payment";
  const AWAITING_SHIPMENT_STATUS = "awaiting_shipment";

  static function create_order(WC_Order $order, $status = self::AWAITING_PAYMENT_STATUS) {
    $ss_order = ShipStation_SDK::request("orders/createorder", "POST", ShipStation_Orders::get_order_body($order, $status));
    if( !is_array($ss_order) || !key_exists("orderId", $ss_order) ) {
      return null;
    }
    return $ss_order;
  }

  static function update_order( $order, $order_id, $status = ShipStation_Orders::AWAITING_SHIPMENT_STATUS ) {
    $body = ShipStation_SDK::request("orders/" . $order_id, "GET");

    if(!$body["orderId"]) {
      return null;
    }

    $body["orderStatus"] = $status;

    $ss_order = ShipStation_SDK::request("orders/createorder", "POST", $body);

    if(!is_array($ss_order) || !key_exists("orderId", $ss_order)) {
      return null;
    }
    return $ss_order;
  }

  static function get_order_body( WC_Order $order, $status ) {
    $name = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
    
    $ss_order =  array (
      "orderNumber" => "rd-" . $order->get_id(),
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
        "phone" => $order->get_shipping_phone(),
      ),
      "items" => array(),
    );

    foreach($order->get_items() as $item) {
      $product = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item->get_product_id() );
      $presentation = strtoupper( $item->get_meta( "pa_presentation" ) ?? "" );
      $size = strtoupper( __get_product_size_label(  $item->get_meta( "pa_size" ) ?? "" ) );
      $ss_order["items"][] = array(
        "name" => $item->get_name() . " - {$presentation} {$size}",
        "quantity" => $item->get_quantity(),
        "unitPrice" => $item->get_total()/$item->get_quantity(),
        "sku" => $product ? $product->get_sku() : ""
      );
    }
    return $ss_order;
  }
}
<?php
class Horizon_Pickup_Orders {
  public static $instance = null;
  public $logger = null;

  static function init() {
    if( !self::$instance instanceof Horizon_Pickup_Orders ) {
      self::$instance = new Horizon_Pickup_Orders();
      self::$instance->add_hooks();
      self::$instance->logger = wc_get_logger();
    }
  }

  public function add_hooks() {
    add_action( 'cmb2_admin_init', [$this, 'register_pickup_metabox'] );
    add_action( 'save_post', [$this, 'on_update_pickup_information'], 10, 1 );
  }

  public function on_update_pickup_information( $post_id ) {
    $order = wc_get_order( $post_id );
    if( 
      !$order ||
      !isset( $_POST['order_pickup_packing_details'] ) || 
      empty( $_POST['order_pickup_packing_details'] )  ||
      $_POST['order_pickup_packing_details'] === $order->get_meta( 'order_pickup_packing_details' ) ||
      trim($order->get_shipping_method()) !== "Local pickup"
    ) {
      return;
    }
    wc_horizon_set_order_brand( $order );
    postmark_send_email(
      'pickup-confirmation',
      $this->get_pickup_email_body($order), 
      wc_horizon_get_email_sender("tracking"), 
      "{$order->get_billing_email()},abigail@horizon.com.pr"
    );
  }

  private function get_pickup_email_body(WC_Order $order) {
    $hours = $order->get_meta('order_pickup_ready_at');
    return array(
      'first_name' => $order->get_billing_first_name() . ' ' . $order->get_shipping_method(),
      'order_number' => "#{$order->get_order_number()}",
      'total' => '$'. number_format($order->get_total(), 2),
      'items' => get_order_products_purchased( $order ),
      "pickup" => array(
        "date" => date("M jS, Y", strtotime("+{$hours} hours")),
        "packing" => explode("\n", $_POST['order_pickup_packing_details'])
      )
    );
  }

  public function register_pickup_metabox() {
    $cmb = new_cmb2_box( array(
      'id' => 'order_pickup_metabox',
      'title' => 'Pick Up',
      'context' => 'side',
      'priority' => 'low',
      'object_types' => array( 'shop_order' )
    ) );
  
    $cmb->add_field( array(
      'name' => 'Ready for pick up',
      'id'   => 'order_pickup_ready_at',
      'type' => 'select',
      'options' => array(
        '36' => __('36 hours', 'cmb2'),
      )
    ));
    $cmb->add_field( array(
      'name' => 'Packing Details',
      'id'   => 'order_pickup_packing_details',
      'type' => 'textarea_small',
    ));
  }
}

Horizon_Pickup_Orders::init();
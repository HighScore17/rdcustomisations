<?php

class Horizon_Coupons {

  public static $instance = null;

  public static function instantiate() {
    if( !self::$instance instanceof Horizon_Coupons ) {
      self::$instance = new Horizon_Coupons();
      self::$instance->run();
    }
  }

  private function run() {
    add_action( 'woocommerce_coupon_options', [ $this, 'register_custom_fields' ] );
    add_action( 'woocommerce_coupon_options_save', [$this,  'register_save_options'] );
    add_filter( 'woocommerce_coupon_is_valid', [ $this, 'validate_coupon_options' ], 10, 3 );
    add_filter( 'woocommerce_coupon_error', [$this, 'change_coupon_messages'], 10, 3 );
    add_filter( 'woocommerce_coupon_data_tabs', [ $this, 'add_b2bking_tab' ] );
    add_action( 'woocommerce_coupon_data_panels', [ $this, 'render_b2bking_options' ], 10, 2 );
  }

  public function register_custom_fields( $user_id ) {
    $this->register_only_registered_users_option();
    $this->register_only_first_purchase_option();

  }

  public function register_save_options( $post_id ) {
    $this->save_only_registered_users_option($post_id);
    $this->save_only_first_purchase_option($post_id);
    $this->save_b2bking_options( $post_id );
  }

  public function validate_coupon_options( $true, $coupon, $instance ) {
    $this->validate_only_registered_users_option( $coupon );
    $this->validate_only_first_purchase_option( $coupon );
    $this->validate_b2bking_filter( $coupon );
    return true;
  }

  /**
   * Register the "Only first purchase" option
   */
 
  private function register_only_first_purchase_option() {
    woocommerce_wp_checkbox(
      array(
        'id' => 'wc_coupon_option_first_purchase',
        'label' => __( 'Only first purchase', 'woocommerce' ),
        'description' => __( 'If checked, the coupon will be valid only in the first purchase. Only registered users must be enabled', 'woocommerce' ),
      )
    );
  }

  private function save_only_first_purchase_option( $post_id ) {
      $only_first_purchase = $_POST['wc_coupon_option_first_purchase'] ? 'yes' : 'no';
      update_post_meta( $post_id, 'wc_coupon_option_first_purchase', $only_first_purchase );
  }

  private function validate_only_first_purchase_option( $coupon ) {
    $only_first_purchase = $coupon->get_meta( 'wc_coupon_option_first_purchase', true ) === "yes";
    $user = wp_get_current_user();
    $email = $user->user_email;
    $guest = $_POST['__guest_customer_email']? $_POST['__guest_customer_email'] : WC()->session->get('__guest_customer_email');
    if( !$user->exists() && !empty( $guest ) ) {
      $email = $guest;
      WC()->session->set('__guest_customer_email', $guest);
    }

    if( $only_first_purchase && ( !$email || __customer_has_boughts( $email ) ) ) {
      throw new Exception( __( "This coupon is only applicable for your first purchase.", "woocommerce" ), 100 );
    }
  }

  /**
   * Register the "Only registered users" option
   */

  private function register_only_registered_users_option() {
    woocommerce_wp_checkbox(
      array(
        'id' => 'wc_coupon_option_only_registered_users',
        'label' => __( 'Only registered users', 'woocommerce' ),
        'description' => __( 'If checked, the coupon will be valid only for registered users.', 'woocommerce' ),
      )
    );
  }

  private function save_only_registered_users_option( $post_id ) {
      $only_registered = $_POST['wc_coupon_option_only_registered_users'] ? 'yes' : 'no';
      update_post_meta( $post_id, 'wc_coupon_option_only_registered_users', $only_registered );
  }

  private function validate_only_registered_users_option( $coupon ) {
    $only_registered = $coupon->get_meta( 'wc_coupon_option_only_registered_users', true ) === "yes";
    $user_id = get_current_user_id();

    if( $only_registered && !$user_id ) {
      throw new Exception( 
        __( "You must be logged in to apply a cupon. Don´t have an account yet? <a href=\"/create-account\">Create account.</a>", 
        "woocommerce" ), 
      100 );
    }
  }

  public function change_coupon_messages($msg, $msg_code, $coupon) {
    if( $msg_code === WC_Coupon::E_WC_COUPON_ALREADY_APPLIED || $msg == "This coupon has already been applied to the cart" ) {
      $msg = "This cupon has already been applied.";
    }
    else if( $msg_code === WC_Coupon::E_WC_COUPON_EXPIRED ) {
      $msg = "This cupon has expired.";
    } else if( $msg_code === WC_Coupon::E_WC_COUPON_USAGE_LIMIT_COUPON_STUCK ) {
      $msg = "It looks like this coupon has been redeemed already. If you applied the coupon just now but your order was not completed, please contact us at <a href=\"mailto:support@amerisano.com\"/>support@amerisano.com</a> or via chatbox, and a representative will assist you to complete your order.";
    }
    return $msg;
  }

  public function add_b2bking_tab( $tabs = [] ) {
    $tabs['custom-b2bking'] = array(
      'label'  => __( 'B2BKing', 'woocommerce' ),
							'target' => 'custom-b2bking',
							'class'  => '',
    );
    return $tabs;
  }

  public function render_b2bking_options( $coupon_id, WC_Coupon $coupon ) {
   
    ?>
    <div id="custom-b2bking" class="panel woocommerce_options_panel">
      <div class="options_group">
        <?php 
        	woocommerce_wp_select(
            array(
              'id'      => 'custom_b2bking_filter_type',
              'label'   => __( 'Filter type', 'woocommerce' ),
              'options' => array(
                'black'       => __( 'Black List', 'woocommerce' ),
                'white'    => __( 'White List', 'woocommerce' ),
              ),
              'value'   => $coupon->get_meta("custom_b2bking_filter_type")
            )
          );
          woocommerce_wp_textarea_input(array(
            'id'                => 'custom_b2bking_block_list',
            'label'             => __( 'B2BKing groups to filter', 'woocommerce' ),
            'placeholder'       => esc_attr__( 'b2c, b2c, etc...', 'woocommerce' ),
            'description'       => __( 'How many times this coupon can be used before it is void.', 'woocommerce' ),
            'type'              => 'number',
            'desc_tip'          => true,
            'class'             => 'short',
            'custom_attributes' => array(
              'step' => 1,
              'min'  => 0,
            ),
            'value'             => $coupon->get_meta("custom_b2bking_block_list")
          ))
        ?>
      </div>
    </div>
    <?php
  }

  private function save_b2bking_options( $post_id ) {
    $custom_b2bking_filter_type = $_POST['custom_b2bking_filter_type'] ?? "black";
    $block_list = $_POST['custom_b2bking_block_list'] ?? "";
    update_post_meta( $post_id, 'custom_b2bking_filter_type', $custom_b2bking_filter_type );
    update_post_meta( $post_id, 'custom_b2bking_block_list', $block_list );
  }

  private function validate_b2bking_filter( WC_Coupon $coupon ) {
    $user = wp_get_current_user();
    $filter_type = $coupon->get_meta("custom_b2bking_filter_type");
    $block_list = $coupon->get_meta("custom_b2bking_block_list");

    if( empty( $block_list ) ) {
      return;
    }

    $block = array_map( function($item){ return trim( $item ); }, explode( ",", $block_list ) ) ;
    $has_b2c = in_array( "b2c", $block );

    if( !$user->exists() && !$has_b2c && $filter_type === "black"){
      return;
    } else if ( !$user->exists() && !$has_b2c && $filter_type === "white" ) {
      throw new Exception(__( "This coupon can be used only by B2B Users.", "woocommerce" ));
    }

    $is_b2b = get_user_meta( $user->ID, "b2bking_b2buser", true ) === "yes";
    $b2b_group = get_user_meta( $user->ID, "b2bking_customergroup", true );

    if( (!$is_b2b && $has_b2c && $filter_type === "black") || (!$is_b2b && !$has_b2c && $filter_type === "white")) {
      throw new Exception(__( "This coupon can be used only by B2B Users.", "woocommerce" ));
    }

    if( !$is_b2b && $has_b2c && $filter_type === "white") {
      return;
    }

    $group = get_post( intval( $b2b_group ) );

    if ( !is_a(  $group, 'WP_Post' ) ||  $group->post_type !== "b2bking_group") {
      return;
    }

    $group_name = $group->post_title;
    $has_b2b = in_array( $group_name, $block );

    if( ($has_b2b && $filter_type === "black") || (!$has_b2b && $filter_type === "white") ) {
      throw new Exception(__( "This coupon cannot be applied to items under a special group pricing.", "woocommerce" ));
    }

  }
 
}

Horizon_Coupons::instantiate();
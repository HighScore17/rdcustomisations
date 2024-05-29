<?php

class WC_Horizon_Admin_Order_Filter_By_Group {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof WC_Horizon_Admin_Order_Filter_By_Group ) {
      self::$instance = new WC_Horizon_Admin_Order_Filter_By_Group();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_filter( 'manage_edit-shop_order_columns', [$this, 'manage_shop_order_colums'], 10, 1 );
    add_action( 'manage_shop_order_posts_custom_column', [ $this, 'display_group_filter_column' ], 10, 1 );
    add_action( 'pre_get_posts', [$this, 'filter_orders_by_group'], 99, 1 );
    add_action( 'restrict_manage_posts', [ $this, 'render_filter_orders_by_group' ] );
  }

  function filter_orders_by_group( $query ) {
    if ( ! is_admin() ) {
      return;
    }
    global $pagenow;
    
    if ( 'edit.php' === $pagenow && 'shop_order' === $query->query['post_type'] && isset( $_GET["b2bking-group"] ) && $_GET["b2bking-group"] !== "all" ) 
    {
      $user_ids = (array) get_users([
        'number'     => - 1,
        'fields'     => 'ID',
        'meta_query' => [
            'relation' => 'OR',
            [
              'key'     => 'b2bking_customergroup',
              'compare' => '=',
              'value'   => $_GET["b2bking-group"]
            ]
        ],
      ]);
      $query->set( 'meta_query', array(
        array(
          'key' => '_customer_user',
          'value' => $user_ids ? $user_ids : [-19],
        )
      ) );
    }
    return;
  }

  function render_filter_orders_by_group() {
    if ( ! isset( $_GET['post_type'] ) || 'shop_order' !== $_GET['post_type'] ) {
      return;
    }
    $posts = get_posts( array( 'post_type' => 'b2bking_group' ) );
    ?>
    <select name="b2bking-group">
    <option value="all">All groups</option>
      <?php foreach( $posts as $post ): ?>
      <option value="<?php echo $post->ID; ?>" <?php echo isset( $_GET["b2bking-group"] ) && $_GET["b2bking-group"] == $post->ID ? "selected" : "" ?>><?php echo $post->post_title; ?></option>	
      <?php endforeach; ?>
    </select>
    <?php
  }

  function manage_shop_order_colums( $columns ) {
    $columns['order-group'] = 'Order Group';
    return $columns;
  }

  function display_group_filter_column( $column ) {
    global $post;
 
    if ( 'order-group' === $column ) {
      $order = wc_get_order( $post->ID );

      if( !__is_valid_wc_order( $order ) ) {
        return;
      }

      $user = $order->get_user_id();
      $group_id = get_user_meta( $user, 'b2bking_customergroup', true );

      if( !$group_id ) {
        echo "B2C";
        return;
      }

      $group = get_post( $group_id );

      if( !$group ) {
        return;
      }

      echo $group->post_title;
    }
  }
}

WC_Horizon_Admin_Order_Filter_By_Group::init();
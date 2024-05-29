<?php 
use GraphQL\Error\UserError;

class WC_Horizon_Cin7_Sizes_Stock_Get_Graphql {
  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof WC_Horizon_Cin7_Sizes_Stock_Get_Graphql ) {
      self::$instance = new WC_Horizon_Cin7_Sizes_Stock_Get_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
  }

  function register_graphql_types() {
    $this->register_object_types();
    register_graphql_field("Product", "cin7", array(
      'type' => 'CIN7',
      "resolve" => [ $this, 'resolve' ]
    ));
  }

  function resolve( $source, $args ) {
    $cin7_products_ids = $source->get_meta( 'cin7_products_id' );

    $stock = WC_Horizon_CIN7_Stock_Sizes_Integration::get_stock( $source->get_id(), $cin7_products_ids );

    $nodes = array();
    $items_per_type = intval($source->get_meta("cin7_stock_items_per_type"));
    foreach($stock as $size => $quantity) {
      $nodes[] = array(
        "size" => $size,
        "stock" => $quantity * ( $items_per_type ? $items_per_type : 1 )
      );
    }

   $total = array_reduce($nodes, function($carry, $key){ return $carry +  $key["stock"]; }, 0);

    return array(
      "stock" => array(
        "presentation" => $source->get_meta("cin7_stock_type"),
        "nodes" => $total > 0 ? $nodes : null
      )
    );
  }

  function register_object_types() {
    register_graphql_object_type( 'CIN7Stock', array(
      'fields' => array(
        'size' => array(
          'type' => 'String'
        ),
        'stock' => array(
          'type' => 'Int'
        )
      )
    ) );
    register_graphql_object_type( 'CIN7StockList', array(
      'fields' => array(
        'nodes' => array(
            'type' => array(
                'list_of' => 'CIN7Stock'
            )
        ),
        "presentation" => array(
          "type" => "String"
        )
      )
    ) );
    register_graphql_object_type('CIN7', [
      'fields' => array(
          'stock' => array(
            'type' => 'CIN7StockList'
          )
      )
    ]);
  }
}

WC_Horizon_Cin7_Sizes_Stock_Get_Graphql::init();



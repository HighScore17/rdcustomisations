<?php

class WC_Horizon_GraphQL_Recurring_Order_Object {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof WC_Horizon_GraphQL_Recurring_Order_Object) ) {
      self::$instance = new WC_Horizon_GraphQL_Recurring_Order_Object();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_objects' ] );
  }

  static function get_recurring_order_fields() {
    return array(
      "id" => array(
        "type" => "Int"
      ),
      "frequency" => array(
        "type" => "horizonOptionWithLabel"
      ),
      "ownerId" => array(
        "type" => "String"
      ),
      "lastOrder" => array(
        "type" => "Order"
      ),
      "linkedOrder" => array(
        "type" => "Order"
      ),
      "status" => array(
        "type" => "String"
      ),
      "payment" => array(
        "type" => "CreditCardObject"
      ),
      "subtotal" => array(
        "type" => "Float"
      ),
      "discount" => array(
        "type" => "Float"
      ),
      "estShippingCost" => array(
        "type" => "Float"
      ),
      "total" => array(
        "type" => "Float"
      ),
      "shipping" => array(
        "type" => "CustomerAddress"
      ),
      "lineItems" => array(
        "type" => "horizonRecurringOrderItemsList"
      ),
      "upcomingDelivery" => array(
        "type" => "String"
      ),
      "createdAt" => array(
        "type" => "String"
      )
    );
  }

  static function get_recurring_order_line_items_fields() {
    return array(
      "quantity" => array(
        "type" => "Int"
      ),
      "product_id" => array(
        "type" => "Int"
      ),
      "variation_id" => array(
        "type" => "Int"
      ),
      "name" => array(
        "type" => "String"
      ),
      "subtotal" => array(
        "type" => "Float"
      ),
      "discount" => array(
        "type" => "horizonDiscountType"
      ),
      "estShippingCost" => array(
        "type" => "Float"
      ),
      "total" => array(
        "type" => "Float"
      ),
      "image" => array(
        "type" => "String"
      ),
      "metaData" => array(
        "type" => array(
          "list_of" => "MetaData"
        )
      ),
      "type" => array(
        "type" => "String"
      ),
    );
  }

  public function register_objects() {
    
    register_graphql_object_type( 'horizonRecurringOrderItems', array(
      "fields" => self::get_recurring_order_line_items_fields()
    ) );

    register_graphql_object_type( 'horizonRecurringOrderItemsList', array(
      "fields" => array(
        'nodes' => array(
          "type" => array(
            "list_of" => "horizonRecurringOrderItems"
          )
        )
      )
    ) );

    register_graphql_object_type( 'horizonRecurringOrderObject', array(
      "fields" => self::get_recurring_order_fields()
    ) );

    register_graphql_object_type( 'horizonRecurringOrderObjectList', array(
      "fields" => array(
        'nodes' => array(
          "type" => array(
            "list_of" => "horizonRecurringOrderObject"
          )
        )
      )
    ) );

    $this->register_recurring_order_options_object();
  }

  public function register_recurring_order_options_object() {
    register_graphql_object_type( 'horizonRecurringOrderOptionsObject', array(
      "fields" => array(
        'frequencies' => array(
          "type" => "horizonOptionWithLabelList"
        ),
        'discount' => array(
          "type" => "horizonDiscountType"
        )
      )
    ) );
  }
}

WC_Horizon_GraphQL_Recurring_Order_Object::init();
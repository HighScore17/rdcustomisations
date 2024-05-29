<?php

class Woocommerce_Packages_Cart {
  private $items = [];

  function add_item( $productId, $variationId, $quantity, $total ) {
    $this->items[] = new Woocommerce_Package_Cart_Item( $productId, $variationId, $quantity, $total );
  }

  /**
   * @return Woocommerce_Package_Cart_Item[] Items as array
   */
  function getItems() {
    return $this->items;
  }
}

class Woocommerce_Package_Cart_Item {
  private $productId;
  private $variationId;
  private $quantity;
  private $total;

  function __construct( $productId, $variationId, $quantity, $total ) {
    $this->productId = $productId;
    $this->variationId = $variationId;
    $this->quantity = intval( $quantity );
    $this->total = floatval( $total );
  }

  function getProductId() {
    return $this->productId;
  }

  function getVariationId() {
    return $this->variationId;
  }

  function getQuantity() {
    return $this->quantity;
  }

  function getTotal() {
    return $this->total;
  }

  function getUnitPrice() {
    return $this->total / $this->quantity;
  }
}
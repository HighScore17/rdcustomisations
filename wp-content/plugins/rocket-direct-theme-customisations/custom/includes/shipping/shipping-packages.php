<?php
use GraphQL\Error\UserError;

class Shipping_Packages_Generator {

  const BOX_PRESENTATION = "box";
  const CASE_PRESENTATION = "case";

  static function generate( $cart ) {
    $package_generator = new Shipping_Packages_Generator();
    return $package_generator->get_cart_packages( $cart );
  }

  static function reduce_packages_by_id( $packages ) {
    $reduced_packages = array();
    foreach ( $packages as $package_key => $package ) {
      if( !key_exists($package->get_id(), $reduced_packages) ) {
        $reduced_packages[$package->get_id()] = array();
      }
      $reduced_packages[$package->get_id()][] = $package;
    }
    return $reduced_packages;
  }

  /**
   * Get the cart packages.
   *
   * @param array $cart Cart items.
   * @return array Return a array of Shipping_Common_Package.
   */
  public function get_cart_packages( $cart ) {
    $packages = [];
    foreach( $cart as $cart_item_key => $item ) {
      $tag = $this->get_item_shipment_key( $item );
      $item_cases = $this->get_cart_item_package( $item );
      if( !$packages[$tag] )
        $packages[$tag] = $item_cases;
      else
        $packages[$tag]->add_quantity( $item_cases->get_quantity(), $item_cases->get_boxes() );
    }
    return $packages;
  }

  function get_item_shipment_key( $item ) {
    $category = get_post_meta( $item['product_id'], 'product_shipment_details_category', true );
    $id = get_post_meta( $item['product_id'], 'product_shipment_details_id', true );
    /*$tags = wp_get_post_terms( $item['product_id'], 'product_tag' );
    $tag = __array_find($tags, function($tag) {
      return __str_starts_with($tag->name, "SHIP");
    });
    */
    return $category . ":" . $id;// $tag ? $tag->name : null;
  }

  /**
   * Get a single cart item package.
   *
   * @param array $item Cart item.
   * @return Shipping_Common_Package Return the package
   */
  function get_cart_item_package( $item ) {
    $product = wc_get_product($item['variation_id']);
    $parent = wc_get_product($product->get_parent_id());
    $quantity = intval($item['quantity']);
    $product_data = $this->validate_package_details($product, $parent);
    $price = floatval($item["line_total"]) / $quantity;
    $presentation =  $product->get_attribute("pa_presentation");

    if(!$product_data || $quantity < 1 || !self::presentation_is_valid( $presentation )) {
      $this->error(__('Some products are not eligible for shipping', 'rocket-direct-shipping'));
    }
    
    $cases = $quantity;
    $boxes = 0;
    $price_per_box = 0;
    // Convert boxes to cases
    if( $presentation === self::BOX_PRESENTATION ) {
      list( $cases, $boxes ) = $this->boxes_to_cases( $quantity, $product_data );
      $price_per_box = $price;
      $price = ($price_per_box / $product_data["items_per_presentation"]) * $product_data["quantity_per_case"];
    }
    
    return $this->get_package_details($parent, $price, $cases, $boxes, $price_per_box);
  }

  /**
   * Convert Boxes to Cases
   */
  function boxes_to_cases( $quantity, $product_data ) {
    $product_count = $product_data["items_per_presentation"] * $quantity;
    $cases_count = $product_count / $product_data["quantity_per_case"];

    if( floor( $cases_count ) == $cases_count ) {
      return array( $cases_count, 0 );
    }

    $cases = floor( $cases_count );
    $boxes = $cases_count - $cases;
    $boxes = ($boxes * $product_data["quantity_per_case"] / $product_data["items_per_presentation"]);
    return array( $cases, $boxes );
  }

  static function presentation_is_valid( $presentation ) {
    return $presentation === self::BOX_PRESENTATION || $presentation === self::CASE_PRESENTATION;
  }

  /**
   * Validate the product details
   *
   * @param WC_Product $product product variation.
   * @param WC_Product $parent main product.
   * @return array|null Return the quantity per case and items per presentation if its valid.
   */
  function validate_package_details( $product, $parent ) {
    $quantity_per_case = intval($parent->get_meta("shipment_case_items"));
    $items_per_presentation = intval( $product->get_meta("contains_item") );
    if( $this->validate_package_data($quantity_per_case) && $this->validate_package_data($items_per_presentation) ) {
      return array(
        "quantity_per_case" => $quantity_per_case,
        "items_per_presentation" => $items_per_presentation
      );
    }
    return null;
  }
  /**
   * Validate the package data
   *
   * @param int $value the value to validate.
   * @return bool Return true if the data is valid.
   */

  function validate_package_data( $value ) {
    return $value > 0 && is_finite( $value );
  }
  
  /**
   * Get the package details
   *
   * @param WC_Product $product product variation.
   * @param int $quantity the number of cases.
   * @return Shipping_Common_Package Return the package
   */

  function get_package_details($product, $price, $cases = 1, $boxes = 0, $price_per_box = 0 ) {
    return new Shipping_Common_Package(
      $product->get_id(),
      floatval($product->get_meta('shipment_dimensions_weight')),
      floatval($product->get_meta('shipment_dimensions_length')),
      floatval($product->get_meta('shipment_dimensions_width')),
      floatval($product->get_meta('shipment_dimensions_height')),
      $product->get_meta('shipment_case_class'),
      intval($product->get_meta('shipment_case_per_pallets')),
      intval($product->get_meta('shipment_max_ups')),
      intval($product->get_meta('shipment_max_pallets')),
      $price,
      $cases,
      $boxes,
      $price_per_box
    );
  }

  function error( $message ) {
    throw new UserError( $message );
  }
}
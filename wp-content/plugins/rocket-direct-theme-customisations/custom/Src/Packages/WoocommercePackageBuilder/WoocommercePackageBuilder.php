<?php
require_once __DIR__ . "/WoocommercePackagesCart.php";
class Woocommerce_Package_Builder {
  const BOX_PRESENTATION = "box";
  const CASE_PRESENTATION = "case";

  static function buildFromCart( Woocommerce_Packages_Cart $cart ) {
    $items = $cart->getItems();
    $packages = [];

    foreach( $items as $item ) {
      $category = self::getShipmentCategory( $item->getProductId() );

      if( !$category ) {
        return new WP_Error('package-builder', 'Some items aren\'t elegible for shipping.');
      }

      $product_id = $item->getProductId();
      $itemPackages = self::getCartItemPackages( $item );

      if( is_wp_error( $itemPackages ) ) 
        return $itemPackages;

      if( $itemPackages["boxes"] ) {
        $presentationKey = $product_id . ":" . $itemPackages["boxes"]->get_id();
        if( ! key_exists( $presentationKey, $packages[$category] ) )
          $packages[$category][$presentationKey] = $itemPackages["boxes"];
        else
          $packages[$category][$presentationKey]->add_quantity( $itemPackages["boxes"]->get_quantity() );
      }

      if( $itemPackages["cases"] ) {
        $presentationKey = $product_id . ":" . $itemPackages["cases"]->get_id();
        if( ! key_exists( $presentationKey, $packages[$category] ) )
          $packages[$category][$presentationKey] = $itemPackages["cases"];
        else
          $packages[$category][$presentationKey]->add_quantity( $itemPackages["cases"]->get_quantity() );
      }
    }
    return $packages;
  }

  static function getShipmentCategory( $productId ) {
    return get_post_meta( $productId, 'product_shipment_details_category', true );
  }

  static function getCartItemPackages( Woocommerce_Package_Cart_Item $item ) {
    $product = wc_get_product($item->getVariationId());
    $parent = wc_get_product($product->get_parent_id());
    $quantity = intval($item->getQuantity());
    $product_data = self::validatePackageDetails($product, $parent);
    $price = $item->getUnitPrice();
    $presentation =  $product->get_attribute("pa_presentation");

    if( is_wp_error( $product_data ) ) {
      return $product_data;
    }

    if( $quantity < 1 ) {
      return new WP_Error( 'invalid-quantity', 'Some products have a invalid quantity' );
    }
    $cases = $quantity;
    $case_variation = $product;
    $cases_package = null;
    $boxes = 0;
    $boxes_package = null;

    if( $presentation === self::BOX_PRESENTATION ) {
      list( $cases, $boxes ) = self::convertBoxesToCases( $quantity, $product_data );
      if( $boxes > 0 ) {
        $boxes_package = self::getPackage($parent, $product, $boxes, $presentation, $price  );
      }
      $price = ($price / $product_data["items_per_presentation"]) * $product_data["quantity_per_case"];
      $case_variation = wc_get_product( find_product_presentation_variation( $parent, $case_variation ) );

    }

    if( $cases > 0 && $case_variation ) {
      $cases_package = self::getPackage($parent, $case_variation, $cases, "case", $price  );
    }

    return array(
      "boxes" => $boxes_package,
      "cases" => $cases_package
    );
  }

  static function validatePackageDetails( $product, $parent ) {
    $quantity_per_case = intval($parent->get_meta("shipment_case_items"));
    $items_per_presentation = intval( $product->get_meta("contains_item") );
    if( self::validatePackageQuantity($quantity_per_case) && self::validatePackageQuantity($items_per_presentation) ) {
      return array(
        "quantity_per_case" => $quantity_per_case,
        "items_per_presentation" => $items_per_presentation
      );
    }
    return new WP_Error('invalid-quantity', 'Some items have a invalid configuration');
  }

  static function validatePackageQuantity( $value ) {
    return $value > 0 && is_finite( $value );
  }

  static function convertBoxesToCases( $quantity, $product_data ) {
    $product_count = $product_data["items_per_presentation"] * $quantity;
    $cases_count = $product_count / $product_data["quantity_per_case"];

    if( floor( $cases_count ) == $cases_count ) {
      return array( $cases_count, 0 );
    }

    $cases = floor( $cases_count );
    $boxes = $cases_count - $cases;
    $boxes = intval( round( floatval( ($boxes * $product_data["quantity_per_case"] / $product_data["items_per_presentation"]) ) ));
    return array( $cases, $boxes );
  }

  static function getPackage( $product, $variation, $quantity, $presentation, $price ) {
    return new Common_Shipping_Package(
      array(
        "id" => $variation->get_id(),
        "weight" => floatval($product->get_meta('shipment_dimensions_weight')),
        "length" => floatval($product->get_meta('shipment_dimensions_length')),
        "width" => floatval($product->get_meta('shipment_dimensions_width')),
        "height" => floatval($product->get_meta('shipment_dimensions_height')),
        "class" => $product->get_meta('shipment_case_class'),
        "cases_per_pallet" => intval($product->get_meta('shipment_case_per_pallets')),
        "max_cases" => intval($product->get_meta('shipment_max_ups')),
        "max_pallets" => intval($product->get_meta('shipment_max_pallets')),
        "price" => floatval( $price ),
        "quantity" => intval( $quantity ),
        "presentation" => $presentation
      )
    );
  }



}


class Common_Shipping_Package {
  private $quantity;
  private $weight;
  private $length;
  private $width;
  private $height;
  private $class;
  private $cases_per_pallet;
  private $total_weight;
  private $max_cases;
  private $max_pallets;
  private $id;
  private $price;
  private $presentation;
  private $size;

  function __construct( $args = array() ) {
    $this->id = $args["id"] ?? -1;
    $this->weight = $args["weight"] ?? 0;
    $this->length = $args["length"] ?? 0;
    $this->width = $args["width"] ?? 0;
    $this->height = $args["height"] ?? 0;
    $this->class = $args["class"] ?? 0;
    $this->cases_per_pallet = $args["cases_per_pallet"] ?? 0;
    $this->max_cases = $args["max_cases"] ?? 0;
    $this->max_pallets = $args["max_pallets"] ?? 0;
    $this->price = $args["price"] ?? 0;
    $this->quantity = $args["quantity"] ?? 0;
    $this->total_weight = $this->weight * $this->quantity;
    $this->presentation = $args["presentation"] ?? "case";
    $this->size = $args["size"] ?? null;
  }

  public function add_quantity($quantity) {
    $this->quantity += $quantity;
    $this->total_weight += $this->weight * $quantity;

  }

  public function set_quantity($quantity) {
    $this->quantity = $quantity;
    $this->total_weight = $this->weight * $quantity;
  }

  public function get_id() {
    return $this->id;
  }
  
  public function get_weight() {
    return floatval( $this->weight );
  }

  public function get_length() {
    return floatval( $this->length );
  }

  public function get_width() {
    return floatval( $this->width );
  }

  public function get_height() {
    return floatval( $this->height );
  }

  public function get_class() {
    return $this->class;
  }

  public function get_cases_per_pallet() {
    return $this->cases_per_pallet;
  }

  public function get_max_cases() {
    return $this->max_cases;
  }

  public function get_max_pallets() {
    return $this->max_pallets;
  }

  public function get_quantity() {
    return intval( $this->quantity );
  }

  public function get_total_weight() {
    return floatval( $this->total_weight );
  }

  public function get_price() {
    return $this->price;
  }

  public function get_presentation() {
    return $this->presentation;
  }

  public function get_size() {
    return $this->size;
  }
}
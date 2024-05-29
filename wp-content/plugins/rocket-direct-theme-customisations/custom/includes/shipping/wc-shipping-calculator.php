<?php
function horizon_rate_quote_method_init() {
  // Your class will go here
  class WC_Horizon_Rate_Quote_Shipping_Calculator extends WC_Shipping_Method {
    /**
    * Constructor for your shipping class
    *
    * @access public
    * @return void
    */
    public function __construct( $instance_id = 0 ) {
      $this->id                 = 'horizon_rate_quote'; // Id for your shipping method. Should be uunique.
      $this->instance_id = absint( $instance_id );
      $this->title = "Horizon request a Shipping Quote"; // This can be added as an setting but for this example its forced.
      $this->method_title = __( 'Horizon request a Shipping Quote' ); // Description shown in admin
      $this->method_description = __( 'Shipping method to be used where the exact shipping amount needs to be quoted' ); // Description shown in admin
      $this->supports = array(
        'shipping-zones',
        'instance-settings',
			  'instance-settings-modal'
      );
      $this->init();
    }
  
  
    function init() {
      $this->init_form_fields();
      $this->init_settings();
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() ) {
      // This is where you'll add your rates
      //$credentials = $this->get_api_credentials();
      //$ups = new Shipping_UPS( TRUE, $credentials );
      $rates = array("rates" => []);
      if( isset( $_POST["is_checkout"] ) ) {
        $calculator = new Shipping_Calculator_V2( TRUE );
        $rates = $calculator->get_rates();
        $_POST["insurance_cost"] = $rates["insurance"];
        WC()->session->set("cached_rates_quotes", $rates);
      } else {
        $rates = WC()->session->get("cached_rates_quotes");
        if( !is_array($rates) ) {
          $rates = array();
        }
      }
      if(  is_array($rates) && is_array($rates["rates"]) ) {
        foreach( $rates["rates"] as $rate ) {
          if( !$rate["error"] ) {
            $insurance_rate = $rate;
            $this->add_custom_rate( $rates, $rate, $package );
            $insurance_rate["service"]["code"] = $rate["service"]["code"] . "-with-insurance";
            $insurance_rate["service"]["description"] = $rate["service"]["description"] . " with insurance";
            $this->add_custom_rate( $rates, $insurance_rate, $package, $rate["insurance"]["cost"] );
          }
        }
      }
    }

    function add_custom_rate( $rates, $rate, $packages, $insurance = FALSE ) {

      $this->add_rate( array(
        'id' => $rate["service"]["code"],
        'label' => $rate["service"]["description"] . "~" . $rate["estimated_delivery"],
        'cost' => floatval($rate["total"]) + ( $insurance ? floatval($insurance) : 0 ),
        'taxes' => FALSE,
        'package' => $packages,
        'meta_data' => array(
          "Rate Cost" => "$" . $rate["total"],
          "Insurance Cost" => "$" .($insurance ? $insurance : "0.00"),
          "Accessorials" => implode(", ", $rates["accessorials"]),
          "Residential Address" => $rates["residential_address"] ? "Yes" : "No",
        )
      ) );
    }

    function get_api_credentials ( $ups = TRUE ) {
      $options = get_option( 'shipping_calculator' );
      if( $ups ) {
        return array(
          "AccessLicenseNumber" => $options['ups_api_key'],
          "Username" => '',// $options['ups_username'],
          "Password" => '',//$options['ups_password']
        );
      } else {
        return array(
          "user" => $options['estes_express_username'],
          "password" => $options['estes_express_password'],
          "account" => $options['estes_express_account']
        );
      }
    }
  }
}
add_action( 'woocommerce_shipping_init', 'horizon_rate_quote_method_init' );

function add_horizon_rate_quote_shipping_method( $methods ) {
  $methods['horizon_rate_quote'] = 'WC_Horizon_Rate_Quote_Shipping_Calculator'; 
  return $methods;
}

add_filter( 'woocommerce_shipping_methods', 'add_horizon_rate_quote_shipping_method' );
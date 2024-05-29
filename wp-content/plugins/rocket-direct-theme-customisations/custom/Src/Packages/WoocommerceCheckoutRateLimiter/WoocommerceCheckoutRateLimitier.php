<?php
require_once __DIR__ . "/WoocommerceCheckoutAdmin.php";
if ( 
  !class_exists( 'WoocommerceCheckoutRateLimitier' ) &&
  class_exists( 'Memcached' ) && 
  in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) 
) {
  class WoocommerceCheckoutRateLimitier {
    private $cache = null;
    private $prefix = "wc-checkout-rate-limit-for-";

    public function __construct() {
      $this->cache = new Memcached();
    }

    /**
     * Start the communication  with the memcached server
     */
    public function init() {
      $this->cache->addServer("localhost", 11211);
    }

    /**
     * Get the key formatted to retrieve data from the memcached server
     */
    function getKey( $ip ) {
      return $this->prefix . $ip;
    }

    /**
     * Add a rate request to the cache
     */
    function addRate( $ip, $limit = 5, $expiration = 0 ) {
      $key = $this->getKey( $ip );
      $remaining = $this->cache->get( $key );

      if( $remaining === false ) {
        $remaining = $limit;
      }

      $this->cache->set( $key, --$remaining, $expiration );

      return $remaining;
    }

    /**
     * Delete the rate attemps from the cache
     */
    function deleteRate( $ip ) {
      $this->cache->delete( $this->getKey($ip) );
    }

    /**
     * Get the total remaning attemps for a given IP
     */
    function getRemainingAttemps( $ip, $default = 5 ) {
      $remaining = $this->cache->get( $this->getKey( $ip ) );
      return $remaining !== false ? $remaining : $default; 
    }

    /**
     * Check if the user hasn't been bloocked 
     */
    function canCheckout( $ip ) {
      $remaining = $this->cache->get( $this->getKey( $ip ) );
      return $remaining === false || $remaining > 0;
    }

    /**
     * Retrive all the failed checkout attemps 
     */
    function getAllAttemps() {
     $keys = $this->cache->getAllKeys();
     $cache = $this->cache;
     $prefix = $this->prefix;
     return array_map( function( $ip ) use ($cache, $prefix) {
      return array( "ip" => str_replace($prefix, "", $ip) , "attemps" => $cache->get( $ip ));
     }, $keys );
    }
  }

  require_once __DIR__ . "/WoocommerceCheckoutLimitier.php";
  
  global $wc_rate_limitier;
  $wc_rate_limitier = new WoocommerceCheckoutRateLimitier();
  $wc_rate_limitier->init();
  
  function get_woocommerce_checkout_rate_limitier() : WoocommerceCheckoutRateLimitier {
    global $wc_rate_limitier;
    return $wc_rate_limitier;
  }
}

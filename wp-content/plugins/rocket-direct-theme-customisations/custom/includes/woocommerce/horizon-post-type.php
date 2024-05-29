<?php

class WC_Horizon_Post {
  protected $object_type = "post";

  protected $public_meta = array();
  
  private $props = array();
  
  private $changes = array();
  
  private $id = 0;

  private $post_title = null;

  function __construct( $id = 0 ) {
    $this->id = $id;
  }

  /**
   * Get the current post id
   */
  public function get_id() {
    return $this->id;
  }

  /**
   * Get a property value
   * @param string $key The property key
   */
  public function get_prop( $key ) {
    if( array_key_exists($key, $this->changes) ) {
      return $this->changes[$key];
    }
    else if ( array_key_exists($key, $this->props) ) {
      return $this->props[$key];
    }
    return null;
  }

  /**
   * Set a property
   * @param string $key The property key
   * @param string $value The property value
   */
  public function set_prop( $key, $value, $as_change = true ) {
    if( $as_change ) {
      $this->changes[$key] = $value;
    }
    else {
      $this->props[$key] = $value;
    }
  }

  /**
   * Set multiples properties
   */
  public function set_props( $props ) {
    foreach( $props as $key => $value ) {
      $this->set_prop($key, $value);
    }
  }

  /**
   * Add value as metadata 
   */
  public function update_meta( $key, $value ) {
    update_post_meta( $this->id, 'meta.' . $key, $value );
  }

  public function get_meta( $key ) {
    return get_post_meta( $this->id, 'meta.' . $key, true );
  }

  public function load_props_from_post( ) {
    foreach( $this->public_meta as $meta ) {
      $this->set_prop( $meta, get_post_meta( $this->get_id(), $meta, true ), false );
    }
  }

  /**
   * Set the post title
   */
  protected function get_post_title( $id ) {
    return "";
  }

  /**
   * Save the post to the database
   */
  public function save() {
    if( !$this->get_id() ) {
      $this->create();
    } else {
      $this->update();
    }
    do_action( "woocommerce_horizon_saved_" . $this->object_type, $this->get_id() );
  }

  /**
   * Create the post in the database
   */
  private function create() {
    $id = wp_insert_post( array(
      "post_type" => $this->object_type,
      "post_status" => "publish",
      "comment_status" => "closed",
      "ping_status" => "closed",
    ) );

    $post_title = $this->get_post_title( $id );

    if( $post_title ) {
      wp_update_post( array(
        'ID' => $id,
        "post_title" => $post_title
      ) );
    }
    
    
    $meta = array_merge($this->props, $this->changes);
    foreach( $meta as $key => $value ) {
      add_post_meta($id, $key, $value);
    }
    $this->id = $id;
    $this->props = $meta;
    $this->changes = array();
  }

  /**
   * Update the post in the database
   */
  private function update() {
    foreach( $this->changes as $key => $value ) {
      update_post_meta($this->id, $key, $value);
    }
    $this->props = array_merge($this->props, $this->changes);
    $this->changes = array();
  }

  

}

<?php 

function __array_find(array $arr, callable $callback) {
  foreach($arr as $item) {
    if($callback($item)) {
      return $item;
    }
  }
  return null;
}



function __empty_some_key( $arr, $keys ) {
  if( !is_array( $arr ) ) {
    return true;
  }

  foreach( $keys as $key ) {
    if( !array_key_exists($key, $arr) || empty($arr[$key]) ) {
      return true;
    }
  }
  return false;
}

function __sanitize_array( $arr ) {
  return array_map(function( $e ) {
    return sanitize_text_field( $e );
  }, $arr);
}

function __vector_3D( $length = 0, $width = 0, $height = 0 ) {
  return array(
    "length" => $length,
    "width" => $width,
    "height" => $height
  );
}
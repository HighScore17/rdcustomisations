<?php

function __str_starts_with( $str, $prefix ) {
  return substr($str, 0, strlen($prefix)) === $prefix;
}

function __str_ends_with( $str, $postfix ) {
  return substr($str, -strlen($str)) === $postfix;
}

function __safe_number_format( $num, $decimals = 0 ) {
  try {
    return number_format( $num, $decimals );
  } catch (Error $e) {
    return 0;
  }
}
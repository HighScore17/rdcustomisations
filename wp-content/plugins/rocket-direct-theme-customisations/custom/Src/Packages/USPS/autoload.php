<?php

if( extension_loaded( 'SimpleXML' ) ) {
  require_once __DIR__ . "/USPS.php";

  // Address
  require_once __DIR__ . "/Address/USPSAddressValidator.php";
  
  //Graphql
  require_once __DIR__ . "/GraphQL/autoload.php";
}


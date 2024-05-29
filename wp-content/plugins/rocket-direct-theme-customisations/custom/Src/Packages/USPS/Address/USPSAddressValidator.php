<?php

/**
 * Note: I don't know why USPS use address 2 as address 1 and address 1 and address 2
 * PLEASE DON'T UNREVERT THE ADDRESSES IN THE XML
 */


class USPSAddressValidatorSDK {
  /**
   * Validate a address
   * @return Array|WP_Error Address Validated
   */
  static function validate( $args = array() ) {
    $body = self::requestAddressValidation( self::getValidationXMLObject( $args ) );

    if( !$body ) {
      return new WP_Error('service-unavailable', 'Address validator service is unavailable.');
    }

    $addressValidated = new SimpleXMLElement( $body );

    if( $addressValidated->Address->Error ) {
      return new WP_Error('address-error',$addressValidated->Address->Error->Description );
    }

    return self::returnValidatedAddress( $addressValidated );
  }

  /**
   * Get a XML Object to send to USPS
   * @return SimpleXMLElement Return a valid SimpleXMLElement object 
   */
  static function getValidationXMLObject( $args = array() ) {
    $xmlString = self::getXMLBodyRequest();
    $addressValidator = new SimpleXMLElement( $xmlString );
    $addressValidator->addAttribute("USERID", USPS_SDK_Helper::getUserID());
    $addressValidator->Address->Address1 = $args["address2"];
    $addressValidator->Address->Address2 = $args["address1"];
    $addressValidator->Address->City = $args["city"];
    $addressValidator->Address->State = $args["state"];
    $addressValidator->Address->Zip5 = $args["zip"];
    return $addressValidator;
  }

  /**
   * Request a address validation to USPS
   * @return String the request response body
   */
  static function requestAddressValidation( \SimpleXMLElement $addressValidator ) {
    $url = USPS_SDK_Helper::getURL("ShippingAPI.dll?API=Verify&XML=" . urlencode($addressValidator->asXML()));
    $response = wp_remote_get( $url );
    $statusCode = wp_remote_retrieve_response_code( $response );
    return intval( $statusCode ) === 200 ? wp_remote_retrieve_body( $response ) : '';
  }

  /**
   * Get a message based on the returned response from USPS
   * @return Array Message
   */
  static function getMessageFromValidation( \SimpleXMLElement $addressValidated ) {
    if( $addressValidated->Address->ReturnText ) {
      return array(
        "type" => "warning",
        "payload" => trim( $addressValidated->Address->ReturnText )
      );
    }
  }

  /**
   * Get the return array
   * @return Array The final response
   */
  static function returnValidatedAddress( \SimpleXMLElement $addressValidated ) {
    return array(
      "address" => array(
        "address1" => $addressValidated->Address->Address2,
        "address2" => $addressValidated->Address->Address1,
        "city" => $addressValidated->Address->City,
        "state" => $addressValidated->Address->State,
        "zip" => $addressValidated->Address->Zip5,
      ),
      "message" => self::getMessageFromValidation( $addressValidated )
    );
  }

  /**
   * Get the XML String 
   */
  static function getXMLBodyRequest() {
    return <<<XML
      <AddressValidateRequest>
        <Address>
          <Address1></Address1>
          <Address2></Address2>
          <City></City>
          <State></State>
          <Zip5></Zip5>
          <Zip4/>
        </Address>
      </AddressValidateRequest>
    XML;
  }
}
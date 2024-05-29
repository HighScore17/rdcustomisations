<?php
class Apollo_Contacts {
  static function update_stage( $ids, $stage ) {
    $apollo = new Apollo_SDK();
    $response = $apollo->make_request("contacts/update_stages/", "POST", array(
      "contact_ids" => array_values($ids),
      "contact_stage_id" => $stage
    ), true);

    $body = json_decode( $response, true );

    if( !is_array( $body ) || !is_array( $body["contacts"] ) ) {
      return false;
    }

    return true;
  }
}
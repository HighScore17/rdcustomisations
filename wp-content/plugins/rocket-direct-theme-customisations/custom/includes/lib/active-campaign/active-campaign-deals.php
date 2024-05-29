<?php

class Active_Campaign_Deal {

  static function update_deal_stage( $deal_id, $stage_id, $fields = array() ) {
    $AC = new Active_Campaign_SDK();
    $body = array(
      "deal" => array(
        "stage" => $stage_id,
      )
    );
    if(count($fields)) {
      foreach($fields as $field) {
        $body["deal"]["fields"][] = array(
          "customFieldId" => $field["id"],
          "fieldValue" => $field["value"]
        );
      }
    }
    $response = $AC->call("deals/" . $deal_id, $body, "PUT");
    return wp_remote_retrieve_body($response);
  }

  static function get_deal( $id ) {
    $sdk = new Active_Campaign_SDK();
    $body = wp_remote_retrieve_body($sdk->call("deals/" . $id, null, "GET" ));

    if( empty( $body ) ) {
      return null;
    }

    $response = json_decode( $body, true );
    if( !is_array( $response["deal"] ) ) {
      return null;
    }

    return $response;
  }
}
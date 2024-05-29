<?php
    $multiple_address_fields = [
        ["name" => "type", "required" => true],
        ["name" => "firstName", "required" => true],
        ["name" => "lastName", "required" => true],
        ["name" => "company", "required" => false],
        ["name" => "country", "required" => true],
        ["name" => "address1", "required" => true],
        ["name" => "address2", "required" => false],
        ["name" => "city", "required" => true],
        ["name" => "state", "required" => true],
        ["name" => "postcode", "required" => true],
        ["name" => "phone", "required" => true],
        ["name" => "email", "required" => true],
        ["name" => "user_id", "required" => true],
        ["name" => "alias", "required" => true]
    ];
    function multiple_address_add()
    {
        global $wpdb;
        global $multiple_address_fields;
        $table_name = $wpdb->prefix . "wc_multiples_address";
        $hasRequiredFields = true;
        $data = [];
        $response = array("success" => false);

        //Check if the required fields exists
        foreach($multiple_address_fields as $field)
        {
            if($field["required"] && !isset($_POST[$field["name"]]))
                $hasRequiredFields = false;
            $data[$field["name"]] = $_POST[$field["name"]];
        }
        $data["user_id"] = intval($data["user_id"]);
        if($hasRequiredFields)
        {
            if($wpdb->insert(
                $table_name,
                $data))
                {
                    $response["success"] = true;
                    echo json_encode($response);
                }
                else
                    echo json_encode($wpdb->print_error());
        }
        else 
            echo json_encode($response);
        wp_die();
    }


    function wcma_create_shipping_address( $user_id, $alias, $address, $address_type, $isPrimary ) {
        global $wpdb;
        $table_name = $wpdb->prefix . "wc_multiples_address";
        $data = array_merge(array(
            "type" => "shipping",
            "alias" => $alias,
            "user_id" => $user_id,
            "isPrimary" => $isPrimary ? "1" : "0",
            "address_type" => $address_type
        ), $address);
        $inserted = $wpdb->insert( $table_name, $data);
        return $inserted ? $wpdb->insert_id : NULL;
    }
?>

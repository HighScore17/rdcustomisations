<?php 
  require_once __DIR__ . "/controller.php";
  require_once __DIR__ . "/../components/dropdown.php";
  require_once __DIR__ . "/fetch.php";
?>
<script src="https://cdn.tailwindcss.com"></script>

<form method="post">
  <!-- Custom Fields Selector -->
  <p class="mt-2 font-bold text-lg"> Web Account </p>
  <?php
    /*renderDropdown( "ac-to-apollo-apollo-field", array_map( function ($cfield){ 
      return array(
        "label" => $cfield["name"],
        "value" => $cfield["id"],
        "data" => $cfield["picklist_values"],
      );
    }, $cfields["typed_custom_fields"] ), $apollo_field, "Apollo", "mb-3 w-52" );
    */
    ?>
    <br/>
    <?php
    renderDropdown( "ac-integration-web-account-field", array_map( function ($cfield){ 
      return array(
        "label" => $cfield["title"],
        "value" => $cfield["id"],
        "data" => $cfield["links"]["options"],
      );
    }, $ac_cfields["fields"] ), $ac_web_account_field, "Custom Field", "mb-3 w-52" );
  ?>

  <br/>
<?php 

  if( $ac_web_account_field ) {
    renderDropdown( 
      "ac-integration-web-account-field-value", 
      $ac_web_account_field_obj["options"], 
      $ac_web_account_field_value,
      "Value", 
      "mb-3 w-52" );
  }
 ?>
 <br/>
  <input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	"/>

</form>


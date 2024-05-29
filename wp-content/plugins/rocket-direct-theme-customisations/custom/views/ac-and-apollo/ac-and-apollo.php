<?php 
  require_once __DIR__ . "/controller.php";
  require_once __DIR__ . "/../components/dropdown.php";
  require_once __DIR__ . "/fields.php";
?>
<script src="https://cdn.tailwindcss.com"></script>

<form method="post">
  <!-- Custom Fields Selector -->
  <p class="my-2 font-bold text-lg"> Life Cycles Custom Fields </p>
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
    renderDropdown( "ac-to-apollo-ac-field", array_map( function ($cfield){ 
      return array(
        "label" => $cfield["title"],
        "value" => $cfield["id"],
        "data" => $cfield["links"]["options"],
      );
    }, $ac_cfields["fields"] ), $ac_field, "Active Campaing", "mb-3 w-52" );
  ?>

  <hr/>
  <p class="mb-5 font-bold text-lg"> Life Cycles to Map </p>
  <input 
  class="shadow appearance-none border  rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
  name="ac-to-apollo-lifecyclies"
  id="ac-to-apollo-lifecyclies"
  value="<?php echo implode(",", $lifecycles); ?>"
  />
  <hr/>

  <!-- Options Selector -->
  <p class="mb-5 font-bold text-lg"> Life Cycles Options </p>
  <?php foreach( $lifecycles as $lifecycle ): ?>
    <div class="bg-slate-50	 rounded-md p-3 relative mb-8">
      <p class="bg-slate-50 absolute rounded-md py-1 px-2 font-bold	" style="top: -15px;"><?php echo strtoupper( str_replace("-", " ", $lifecycle)); ?></p>
      <?php renderDropdown( 
        "ac-to-apollo-apollo-" . $lifecycle . "-option", 
        //$apollo_lifecycle_options, 
        array_map( function( $cfield ) {
          return array(
            "label" => $cfield["name"],
            "value" => $cfield["id"]
          );
        }, $cfields ),
        key_exists( $lifecycle, $apollo_lifecycle_values ) ? $apollo_lifecycle_values[$lifecycle] : null, 
        "Apollo", "mb-3 w-52" 
        ); ?>
      <br/>
      <?php renderDropdown( 
        "ac-to-apollo-ac-" . $lifecycle . "-option", 
        $ac_lifecycle_options, 
        key_exists( $lifecycle, $ac_lifecycle_values ) ? $ac_lifecycle_values[$lifecycle] : null,
        "Active Campaign", 
        "mb-3 w-52" ); ?>
    </div>
  <?php endforeach; ?>
  <hr/>
  <p class="my-2 font-bold text-lg"> Apollo to AC Contacts </p>
  <?php renderDropdown( 
        "ac-to-apollo-apollo-sync-by-stage", 
        array_map( function( $cfield ) {
          return array(
            "label" => $cfield["name"],
            "value" => $cfield["id"]
          );
        }, $cfields ), 
        $apollo_stage_to_sync,
        "Apollo Stage to Sync", 
        "mb-3 w-52" ); ?>
        <br/>
  <?php renderDropdown( 
        "ac-to-apollo-apollo-default-stage-at-create", 
        array_map( function( $cfield ) {
          return array(
            "label" => $cfield["name"],
            "value" => $cfield["id"]
          );
        }, $cfields ), 
        $apollo_defult_stage,
        "Apollo New Stage", 
        "mb-3 w-52" ); ?>
  <div>
  <?php renderDropdown( 
        "ac-to-apollo-ac-default-stage-at-create", 
        $ac_lifecycle_options, 
        $ac_defult_stage,
        "Active Campaign Default Stage", 
        "mb-3 w-52" ); ?>
  <div>
  <input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	"/>
  </div>
</form>
<script src="https://cdn.tailwindcss.com"></script>

<?php
$apis = [ 'apollo', 'active-campaign', 'usps-user-id' ];

?>
<form method="post">
<?php
foreach( $apis as $api ): ?>
<?php 
  if( isset( $_POST["horizon-api-key-$api"] ) ) {
    update_option("horizon-api-key-$api", $_POST["horizon-api-key-$api"]);
  }
?>
<div class="mt-3">
<label><?php echo strtoupper( str_replace("-", " ", $api)); ?></label>
<input 
  class="shadow appearance-none border  rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
  name="horizon-api-key-<?php echo $api ?>"
  id="horizon-api-key-<?php echo $api ?>"
  value="<?php echo get_option("horizon-api-key-$api"); ?>"
  />
</div>
<?php endforeach; ?>
<input type="submit" class=" transition ease-in-out  bg-green-500 py-2 px-4 rounded-md text-white hover:bg-green-700	cursor-pointer	"/>

</form>
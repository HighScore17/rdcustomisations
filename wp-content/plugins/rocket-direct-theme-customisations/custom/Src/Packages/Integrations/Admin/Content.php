<form method="post">
<?php

Woocommerce_Integration_Components::input(array(
  "label" => "API Key",
  "id" => "ss_api_key"
));
echo "<br/>";

Woocommerce_Integration_Components::checkbox(array(
  "label" => "Sync orders automatically when order status is processing",
  "id" => "ss_sync_order"
));
echo "<br/><br/>";
Woocommerce_Integration_Components::checkbox(array(
  "label" => "Create labels when order is synced to ShipStation",
  "id" => "ss_create_labels"
));
echo "<br/><br/>";

Woocommerce_Integration_Components::button(array(
  "content" => "Save"
));
?>
<input type="hidden" name="integration-controller" value="shipstation_integration"/>
</form>
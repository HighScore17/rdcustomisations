<form method="post">
<?php


Woocommerce_Integration_Components::input(array(
  "label" => "API Key",
  "id" => "ss_api_key",
  "name" => "ss_api_key",
  "value" => Woocommerce_ShipStation_Admin_Values::apiKey()
));
echo "<br/>";
Woocommerce_Integration_Components::input(array(
  "label" => "API Secret",
  "id" => "ss_api_secret",
  "name" => "ss_api_secret",
  "value" => Woocommerce_ShipStation_Admin_Values::apiSecret()
));
echo "<br/>";

Woocommerce_Integration_Components::checkbox(array(
  "label" => "Sync orders automatically when order status is processing",
  "id" => "ss_sync_order",
  "name" => "ss_sync_order",
  "checked" => Woocommerce_ShipStation_Admin_Values::canSyncOrder() === "yes"
));
echo "<br/><br/>";
Woocommerce_Integration_Components::checkbox(array(
  "label" => "Create labels when order is synced to ShipStation",
  "id" => "ss_create_labels",
  "name" => "ss_create_labels",
  "checked" => Woocommerce_ShipStation_Admin_Values::canCreateLabels() === "yes"
));
echo "<br/><br/>";

Woocommerce_Integration_Components::button(array(
  "content" => "Save"
));
?>
<input type="hidden" name="integration-controller" value="shipstation_integration"/>
</form>
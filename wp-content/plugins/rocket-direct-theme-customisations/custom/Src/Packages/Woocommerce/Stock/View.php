<form method="post">
<?php
Woocommerce_Integration_Components::checkbox(array(
  "label" => "Enable out of stock",
  "id" => "wc_stock_enable",
  "name" => "wc_stock_enable",
  "checked" => Woocommerce_Stock_Packagen_Admin_Values::canSetOutStock() === "yes"
));
echo "<br/><br/>";
Woocommerce_Integration_Components::input(array(
  "label" => "Min Cases before out of stock",
  "id" => "wc_stock_min_cases",
  "name" => "wc_stock_min_cases",
  "value" => Woocommerce_Stock_Packagen_Admin_Values::minium_cases()
));
Woocommerce_Integration_Components::input(array(
  "label" => "Min Cases of masks before out of stock",
  "id" => "wc_stock_min_cases_masks",
  "name" => "wc_stock_min_cases_masks",
  "value" => Woocommerce_Stock_Packagen_Admin_Values::minium_masks_cases()
));

Woocommerce_Integration_Components::button(array(
  "content" => "Save"
));
?>
<input type="hidden" name="integration-controller" value="wc_stock_integration"/>
</form>
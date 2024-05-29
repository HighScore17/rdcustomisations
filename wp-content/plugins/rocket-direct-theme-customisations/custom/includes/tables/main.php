<?php
require "ac_free_sample_deals.php";

function horizon_plugin_activation_create_tables() {
  ac_free_sample_deals_create_tables();
}
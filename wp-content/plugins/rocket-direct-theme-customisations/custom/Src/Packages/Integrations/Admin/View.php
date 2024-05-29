
<?php
  if( isset( $_POST["integration-controller"] ) ) {
    do_action( 'rocket_integration_controller_for_' . $_POST["integration-controller"]);
  }

  $tabs = apply_filters( "rocket_integration_tabs", [] );

  $tab = isset($_GET["tab"], $tabs[$_GET["tab"]] ) ? $tabs[$_GET["tab"]] : null;

  if( !$tab && count( $tabs ) ) {
    $tab = array_values( $tabs )[0];
  }
?>

<script src="https://cdn.tailwindcss.com"></script>

  <?php require_once __DIR__ . "/Components.php" ?>
<div class="main-container">
  <?php require_once __DIR__ . "/Menu.php" ?>
  <div class="integration-content">
  <?php 
    if( $tab ) {
      call_user_func( $tab["render"] );
    }
  ?>
  </div>

</div>
<?php require_once __DIR__ . "/Styles.php" ?>
<?php 
  $tabs = $this->tabs;
  $default_tab = 'prices_options';
  $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'prices';

  function get_tab_active_class( $current_tab )
  {
    $active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'prices';
    if( $active_tab === $current_tab )
      return "nav-tab-active";
    return "";
  }

?>
<div class="wrap">
  <h2> Horizon Customizations </h2>
  <?php settings_errors(); ?>
  <!-- Tabs -->
  <h2 class="nav-tab-wrapper">
    <?php foreach( $tabs as $tab ): ?>
      <a href="?page=horizon_customisations&tab=<?php echo $tab["group"] ?>" class="nav-tab <?php echo get_tab_active_class($tab["group"]) ?>"> <?php echo $tab["name"] ?> </a>
    <?php endforeach; ?>
  </h2>
  <?php 
    foreach( $tabs as $tab ) {
      if( $tab["group"] === $active_tab )
      {
        if( $tab["type"]["code"] === "options" ) {
          echo "<form method=\"post\" action=\"options.php\">";
          settings_fields( $tab['group'] );
          do_settings_sections( 'horizon_customisations' );
          submit_button();
          echo "</form>";
        }
        else if( $tab["type"]["code"] === "custom_page" ) {
          require_once $tab["type"]["file"];
        }
        
        
      }
    }  
  ?>
  
</div>
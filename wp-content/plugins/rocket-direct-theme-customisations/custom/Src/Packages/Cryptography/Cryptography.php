
<?php
define('HORIZON_CRYPTOGRAPHY_VERSION', '1.0');

  if( 
  defined( 'DEFUSE_CRYPTO_PATH' ) && 
  file_exists( DEFUSE_CRYPTO_PATH ) && 
  is_readable( DEFUSE_CRYPTO_PATH ) 
) {
  require_once DEFUSE_CRYPTO_PATH;

  if( defined( 'DEFUSE_CRIPTO_PASSWORD' ) && !empty( DEFUSE_CRIPTO_PASSWORD ) ) {
    require_once __DIR__ . "/UserDataEncryptation.php";
    require_once __DIR__ . "/SystemDataEncryptation.php";
  } else {
    add_action( 'admin_notices', function() {
      echo '<div class="notice notice-error is-dismissible">
               <p>Defuse crypto password is not defined.</p>
           </div>';
    } );
  }
} else {
  add_action( 'admin_notices', function() {
    echo '<div class="notice notice-error is-dismissible">
             <p>Defuse crypto class is not defined / not exists / not readable.</p>
         </div>';
  } );
}

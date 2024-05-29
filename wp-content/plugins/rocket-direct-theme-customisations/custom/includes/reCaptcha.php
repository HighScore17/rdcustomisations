<?php
  define( "RECAPTCHA_V3_SECRET", "6LdXWHsaAAAAADDcNlF5wTUhruxckdoShdX6BzGa" );
  define( "HUMAN_MIN_SCORE", 0.3 );

  function validateReCaptcha( $reCaptcha )
  {
    $logger = wc_get_logger();
    if( $reCaptcha && !empty( $reCaptcha ) )
    {
      $response = wp_remote_post("https://www.google.com/recaptcha/api/siteverify", array(
        'body' => array(
          'secret' => RECAPTCHA_V3_SECRET,
          'response' => $reCaptcha
        )
      ));
      if( !is_wp_error( $response ) && is_array( $response ) )
      {
        $data = json_decode( $response['body'], true );
        if( $data["success"] && $data["score"] > HUMAN_MIN_SCORE )
        {
          return "success";
        }
        else{
          $logger->info("Google: " . wc_print_r( $data, true ), [ "source" => "recaptcha" ]);
          return "Invalid Recaptcha. Please try again. (" . $data["score"] . ")";
        }
      }else{
        return "Failed to validate reCaptcha.";
      }
    }
    else{
      return "Recaptcha field is empty.";
    }
  }
?>
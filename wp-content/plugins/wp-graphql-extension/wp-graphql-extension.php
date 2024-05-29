<?php
  /**
    * Plugin Name: WP Graphql Extension
    * Description: Add required extensions
    * Author:  Horizon trade solutions
    * Version: 0.1
  */

  use GraphQL\Error\UserError;

  add_filter('graphql_input_fields', function($input_fields, $type_name) {
    if ( $type_name === "RegisterUserInput" || $type_name === "RegisterCustomerInput" ) {
      $input_fields['reCaptcha'] = [
        'type' => 'String',
        'description' => __('Google ReCaptcha token', 'wp-graphql'),
      ];
    }
    return $input_fields;
  }, 10, 2);

  $reCaptchaChecked = FALSE;
  add_action( 'graphql_before_resolve_field', function( $source, $args, $context, $info, $field_resolver, $type_name, $field_key, $field ) {
    global $reCaptchaChecked;
    if ( ( $field_key === "registerUser" || $field_key === "registerCustomer" ) && !$reCaptchaChecked) {
      $reCaptcha = $args["input"]["reCaptcha"];
      if( $reCaptcha && !empty( $reCaptcha ) )
      {
        $response = wp_remote_post("https://www.google.com/recaptcha/api/siteverify", array(
          'body' => array(
            'secret' => '6LdXWHsaAAAAADDcNlF5wTUhruxckdoShdX6BzGa',
            'response' => $reCaptcha
          )
        ));
        if( !is_wp_error( $response ) && is_array($response) )
        {
          $data = json_decode( $response['body'], true );
          if( $data["success"] && $data["score"] > 0.3 )
          {
            $reCaptchaChecked = TRUE;
            return;
          }
          else{
            throw new UserError("We aren't secure that you are a human. Please try again.");
          }
        }else{
          throw new UserError("Failed to request Google ReCaptcha token.");
        }
      }
      else{
        throw new UserError("The Google ReCaptcha field is required.");
      }
    }
  }, 10, 9 );
?>
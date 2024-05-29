<?php

function check_if_user_account_is_active($user, $username, $password ) {
  $allowed_roles = array('administrator');
  
  if( is_wp_error($user) ) {
    return $user;
  }

  if( (!array_intersect($allowed_roles, $user->roles) && get_user_meta($user->ID, 'active_email_account', TRUE) !== "1") ) {
    return new WP_Error('inactive_account', 'Your Account is not active');
  }
  
  return $user;
}
add_filter('authenticate', 'check_if_user_account_is_active', 20, 3);

//Activate account mutation
register_graphql_mutation('activateAccount', array(
  'inputFields' => array(
    'email' => array(
      'type' => 'String'
    ),
    'code' => array(
      'type' => 'String'
    ),
  ),
  'outputFields' => array(
    'activated' => array(
      'type' => 'Boolean'
    )
  ),
  'mutateAndGetPayload' => function($input, $context, $info) {
    $activated = false;
    if( isset( $input["email"], $input["code"] ) ) {
      $user = get_user_by('email', $input["email"]);
      if(
        $user && 
        get_user_meta($user->ID, 'active_email_code', TRUE) === $input["code"] &&
        get_user_meta($user->ID, 'active_email_account', TRUE) !== "1"
      ) {
        update_user_meta($user->ID, 'active_email_account', '1');
        do_action('account_activation', $user->ID);
        $activated = true;
      }
    }
    return array(
      'activated' => $activated
    );
  }
));
<?php

function foreach_paginated_posts( $callback, $post_type = 'post', $post_status = array('publish'), $per_page = 5, $page = 1 ) {
  $args = array( 
    'posts_per_page' => $per_page,
    'paged' => $page,
    'post_type' => $post_type,
    'post_status' => $post_status,
    'category' => 0,
  );
  $postslist = new WP_Query($args);
  if ( $postslist->have_posts() ) {
    while ( $postslist->have_posts() ) {
      $postslist->the_post();
      $callback( get_the_ID() );
    }
    wp_reset_postdata();
    foreach_paginated_posts($callback, $post_type, $post_status, $per_page, $page + 1 );
  } else {
    return;
  }

  $postslist->query( $args );
}
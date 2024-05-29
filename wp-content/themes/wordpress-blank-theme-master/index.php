<?php



         ?> <h4> <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
        //get the current product ID
        $product_id  = $cart_item['product_id'];
        //first - check if product has variations
        if(isset($cart_item['variation']) && count($cart_item['variation']) > 0 ){
            //get the WooCommerce Product object by product ID
            $current_product = new  WC_Product_Variable($product_id);
            //get the variations of this product
            $variations = $current_product->get_available_variations();
            //Loop through each variation to get its title
            foreach($variations as $index => $data){

                $variation_attr  = $data['attributes']['attribute_pa_presentation'];
                if ($variation_attr  === "case" && $cart_item['quantity'] >= "5"){
                    $var = "valido";
                }
                else {
                    $var = "no es";
                }
            }
        }
    } var_dump($var); ?> </h4> <?php

?> <h3><?php

$coupon_posts = get_posts( array(
    'posts_per_page'   => -1,
    'orderby'          => 'name',
    'order'            => 'asc',
    'post_type'        => 'shop_coupon',
    'post_status'      => 'publish',
) );

$coupon_codes = []; // Initializing
$coupon = new WC_Coupon();
$meta = get_post_meta("min_boxes_qty");

var_dump($meta);
?></h3><?php




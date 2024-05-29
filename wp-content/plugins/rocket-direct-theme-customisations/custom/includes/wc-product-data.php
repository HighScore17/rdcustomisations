<?php
    add_action( 'cmb2_admin_init', 'hc_register_product_data_metabox' );
    /**
        * Hook in and register a metabox for the admin comment edit page.
        */
    function hc_register_product_data_metabox() {
        /**
            * Sample metabox to demonstrate each field type included
            */
        $cmb = new_cmb2_box( array(
            'id'           => 'yourprefix_comment_metabox',
            'title'        => 'Product Details',
            'object_types' => array( 'product' ),
        ) );
    
        $cmb->add_field( array(
            'name' => 'Datasheet',
            'desc' => 'Pdf Datasheet ',
            'id'   => 'datasheet',
            'type' => 'file',
            'column' => array(
                'position' => 1,
            ),
        ) );

        $cmb->add_field( array(
            'name' => 'Guarantee',
            'id'   => 'guarantee',
            'type' => 'text',
            'column' => array(
                'position' => 2,
                'name' => 'CMB2 Custom Column2',
            ),
        ) );
    
        $cmb->add_field( array(
            'name'    => 'Specifications',
            'desc'    => 'name ~ value',
            'id'      => 'specifications',
            'type'    => 'textarea',
        ) );

        $cmb->add_field( array(
            'name'    => 'Presentations',
            'desc'    => 'name ~ image',
            'id'      => 'product_presentations',
            'type'    => 'textarea',
        ) );

        $cmb->add_field( array(
            'name'    => 'Show Product Prices',
            'id'      => 'product_show_prices',
            'type'    => 'radio_inline',
            'options' => array(
                'show' => __( 'Show Prices', 'cmb2' ),
                'hide'   => __( 'Hide Prices', 'cmb2' ),
            ),
            'default' => 'hide',
        ) );

        $cmb->add_field( array(
            'name'    => 'Prices actions',
            'id'      => 'product_prices_actions',
            'type'    => 'multicheck',
            'options' => array(
                'show_prices' => __( 'Show Prices', 'cmb2' ),
                'allow_add_to_cart'   => __( 'Allow add to cart', 'cmb2' ),
            ),
            'default' => 'hide',
        ) );

        $cmb->add_field( array(
            'name'    => 'Tier titles',
            'id'      => 'product_tier_table_titles',
            'type'    => 'checkbox',
            'desc'    => 'Use primary tier as title',
        ) );

        //Seo
        $cmb = new_cmb2_box( array(
            'id'           => 'rd_seo_meta',
            'title'        => 'SEO',
            'object_types' => array( 'product' ),
        ) );
    
        $cmb->add_field( array(
            'name' => 'SEO Title',
            'id'   => 'rd_seo_title',
            'type' => 'text',
            'column' => array(
                'position' => 1,
            ),
        ) );

        $cmb->add_field( array(
            'name' => 'SEO Description',
            'id'   => 'rd_seo_description',
            'type' => 'textarea_small',
            'column' => array(
                'position' => 1,
            ),
        ) );

        $cmb->add_field( array(
          'name' => 'SEO Keywords',
          'id'   => 'rd_seo_keywords',
          'type' => 'textarea_small',
          'column' => array(
              'position' => 1,
          ),
      ) );

        $cmb->add_field( array(
            'name' => 'SEO Image',
            'id'   => 'rd_seo_image',
            'type' => 'file',
            'column' => array(
                'position' => 1,
            ),
            'query_args' => array(
                'type' => array(
                    'image/gif',
                    'image/jpeg',
                    'image/png',
                ),
            ),
        ) );


        $cmb = new_cmb2_box( array(
            'id'           => 'product_marketing_metabox',
            'title'        => 'SEO',
            'object_types' => array( 'product' ),
        ) );
    
        $cmb->add_field( array(
            'name' => 'Marketing Strategy',
            'id'   => 'product_marketing_strategy',
            'type' => 'text',
            'column' => array(
                'position' => 1,
            ),
        ) );

        $cmb = new_cmb2_box( array(
            'id'           => 'product_shipment_details_metabox',
            'title'        => 'Shipment Details',
            'object_types' => array( 'product' ),
            'context' => 'side',
        ) );
    
        $cmb->add_field( array(
            'name' => 'Shipment Category',
            'id'   => 'product_shipment_details_category',
            'type' => 'select',
            'options' => array(
                'mask' => __('MASK', 'cmb2'),
                'glove' => __('GLOVE AS', 'cmb2'),
                'glove-kingfa' => __('GLOVE KINGFA', 'cmb2')
            )
        ) );
        $cmb->add_field( array(
            'name' => 'Shipment ID',
            'id'   => 'product_shipment_details_id',
            'type' => 'text',
        ) );

    }

    /**
     * Add extra input field to variation
     */

    add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );
    add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );
    add_filter( 'woocommerce_available_variation', 'load_variation_settings_fields' );

    function variation_settings_fields( $loop, $variation_data, $variation ) {
        woocommerce_wp_text_input(
            array(
                'id'            => "contains_item{$loop}",
                'name'          => "contains_item[{$loop}]",
                'value'         => get_post_meta( $variation->ID, 'contains_item', true ),
                'label'         => __( 'How many items contains', 'woocommerce' ),
                'type'          => 'number',
                'desc_tip'      => true,
                'description'   => __( 'The numer of pieces that has', 'woocommerce' ),
                'wrapper_class' => 'form-row form-row-full',
            )
        );

        woocommerce_wp_textarea_input(
            array(
                'id'            => "tiers_range{$loop}",
                'name'          => "tiers_range[{$loop}]",
                'value'         => get_post_meta( $variation->ID, 'tiers_range', true ),
                'label'         => __( 'Tiers range', 'woocommerce' ),
                'rows'          => 6,
                'wrapper_class' => 'form-row form-row-full',
            )
        );
    }

    function save_variation_settings_fields( $variation_id, $loop ) {
        $text_field = $_POST['contains_item'][ $loop ];

        if ( ! empty( $text_field ) ) {
            update_post_meta( $variation_id, 'contains_item', esc_attr( $text_field ));
        }
    }

    function load_variation_settings_fields( $variation ) {     
        $variation['contains_item'] = get_post_meta( $variation[ 'variation_id' ], 'contains_item', true );
        return $variation;
    }

    

    add_action( 'graphql_register_types', function() {
        register_graphql_field( 'ProductVariation', 'containQty',  [
            'type' => 'String',
            'resolve' => function( $source, $args, $context, $info ){ 
                return get_post_meta( $source->fields["ID"], 'contains_item', true );
            }
        ]);
        register_graphql_field( 'ProductVariation', 'tiersRange',  [
            'type' => 'String',
            'resolve' => function( $source, $args, $context, $info ){ 
                return get_post_meta( $source->fields["ID"], 'tiers_range', true );
            }
        ]);
        register_graphql_field( 'Product', 'showPrices',  [
            'type' => 'String',
            'resolve' => function( $source, $args, $context, $info ){
                return get_post_meta( $source->fields["ID"], 'product_show_prices', true ) === "show" ? "1" : "0";
            }
        ]);

        register_graphql_field( 'Product', 'allowedActions',  [
            'type' => array(
                'list_of' => 'String'
            ),
            'resolve' => function( $source, $args, $context, $info ){
                $allowed_actions = get_post_meta( $source->fields["ID"], 'product_prices_actions');
                if( is_array( $allowed_actions ) )
                    return $allowed_actions[0]; 
                return array();
            }
        ]);

        register_graphql_field( 'Product', 'customTier',  [
            'type' => 'String',
            'resolve' => function( $source, $args, $context, $info ){
                return get_post_meta( $source->fields["ID"], 'product_tier_table_titles', true );
            }
        ]);

        register_graphql_object_type( 'SEO', array(
          'fields' => array(
            'title' => array(
              'type' => 'string'
            ),
            'description' => array(
              'type' => 'string'
            ),
            'keywords' => array(
              'type' => 'string'
            ),
            'image' => array(
              'type' => 'string'
            ),
          )
        ) );

        register_graphql_field( 'Product', 'seo',  [
            'type' => 'SEO',
            'resolve' => function( $source, $args, $context, $info ){ 
                return array(
                  'title' => get_post_meta( $source->fields["ID"], 'rd_seo_title', true ), 
                  'description' => get_post_meta( $source->fields["ID"], 'rd_seo_description', true ),
                  'keywords' => get_post_meta( $source->fields["ID"], 'rd_seo_keywords', true ),
                  'image' => get_post_meta( $source->fields["ID"], 'rd_seo_image', true ),
                );
            }
        ]);

      });
      

    add_filter( 'wc_get_price_decimals', 'change_prices_decimals', 20, 1 );
function change_prices_decimals( $decimals ){
    if( is_cart() || is_checkout() )
        $decimals = 2;
    return $decimals;
}
?>

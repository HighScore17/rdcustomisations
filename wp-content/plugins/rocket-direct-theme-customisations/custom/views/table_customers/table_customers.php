<?php

add_action('woocommerce_settings_tabs', 'wc_settings_tabs_customer_list_tab');
function wc_settings_tabs_customer_list_tab()
{
    $current_tab = (isset($_GET['tab']) && $_GET['tab'] === 'customer_list') ? 'nav-tab-active' : '';
    echo '<a href="admin.php?page=wc-settings&tab=customer_list" class="nav-tab ' . $current_tab . '">' . __("Lista de Clientes", "woocommerce") . '</a>';
}

// The setting tab content
add_action('woocommerce_settings_customer_list', 'display_customer_list_tab_content');
function display_customer_list_tab_content($order_id)
{
    global $wpdb;
    $order = wc_get_order( $order_id );

    // Styling the table a bit
    echo '<style> table.user-data th { font-weight: bold; } table.user-data, th, td { border: solid 1px #999; } </style>';

    $table_display = '<table class="user-data" cellspacing="0" cellpadding="6"><thead><tr>
    <th>' . __('ID', 'woocommerce') . '</th>
    <th>' . __('Nombre', 'woocommerce') . '</th>
    <th>' . __('Apellido', 'woocommerce') . '</th>
    <th>' . __('Address', 'woocommerce') . '</th>
    <th>' . __('ZIP Code', 'woocommerce') . '</th>
    <th>' . __('City', 'woocommerce') . '</th>
    <th>' . __('Phone', 'woocommerce') . '</th>
    <th>' . __('Email', 'woocommerce') . '</th>
    <th>' . __('Amount', 'woocommerce') . '</th>
    <th>' . __('Purchases', 'woocommerce') . '</th>
    <th>' . __('Last order', 'woocommerce') . '</th>
    <th>' . __('Brand', 'woocommerce') . '</th>
    </tr></thead>
    <tbody>';

    // Loop through customers
    foreach (get_users() as $key => $customer) {
        // Customer total purchased
        $total_purchased = (float)$wpdb->get_var("
            SELECT SUM(pm.meta_value) FROM {$wpdb->prefix}postmeta as pm
            INNER JOIN {$wpdb->prefix}posts as p ON pm.post_id = p.ID
            INNER JOIN {$wpdb->prefix}postmeta as pm2 ON pm.post_id = pm2.post_id
            WHERE p.post_status = 'wc-completed' AND p.post_type = 'shop_order'
            AND pm.meta_key = '_order_total' AND pm2.meta_key = '_customer_user'
            AND pm2.meta_value = {$customer->ID}
        ");
        // Customer orders count
        $orders_count = (int)$wpdb->get_var("
            SELECT DISTINCT COUNT(p.ID) FROM {$wpdb->prefix}posts as p
            INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order' AND pm.meta_key = '_customer_user'
            AND pm.meta_value = {$customer->ID}
        ");
        // Customer last order ID
        $last_order_id = (int)$wpdb->get_var("
            SELECT MAX(p.ID) FROM {$wpdb->prefix}posts as p
            INNER JOIN {$wpdb->prefix}postmeta as pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order' AND pm.meta_key = '_customer_user'
            AND pm.meta_value = {$customer->ID}
        ");

        $user_link = 'user-edit.php?user_id=' . $customer->ID;
        $last_order_link = 'post.php?post=' . $last_order_id . '&action=edit';

        $table_display .= '<tr>
        <td align="center"><a href="' . $user_link . '">' . esc_attr($customer->ID) . '</a></td>
        <td>' . esc_html($customer->first_name) . '</td>
        <td>' . esc_html($customer->last_name) . '</td>
        <td>' . esc_html($customer->billing_address_1) . '</td>
        <td>' . esc_attr($customer->billing_postcode) . '</td>
        <td>' . esc_attr($customer->billing_city) . '</td>
        <td>' . esc_attr($customer->billing_phone) . '</td>
        <td><a href="mailto:' . $customer->billing_email . '">' . esc_attr($customer->billing_email) . '</a></td>
        <td align="right">' . ($total_purchased > 0 ? wc_price($total_purchased) : ' - ') . '</td>
        <td align="center">' . $orders_count . '</td>
        <td align="center"><a href="' . $last_order_link . '">' . ($last_order_id > 0 ? $last_order_id : ' - ') . '</a></td>
        <td>' . wc_horizon_get_brand_name() . '</td>
        </tr>';
    }
    // Output the table
    echo $table_display . '</tbody></table>';
}
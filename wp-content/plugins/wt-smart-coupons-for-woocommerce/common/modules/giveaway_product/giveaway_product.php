<?php
/**
 * Giveaway product admin/public section
 *
 * @link       
 * @since 1.3.9   
 *
 * @package  Wt_Smart_Coupon
 */
if (!defined('ABSPATH')) {
    exit;
}

class Wt_Smart_Coupon_Giveaway_Product
{
    public $module_base='giveaway_product';
    public $module_id='';
    public static $module_id_static='';
    private static $instance = null;
    public static $bogo_coupon_type_name='wt_sc_bogo'; /* bogo coupon type name */
    public static $meta_arr=array();
    public function __construct()
    {
        $this->module_id=Wt_Smart_Coupon::get_module_id($this->module_base);
        self::$module_id_static=$this->module_id;

        add_filter('woocommerce_coupon_discount_types', array($this, 'add_bogo_coupon_type'));
    }

    /**
     * Get Instance
    */
    public static function get_instance()
    {
        if(self::$instance==null)
        {
            self::$instance=new Wt_Smart_Coupon_Giveaway_Product();
        }
        return self::$instance;
    }

    /**
     * Register BOGO coupon type
     * @since 1.3.9
     */
    public function add_bogo_coupon_type($discount_types)
    {
        $discount_types[self::$bogo_coupon_type_name] = __('BOGO (Buy X Get X/Y) offer (Coming soon)', 'wt-smart-coupons-for-woocommerce-pro');
        return $discount_types;
    }

    /**
     * Is current coupon is BOGO.
     */
    public static function is_bogo($coupon)
    {
        return $coupon->is_type(self::$bogo_coupon_type_name);
    }

    
}
Wt_Smart_Coupon_Giveaway_Product::get_instance();
<?php

/**
 * The file that contains definitions for the Dependencies Container.
 *
 * The definitions laid out here are used by the Dependencies Container to fetch
 * the appropriate value/class/etc. If the value of the definition is callable,
 * the container will return the returned value from the callable.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/config
 */

use Activecampaign_For_Woocommerce_Add_Cart_Id_To_Order_Command as Add_Cart_Id_To_Order;
use Activecampaign_For_Woocommerce_Add_Accepts_Marketing_To_Customer_Meta_Command as Add_Accepts_Marketing_To_Customer_Meta;
use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Api_Client as Api_Client;
use Activecampaign_For_Woocommerce_Cart_Emptied_Event as Cart_Emptied;
use Activecampaign_For_Woocommerce_Cart_Updated_Event as Cart_Updated;
use Activecampaign_For_Woocommerce_Clear_User_Meta_Command as Clear_User_Meta_Command;
use Activecampaign_For_Woocommerce_Connection_Option_Repository as Connection_Option_Repository;
use Activecampaign_For_Woocommerce_Connection_Repository as Connection_Repository;
use Activecampaign_For_Woocommerce_Create_Or_Update_Connection_Option_Command as Create_Or_Update_Connection_Option_Command;
use Activecampaign_For_Woocommerce_Create_And_Save_Cart_Id_Command as Create_And_Save_Cart_Id;
use Activecampaign_For_Woocommerce_Delete_Cart_Id_Command as Delete_Cart_Id;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order_Factory as Ecom_Order_Factory;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_I18n as I18n;
use Activecampaign_For_Woocommerce_Loader as Loader;
use Activecampaign_For_Woocommerce_Logger as Log;
use Activecampaign_For_Woocommerce_Plugin_Upgrade_Command as Plugin_Upgrade_Command;
use Activecampaign_For_Woocommerce_Public as AC_Public;
use Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command as Run_Abandonment_Sync_Command;
use Activecampaign_For_Woocommerce_Set_Connection_Id_Cache_Command as Set_Connection_Id_Cache_Command;
use Activecampaign_For_Woocommerce_Update_Cart_Command as Update_Cart_Command;
use Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command as Sync_Guest_Abandoned_Cart_Command;
use Activecampaign_For_Woocommerce_Order_Finished_Event as Order_Finished;
use Activecampaign_For_Woocommerce_User_Registered_Event as User_Registered;
use AcVendor\Psr\Container\ContainerInterface;
use AcVendor\Psr\Log\LoggerInterface;

return array(
	Activecampaign_For_Woocommerce::class             => function (
		Loader $loader,
		Admin $admin,
		AC_Public $public,
		I18n $i18n,
		LoggerInterface $logger,
		Cart_Updated $cart_updated_event,
		Cart_Emptied $cart_emptied_event,
		Set_Connection_Id_Cache_Command $set_connection_id_cache_command,
		Create_Or_Update_Connection_Option_Command $c_or_u_co_command,
		Create_And_Save_Cart_Id $create_and_save_cart_id_command,
		Update_Cart_Command $update_cart_command,
		Delete_Cart_Id $delete_cart_id_command,
		Add_Cart_Id_To_Order $add_cart_id_to_order_command,
		Add_Accepts_Marketing_To_Customer_Meta $add_am_to_meta_command,
		Clear_User_Meta_Command $clear_user_meta_command,
		Sync_Guest_Abandoned_Cart_Command $sync_guest_abandoned_cart_command,
		Order_Finished $order_finished_event,
		User_Registered $user_registered_event,
		Run_Abandonment_Sync_Command $run_abandonment_sync_command,
		Plugin_Upgrade_Command $plugin_upgrade_command
	) {
		$version = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION :
			'1.0.0';

		$plugin_name = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB :
			'activecampaign-for-woocommerce';

		return new Activecampaign_For_Woocommerce(
			$version,
			$plugin_name,
			$loader,
			$admin,
			$public,
			$i18n,
			$logger,
			$cart_updated_event,
			$cart_emptied_event,
			$set_connection_id_cache_command,
			$c_or_u_co_command,
			$create_and_save_cart_id_command,
			$update_cart_command,
			$delete_cart_id_command,
			$add_cart_id_to_order_command,
			$add_am_to_meta_command,
			$clear_user_meta_command,
			$sync_guest_abandoned_cart_command,
			$order_finished_event,
			$user_registered_event,
			$run_abandonment_sync_command,
			$plugin_upgrade_command
		);
	},

	Add_Accepts_Marketing_To_Customer_Meta::class     => function ( LoggerInterface $logger ) {
		return new Add_Accepts_Marketing_To_Customer_Meta( $logger );
	},

	Admin::class                                      => function ( ContainerInterface $c ) {
		$version = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION :
			'1.0.0';

		$plugin_name = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB' ) ?
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB :
			'activecampaign-for-woocommerce';

		$validator = $c->get( Activecampaign_For_Woocommerce_Admin_Settings_Validator::class );

		$event = new Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event();

		return new Admin( $plugin_name, $version, $validator, $event );
	},

	Api_Client::class                                 => function ( LoggerInterface $logger ) {
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );

		$api_uri = isset( $settings['api_url'] ) ? $settings['api_url'] : null;
		$api_key = isset( $settings['api_key'] ) ? $settings['api_key'] : null;

		return new Api_Client( $api_uri, $api_key, $logger );
	},

	AC_Public::class                                  => function ( Admin $admin ) {
		$version = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION' ) ? ACTIVECAMPAIGN_FOR_WOOCOMMERCE_VERSION : '1.0.0';

		$plugin_name = defined( 'ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB' ) ? ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB : 'activecampaign-for-woocommerce';

		return new AC_Public( $plugin_name, $version, $admin );
	},

	Create_And_Save_Cart_Id::class                    => function ( LoggerInterface $logger ) {
		return new Create_And_Save_Cart_Id( $logger );
	},

	Create_Or_Update_Connection_Option_Command::class => function (
		Admin $admin,
		Connection_Option_Repository $repository,
		LoggerInterface $logger
	) {
		return new Create_Or_Update_Connection_Option_Command( $admin, $repository, null, $logger );
	},

	Update_Cart_Command::class                        => function (
		Admin $admin,
		Ecom_Order_Factory $factory,
		Ecom_Order_Repository $order_repository,
		Ecom_Customer_Repository $customer_repository,
		LoggerInterface $logger
	) {
		return new Update_Cart_Command(
			null,
			null,
			$admin,
			$factory,
			$order_repository,
			$customer_repository,
			$logger
		);
	},

	Set_Connection_Id_Cache_Command::class            => function (
		Admin $admin,
		Connection_Repository $connection_repository,
		LoggerInterface $logger
	) {
		return new Set_Connection_Id_Cache_Command( $admin, $connection_repository, $logger );
	},

	Sync_Guest_Abandoned_Cart_Command::class          => function (
		Admin $admin,
		Ecom_Customer_Repository $customer_repository
	) {
		return new Sync_Guest_Abandoned_Cart_Command(
			null,
			null,
			null,
			$admin,
			$customer_repository
		);
	},

	Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command::class => function (
		LoggerInterface $logger
	) {
		return new Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command( $logger );
	},

	LoggerInterface::class                            => AcVendor\DI\factory(
		function () {
			return new Log();
		}
	),

);

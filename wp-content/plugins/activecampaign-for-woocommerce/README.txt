=== Plugin Name ===
Contributors: acteamintegrations, bartboy011
Tags: marketing, ecommerce, woocommerce, email, activecampaign, abandoned cart
Requires at least: 4.7
Tested up to: 5.8
Stable tag: 1.4.9
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

ActiveCampaign for WooCommerce enables you to create abandoned cart automations and send emails to your contacts who abandon carts.

== Description ==

ActiveCampaign for WooCommerce automatically syncs your customers and their purchase data into ActiveCampaign, including abandoned carts and whether or not the customer opted-in to marketing.

ActiveCampaign for WooCommerce gives you the power to:
- Sync all customers and their purchase data into ActiveCampaign in real time
- Configure how long until a cart should be considered abandoned
- Provide an opt-in checkbox on your checkout form for customers to opt-in to marketing
- Configure what the opt-in checkbox says and if it's checked by default
- Trigger automations when a customer abandons a cart

ActiveCampaign's category-defining customer experience automation (CXA) platform helps over 130,000 businesses in 170 countries meaningfully engage with their customers. The platform gives businesses of all sizes access to hundreds of pre-built automations that combine email marketing, marketing automation, CRM, and machine learning for powerful segmentation and personalization across social, email, messaging, chat, and text.

By removing the silos that typically exist between email marketing, marketing automation, CRM, and account management solutions, businesses can automate truly personalized experiences that feel authentic. Over 70% of ActiveCampaign's customers use its 300+ integrations including WooCommerce, Square, Facebook, Eventbrite, Wordpress and Salesforce.

== Installation ==

Before You Start
- Our plugin requires you to have the WooCommerce plugin installed and activated in WordPress.
- Your hosting environment should meet WooCommerce's minimum requirements, including PHP 7.0 or greater.

Installation
1. In your ActiveCampaign account, navigate to Settings.
2. Click the Integrations tab.
3. If your WooCommerce store is already listed here, skip to step 7. Otherwise, continue to step 4.
4. Click the "Add Integration" button.
5. Enter the URL of your WooCommerce site.
6. Follow the connection process that appears in WooCommerce.
7. In your WooCommerce store, install the "ActiveCampaign for WooCommerce" plugin and activate it.
8. Navigate to the plugin settings page (Settings > ActiveCampaign for WooCommerce)
9. Enter your ActiveCampaign API URL and API Key in the provided boxes.
10. Click "Update Settings".

== Changelog ==

= 1.4.9 =
* Updated activation and setup to make configuration easier
* Bugfix for numbers with more than 2 decimals & concat error in the stack trace

= 1.4.8 =
* Bugfix for WooCommerce orders showing as duplicates in ActiveCampaign

= 1.4.7 =
* Various bug fixes and logging improvements

= 1.4.6 =
* Adds advanced helper buttons to debug connection issues and clear settings without uninstalling the plugin
* Bugfix for zero total orders that fail validation but are supposed to be zero

= 1.4.5 =
* Adds a total value check fallback to double check we have the order total.
* Adds validation of order before sending to ActiveCampaign.
* Feature change for debug mode: debug messages will no longer post to the logs if debug mode in the plugin settings is off. Reduces log waste.
* Bugfix to stop record mismatches when looking up an existing order in ActiveCampaign.
* Bugfix for UUID reset happening at the wrong time causing some finished orders to record as abandoned carts.

= 1.4.4 =
* Bugfix to check if undefined functions wc_admin_url() & wp_get_scheduled_event() are available.
* Bugfix for array_walk issue to make sure the WC_Order is not set to null during checkout.
* Bugfix for get() on null error on order processing.
* Bugfix for zero dollar totals
* Bugfix for error on dropping the table on deletion of the plugin
* Adds catches for more error conditions

= 1.4.3 =
* Bugfix for array_walk error.
* Adding more error handling.
* Extending the connection timeout so customers with slower connection to ActiveCampaign can send data.

= 1.4.2 =
* Bugfix for abandoned cart report page errors
* Allowing admin to force sync an abandoned cart row

= 1.4.1 =
* Plugin now surfaces errors to admin with a dismissible notice.
* You can find a list of the last 10 errors related to this plugin in this plugin's status tab to help quickly identify problems.
* Added an abandon cart manual sync button to allow manual re-running of the abandoned cart sync process.
* Added the ability to delete individual rows from the abandoned cart page.

= 1.4.0 =
* New menu item for ActiveCampaign features.
* Guest abandoned carts now sync as a background process creating more reliable data in ActiveCampaign.
* All abandoned carts are now synced on an hourly basis.
* New abandoned carts status page shows abandoned carts in WooCommerce and their current status.
* Contacts and phone numbers now properly sync to ActiveCampaign when orders are placed.
* Bugfix for various cases where "place order" hangs or errors and causes orders to not be placed.
* Bugfix for accepts marketing selection not always being set in ActiveCampaign.
* Bugfix for duplicate orders appearing in ActiveCampaign.
* Bugfix for orders marked as abandoned no longer create bad records as both abandoned and completed in ActiveCampaign.

= 1.3.6 =
* Bugfix for Normalizer fatal error issues, removing Normalizer dependency

= 1.3.5 =
* Fixes a bug surfaced by the 1.3.4 fix which caused an error when admin updates an order

= 1.3.4 =
* Fixing a bug that sent orders using external payment methods to ActiveCampaign (Paypal, Stripe) before the order was complete.

= 1.3.3 =
* Resolving a javascript error with the copy to clipboard function
* Adds the connection id output to the status tab
* Bugfix for adding all categories to the abandoned cart product send
* Adding a new process to manage sync of abandoned carts for registered users
* Adding more checks and safety points to keep orders from failing to process
* Resolving a bug that stops orders from finishing

= 1.3.2 =
* Bugfix for logger error when the plugin isn't configured properly
* Bugfix for checkbox configuration issues
* Adding a vendor library to always format money properly when sending to AC
* Adding a missing package that was causing fatal errors on some installs
* Add a new customer to ActiveCampaign on registration, also adds a synced to AC time to metadata for users/contacts

= 1.3.1 =
* Adding an API test button to verify that the connection to the customer's ActiveCampaign account is valid without needing to save
* Bugfix adding a check to verify the orderProducts exists and is an array.
* Bugfix for request errors when the plugin is not configured or there are settings issues
* Bugfix for orders not completing and 500 errors when users try to place an order
* Bugfix for Javascript syntax errors on iPhone/Safari devices

= 1.3.0 =
* The admin settings link on the left menu has moved from Settings to WooCommerce
* New feature - Adding functionality to send orders to AC on order completed instead of waiting for webhooks (webhooks will still run in case this process fails)
* Upon sending to AC a metadata field will be added to orders to track last sync time for that order to ActiveCampaign
* New feature - Adding an advanced admin field to customize the email ID we bind on for abandoned cart
* New feature - Adding an admin status page

= 1.2.16 =
* Bugfix for abandoned carts not sending product image, sku, or url

= 1.2.15 =
* Updated description copy
* Updated WooCommerce compatibility version tested

= 1.2.14 =
* Bugfix for repeat guest orders not syncing to ActiveCampaign
* Fix for abandoned carts not being sent to ActiveCampaign due to checkout ID conflicts
* Adds error logging for marketing checkbox issues
* Updating compatibility reference for Wordpress 5.6 and WooCommerce 4.8

= 1.2.13 =
* Resolving errors resulting from files missing in the package

= 1.2.12 =
* Updating compatibility documentation

= 1.2.11 =
* Fix bug with abandoned cart when there is no logged in customer
* Improve logging

= 1.2.10 =
* Upgrade Guzzle for bug fixes
* Fix email validation
* Send first and last name to guest abandoned carts

= 1.2.9 =
* Improve nonce validation

= 1.2.8 =
* Register plugin with version number for cache busting purposes

= 1.2.7 =
* Fixed incompatibility with Aero Checkout plugin

= 1.2.6 =
* Update Guzzle

= 1.2.5 =
* Added more info to logs. Fixed imports and doc blocks.

= 1.2.4 =
* Added WooCommerce version check

= 1.2.3 =
* Prevent erroneous abandoned carts

= 1.2.2 =
* Prevent vendor package collisions with other plugins
* Increased error logging for easier debugging

= 1.2.0 =
* Accepts Marketing for Guests
* Local setup and readme updates

= 1.1.0 =
* Added support for guest abandoned carts

= 1.0.3 =
* Prevent edgecase where updating the Abandoned Cart time causes an exception

= 1.0.2 =
* Allow Woocommerce API calls to work when Wordpress is behind a load balancer
* Fixed a bug where abandoned cart functionality would not work if an item had no categories

= 1.0.1 =
* Prevent exceptions from breaking WooCommerce cart functionality

= 1.0.0 =
* Initial Release

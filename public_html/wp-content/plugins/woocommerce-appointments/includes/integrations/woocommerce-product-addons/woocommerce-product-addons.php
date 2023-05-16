<?php
/**
 * Plugin Name: WooCommerce Product Add-ons
 * Plugin URI: https://woocommerce.com/products/product-add-ons/
 * Description: Add extra options to products which your customers can select from, when adding to the cart, with an optional fee for each extra option. Add-ons can be checkboxes, a select box, or custom text input.
 * Version: 3.7.0
 * Author: WooCommerce
 * Author URI: https://woocommerce.com
 * Requires at least: 3.8
 * Tested up to: 5.7
 * WC tested up to: 5.1
 * WC requires at least: 3.0
 * Text Domain: woocommerce-product-addons
 * Domain Path: /languages/
 * Copyright: © 2021 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Files.FileName

// Start here when no conflict detected.
add_action( 'init', 'woocommerce_appointments_product_addons_init', 99 );
function woocommerce_appointments_product_addons_init() {

	if ( is_woocommerce_active() && ! class_exists( 'WC_Product_Addons' ) ) :

		define( 'WC_PRODUCT_ADDONS_VERSION', '3.7.0' );
		define( 'WC_PRODUCT_ADDONS_MAIN_FILE', __FILE__ );
		define( 'WC_PRODUCT_ADDONS_PLUGIN_URL', WC_APPOINTMENTS_PLUGIN_URL . '/includes/integrations/woocommerce-product-addons' );
		define( 'WC_PRODUCT_ADDONS_PLUGIN_PATH', WC_APPOINTMENTS_ABSPATH . 'includes/integrations/woocommerce-product-addons' );

		/**
		 * Main class.
		 */
		class WC_Product_Addons {

			protected $groups_controller;

			/**
			 * Constructor.
			 */
			public function __construct() {
				$this->init();
				add_action( 'init', array( $this, 'init_post_types' ), 20 );
				add_action( 'init', array( 'WC_Product_Addons_install', 'init' ) );
				add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
			}

			/**
			 * Initializes plugin classes.
			 *
			 * @version 2.9.0
			 */
			public function init() {
				require_once( dirname( __FILE__ ) . '/includes/class-wc-product-addons-helper.php' );

				// Pre 3.0 conversion helper to be remove in future.
				require_once( dirname( __FILE__ ) . '/includes/updates/class-wc-product-addons-3-0-conversion-helper.php' );

				require_once( dirname( __FILE__ ) . '/includes/class-wc-product-addons-install.php' );

				// Core (models)
				require_once( dirname( __FILE__ ) . '/includes/groups/class-wc-product-addons-group-validator.php' );
				require_once( dirname( __FILE__ ) . '/includes/groups/class-wc-product-addons-global-group.php' );
				require_once( dirname( __FILE__ ) . '/includes/groups/class-wc-product-addons-product-group.php' );
				require_once( dirname( __FILE__ ) . '/includes/groups/class-wc-product-addons-groups.php' );

				// Admin
				if ( is_admin() ) {
					require_once( dirname( __FILE__ ) . '/includes/admin/class-wc-product-addons-admin.php' );

					$GLOBALS['Product_Addon_Admin'] = new WC_Product_Addons_Admin();
				}

				// Handle WooCommerce 3.0 compatibility.
				require_once( dirname( __FILE__ ) . '/includes/class-wc-product-addons-display.php' );
				require_once( dirname( __FILE__ ) . '/includes/class-wc-product-addons-cart.php' );
				require_once( dirname( __FILE__ ) . '/includes/class-wc-product-addons-ajax.php' );

				$GLOBALS['Product_Addon_Display'] = new WC_Product_Addons_Display();
				$GLOBALS['Product_Addon_Cart']    = new WC_Product_Addons_Cart();
				new WC_Product_Addons_Cart_Ajax();
			}

			/**
			 * Init post types used for addons.
			 */
			public function init_post_types() {
				register_post_type(
					'global_product_addon',
					array(
						'public'              => false,
						'show_ui'             => false,
						'capability_type'     => 'product',
						'map_meta_cap'        => true,
						'publicly_queryable'  => false,
						'exclude_from_search' => true,
						'hierarchical'        => false,
						'rewrite'             => false,
						'query_var'           => false,
						'supports'            => array( 'title' ),
						'show_in_nav_menus'   => false,
					)
				);

				register_taxonomy_for_object_type( 'product_cat', 'global_product_addon' );
			}

			/**
			 * Initialize the REST API
			 *
			 * @since 2.9.0
			 * @param WP_Rest_Server $wp_rest_server
			 */
			public function rest_api_init( $wp_rest_server ) {
				require_once( dirname( __FILE__ ) . '/includes/api/wc-product-add-ons-groups-controller-v1.php' );
				$this->groups_controller = new WC_Product_Add_Ons_Groups_Controller();
				$this->groups_controller->register_routes();
			}

		}

		new WC_Product_Addons();

	endif;
}

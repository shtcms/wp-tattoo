<?php
/**
 * Plugin Name: WooCommerce Appointments
 * Plugin URI: https://bookingwp.com/plugins/woocommerce-appointments/
 * Description: Setup appointable products for WooCommerce
 * Version: 4.12.0
 * Tested up to: 5.7
 * Requires at least: 4.2
 * WC tested up to: 5.2
 * WC requires at least: 3.1
 * Author: BookingWP
 * Author URI: https://bookingwp.com
 *
 * Text Domain: woocommerce-appointments
 * Domain Path: /languages
 *
 * Copyright: © BookingWP.com
 * License: GNU General Public License v3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Files.FileName

/**
 * Required functions.
 */
if ( ! function_exists( 'bookingwp_queue_update' ) ) {
	require_once 'dependencies/wc-functions.php';
}

register_activation_hook( __FILE__, 'woocommerce_appointments_activate' );
function woocommerce_appointments_activate() {
	// Stop if woocommerce plugin is not active.
	if ( ! is_woocommerce_active() ) {
		return;
	}

	/* translators: %s: a link to new product screen */
	$notice_html = sprintf( __( 'Welcome to WooCommerce Appointments. <a href="%s" class="button button-primary">Add Appointable Products</a>', 'woocommerce-appointments' ), admin_url( 'post-new.php?post_type=product&appointable_product=1' ) );

	WC_Admin_Notices::add_custom_notice( 'woocommerce_appointments_activation', $notice_html );

	// Register the rewrite endpoint before permalinks are flushed.
	add_rewrite_endpoint( apply_filters( 'woocommerce_appointments_account_endpoint', 'appointments' ), EP_PAGES );

	// Flush Permalinks.
	flush_rewrite_rules();
}

if ( ! class_exists( 'WC_Appointments' ) ) :
	define( 'WC_APPOINTMENTS_VERSION', '4.12.0' ); // WRCS: DEFINED_VERSION.
	define( 'WC_APPOINTMENTS_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
	define( 'WC_APPOINTMENTS_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
	define( 'WC_APPOINTMENTS_MAIN_FILE', __FILE__ );
	define( 'WC_APPOINTMENTS_ABSPATH', dirname( __FILE__ ) . '/' );
	if ( ! defined( 'WC_APPOINTMENTS_DEBUG' ) ) {
		define( 'WC_APPOINTMENTS_DEBUG', false );
	}

	/**
	 * Load Action Scheduler.
	 *
	 * @since 4.0.1
	 */
	require_once WC_APPOINTMENTS_ABSPATH . 'includes/lib/action-scheduler/action-scheduler.php';

	/**
	 * WC Appointments class.
	 */
	class WC_Appointments {

		/**
		 * @var WC_Appointments The single instance of the class.
		 */
		protected static $_instance = null;

		/**
		 * Main WooCommerce Appointments Instance.
		 *
		 * Ensures only one instance of WooCommerce Appointments is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return WC_Appointments - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @since 4.3.4
		 */
		public function __clone() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'woocommerce-appointments' ), WC_APPOINTMENTS_VERSION );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 *
		 * @since 4.3.4
		 */
		public function __wakeup() {
			wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'woocommerce-appointments' ), WC_APPOINTMENTS_VERSION );
		}

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->includes();
			$this->load_plugin_textdomain();

			// Plugin row meta on Plugins screen.
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

			// Handle plugin installation / data migrations.
			WC_Appointments_Install::init();

			// Handle plugin deactivation.
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		}

		/**
		 * Load Classes.
		 *
		 * @since 1.0.0
		 */
		public function includes() {
			if ( ! class_exists( 'WC_Ajax_Compat' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/compatibility/class-wc-ajax-compat.php';
			}

			if ( ! class_exists( 'WC_Appointments_Data' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/compatibility/abstract-wc-appointments-data.php'; // Appointments version of WC_Data.
			}

			// Caching.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-cache.php';

			// Initialize.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-init.php';

			// Install.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-install.php';

			// WC AJAX.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-wc-ajax.php';

			// Objects.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/data-objects/class-wc-appointment.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/data-objects/class-wc-appointments-availability.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/data-objects/class-wc-product-appointment.php';

			// Stores.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/data-stores/class-wc-appointment-data-store.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/data-stores/class-wc-appointments-availability-data-store.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/data-stores/class-wc-product-appointment-data-store-cpt.php';

			// Self.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-email-manager.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-cart-manager.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-checkout-manager.php';

			// External Libraries (autoload to avoid conflicts).
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/lib/php-rrule/vendor/autoload.php'; #rrule

			// Admin.
			if ( is_admin() ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/admin/class-wc-appointments-admin.php';
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/admin/class-wc-appointments-admin-ajax.php';
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/admin/class-wc-appointments-admin-addons.php';
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/admin/class-wc-appointments-admin-product-export-import.php';
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/admin/class-wc-appointments-admin-report-dashboard.php';
			}

			// Customize.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-customize.php';

			// Core.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/wc-appointments-functions.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-form-handler.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-order-manager.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-product-appointment-manager.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-controller.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-cron-manager.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-ics-exporter.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-shortcodes.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/gateways/class-wc-appointments-gateway.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/appointment-form/class-wc-appointment-form.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-widget-availability.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-gcal.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-cost-calculation.php';

			// REST API.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointments-rest-api.php';

			// Products.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-product-appointment-rule-manager.php';
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-product-appointment-staff.php';

			// Integrations.
			#if ( ! class_exists( 'WC_Product_Addons' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/woocommerce-product-addons/class-wc-appointments-integration-addons.php';
			#}

			if ( class_exists( 'SitePress' ) && class_exists( 'woocommerce_wpml' ) && class_exists( 'WPML_Element_Translation_Package' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-wcml.php';
			}

			if ( class_exists( 'Follow_Up_Emails' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/woocommerce-follow-up-emails/class-wc-appointments-integration-follow-ups.php';
			}

			if ( class_exists( 'WC_Twilio_SMS_Notification' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/woocommerce-twilio-sms-notifications/class-wc-appointments-integration-twilio-sms.php';
			}

	        if ( class_exists( 'WC_POS' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/woocommerce-point-of-sale/class-wc-appointments-integration-point-of-sale.php';
			}

	        if ( class_exists( 'WC_CRM' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-customer-relationship-manager.php';
			}

			if ( class_exists( 'WC_Product_Vendors' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-product-vendors.php';
			}

			if ( class_exists( 'WC_Memberships' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-memberships.php';
			}

			if ( class_exists( 'WC_PIP' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-invoices.php';
			}

			if ( class_exists( 'WPO_WCPDF' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-pdf-invoices.php';
			}

			if ( class_exists( 'WC_Deposits' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-deposits.php';
			}

			if ( class_exists( 'Polylang' ) && class_exists( 'Polylang_Woocommerce' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-polylang.php';
			}

			if ( class_exists( 'WC_Create_Customer_On_Order' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-create-customer-on-order.php';
			}

			if ( class_exists( 'PP_One_Page_Checkout' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-wcopc.php';
			}

			if ( class_exists( 'WC_Product_Price_Based_Country' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-wcpbc.php';
			}

			if ( class_exists( 'Kadence_Woomail_Designer' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-kadence-woomail.php';
			}

			if ( class_exists( 'WC_Box_Office' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-wc-box-office.php';
			}

			if ( class_exists( 'Webtomizer\WCDP\WC_Deposits' ) ) {
                include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-webtomizer-deposits.php';
            }

			if ( class_exists( 'WOOMULTI_CURRENCY' ) ) {
				include_once WC_APPOINTMENTS_ABSPATH . 'includes/integrations/class-wc-appointments-integration-multi-currency.php';
			}

			// Privacy.
			include_once WC_APPOINTMENTS_ABSPATH . 'includes/class-wc-appointment-privacy.php';
		}

		/**
		 * Localization.
		 *
		 *    - WP_LANG_DIR/woocommerce-appointments/woocommerce-appointments-LOCALE.mo
		 *    - woocommerce-appointments/languages/woocommerce-appointments-LOCALE.mo (which if not found falls back to:)
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-appointments' );

			load_textdomain( 'woocommerce-appointments', WP_LANG_DIR . '/woocommerce-appointments/woocommerce-appointments-' . $locale . '.mo' );
			load_plugin_textdomain( 'woocommerce-appointments', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Show row meta on the plugin screen.
		 *
		 * @access public
		 * @param  mixed $links Plugin Row Meta.
		 * @param  mixed $file  Plugin Base file.
		 * @return array
		 */
		public function plugin_row_meta( $links, $file ) {
			if ( plugin_basename( WC_APPOINTMENTS_MAIN_FILE ) == $file ) {
				$row_meta = array(
					'docs'    => '<a href="' . esc_url( apply_filters( 'woocommerce_appointments_docs_url', 'https://bookingwp.com/help/setup/woocommerce-appointments/' ) ) . '" title="' . esc_attr( __( 'View Documentation', 'woocommerce-appointments' ) ) . '">' . __( 'Docs', 'woocommerce-appointments' ) . '</a>',
					'support' => '<a href="' . esc_url( apply_filters( 'woocommerce_appointments_support_url', 'https://bookingwp.com/forums/' ) ) . '" title="' . esc_attr( __( 'Visit Support Forum', 'woocommerce-appointments' ) ) . '">' . __( 'Premium Support', 'woocommerce-appointments' ) . '</a>',
				);

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		/**
		 * Cleanup on plugin deactivation.
		 *
		 * @since 3.5.6
		 */
		public function deactivate() {
			WC_Admin_Notices::remove_notice( 'woocommerce_appointments_activation' );
		}

	}

endif;

/**
 * Returns the main instance of WC Appointments.
 *
 * @since  4.3.4
 * @return WooCommerce Appointments
 */
add_action( 'plugins_loaded', 'woocommerce_appointments_init', 11 ); #11 - load after all other plugins
function woocommerce_appointments_init() {
	// Stop if woocommerce plugin is not active.
	if ( ! is_woocommerce_active() ) {
		return;
	}

	// Stop if woocommerce version is not defined.
	if ( ! defined( 'WC_VERSION' ) ) {
		return;
	}

	// Fire up!
	$GLOBALS['wc_appointments'] = WC_Appointments::instance();
}

/**
 * LICENSE
 *
 * Also adds notices to activate/install WooCommerce when missing.
 *
 * @since  1.0.0
 */
add_action( 'plugins_loaded', 'wca_plugin_license' );
function wca_plugin_license() {

	// load our custom updater if it doesn't already exist.
	if ( ! class_exists( 'EDD_SL_Plugin_License' ) ) {
		require_once 'dependencies/class-edd-sl-plugin-license.php';
	}

	// Handle licensing.
	if ( class_exists( 'EDD_SL_Plugin_License' ) ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data( __FILE__, true, false ); #file, markup, translate
		$license     = new EDD_SL_Plugin_License( __FILE__, $plugin_data['Name'], $plugin_data['Version'], $plugin_data['Author'] );
	}

}

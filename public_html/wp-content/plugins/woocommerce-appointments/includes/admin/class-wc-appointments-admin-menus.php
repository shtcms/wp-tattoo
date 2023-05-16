<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;

/**
 * WC_Appointments_Admin_Menus.
 */
class WC_Appointments_Admin_Menus {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'current_screen', array( $this, 'buffer' ) );
		add_filter( 'woocommerce_screen_ids', array( $this, 'woocommerce_screen_ids' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_menu', array( $this, 'remove_default_appointments_menu_links' ), 10 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 49 );
		add_action( 'admin_menu', array( $this, 'register_navigation_items' ) );
		add_filter( 'menu_order', array( $this, 'menu_order' ), 20 );
		add_filter( 'admin_url', array( $this, 'add_new_appointment_url' ), 10, 2 );
	}

	/**
	 * output buffer.
	 */
	public function buffer() {
		// Get current screen.
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : '';
		$screen_id = $screen ? $screen->id : '';

		if ( 'wc_appointment_page_add_appointment' === $screen_id ) {
			ob_start();
		}
	}

	/**
	 * Screen IDS.
	 *
	 * @param  array  $ids
	 * @return array
	 */
	public function woocommerce_screen_ids( $ids ) {
		return array_merge(
			$ids,
			array(
				'edit-wc_appointment',
				'wc_appointment',
				'wc_appointment_page_appointment_calendar',
				'wc_appointment_page_appointment_notification',
				'wc_appointment_page_add_appointment',
				'wc_appointment_page_wc_appointments_global_availability',
			)
		);
	}

	/**
	 * Add appointment scripts in admin pages.
	 *
	 * @param  string $hook
	 * @return void meta in header
	 */
	public function admin_scripts( $hook ) {

		// Admin calendar page scripts.
		if ( 'wc_appointment_page_appointment_calendar' == $hook ) {
			wp_enqueue_script( 'wc-enhanced-select' );
			wp_enqueue_script( 'jquery-ui-datepicker' );

		// Admin add new appointment page scripts.
		} elseif ( 'wc_appointment_page_add_appointment' == $hook ) {
			WC_Appointments_Init::appointment_form_styles();
		}

	}

	/**
	 * Removes the default appointments menu links from the main admin menu.
	 */
	public function remove_default_appointments_menu_links() {
		global $submenu;

		if ( isset( $submenu['edit.php?post_type=wc_appointment'] ) ) {
			foreach ( $submenu['edit.php?post_type=wc_appointment'] as $key => $value ) {
				if ( 'post-new.php?post_type=wc_appointment' == $value[2] ) {
					unset( $submenu['edit.php?post_type=wc_appointment'][ $key ] );
					return;
				}
			}
		}
	}

	/**
	 * Add a submenu for managing appointments pages.
	 */
	public function admin_menu() {
		// Add new appointment menu item.
		$add_appointment_page = add_submenu_page(
			'edit.php?post_type=wc_appointment',
			__( 'Add New', 'woocommerce-appointments' ),
			__( 'Add New', 'woocommerce-appointments' ),
			'manage_appointments',
			'add_appointment',
			array(
				$this,
				'add_appointment_page'
			)
		);

		// Calendar menu item.
		$calendar_page        = add_submenu_page(
			'edit.php?post_type=wc_appointment',
			__( 'Calendar', 'woocommerce-appointments' ),
			__( 'Calendar', 'woocommerce-appointments' ),
			'manage_appointments',
			'appointment_calendar',
			array(
				$this,
				'calendar_page'
			)
		);
	}

	/**
	 * Create appointment page
	 */
	public function add_appointment_page() {
		require_once 'class-wc-appointments-admin-add.php';
		$page = new WC_Appointments_Admin_Add();
		$page->output();
	}

	/**
	 * Output the calendar page
	 */
	public function calendar_page() {
		require_once 'class-wc-appointments-admin-calendar.php';
		$page = new WC_Appointments_Admin_Calendar();
		$page->output();
	}

	/**
	 * Register the navigation items in the WooCommerce navigation.
	 *
	 * @since 4.11.4
	 */
	public static function register_navigation_items() {
		if (
			! method_exists( Screen::class, 'register_post_type' ) ||
			! method_exists( Menu::class, 'add_plugin_category' ) ||
			! method_exists( Menu::class, 'add_plugin_item' )
		) {
			return;
		}

		// Add extensions menu in WC dashboard.
		Menu::add_plugin_category(
			array(
				'id'         => 'woocommerce_appointments',
				'title'      => __( 'Appointments', 'woocommerce-appointments' ),
			)
		);

		// All Appointments menu item.
		$appointment_item = Menu::get_post_type_items(
			'wc_appointment',
			array(
				'parent' => 'woocommerce_appointments',
			)
		);
		Menu::add_plugin_item( $appointment_item['all'] );

		// Add New menu item.
		Menu::add_plugin_item(
			array(
				'id'         => 'woocommerce-appointments-add',
				'title'      => __( 'Add New', 'woocommerce-appointments' ),
				'capability' => 'edit_wc_appointments',
				'url'        => 'edit.php?post_type=wc_appointment&page=add_appointment',
				'parent'     => 'woocommerce_appointments',
			)
		);

		// Calendar menu item.
		Menu::add_plugin_item(
			array(
				'id'         => 'woocommerce-appointments-calendar',
				'title'      => __( 'Calendar', 'woocommerce-appointments' ),
				'capability' => 'edit_wc_appointments',
				'url'        => 'edit.php?post_type=wc_appointment&page=appointment_calendar',
				'parent'     => 'woocommerce_appointments',
			)
		);

		// Set the post type for the scren to trap all submenus.
		Screen::register_post_type( 'wc_appointment' );
	}

	/**
	 * Reorder the WC menu items in admin.
	 *
	 * @param mixed $menu_order
	 * @return array
	 */
	public function menu_order( $menu_order ) {
		// Initialize our custom order array
		$new_menu_order = [];

		// Get index of product menu
		$appointment_menu = array_search( 'edit.php?post_type=wc_appointment', $menu_order );

		// Loop through menu order and do some rearranging
		foreach ( $menu_order as $index => $item ) :
			if ( ( ( 'edit.php?post_type=product' ) == $item ) ) :
				$new_menu_order[] = $item;
				$new_menu_order[] = 'edit.php?post_type=wc_appointment';
				unset( $menu_order[ $appointment_menu ] );
			else :
				$new_menu_order[] = $item;
			endif;
		endforeach;

		// Return order
		return $new_menu_order;
	}

	/**
	 * Filters the add new appointment url to point to our custom page.
	 *
	 * @param string $url original url
	 * @param string $path requested path that we can match against
	 * @return string new url
	 */
	public function add_new_appointment_url( $url, $path ) {
		if ( 'post-new.php?post_type=wc_appointment' == $path ) {
			return admin_url( 'edit.php?post_type=wc_appointment&page=add_appointment' );
		}

		return $url;
	}
}

$GLOBALS['wc_appointment_admin_menus'] = new WC_Appointments_Admin_Menus();

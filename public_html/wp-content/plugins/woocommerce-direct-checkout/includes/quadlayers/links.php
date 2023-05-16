<?php

class QLWCDC_Admin_Links {

	protected static $_instance;

	function __construct() {
		add_filter( 'plugin_action_links_' . QLWCDC_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	public function add_action_links( $links ) {
		$links[] = '<a target="_blank" href="' . QLWCDC_PREMIUM_SELL_URL . '">' . esc_html__( 'Premium', 'woocommerce-direct-checkout' ) . '</a>';
		$links[] = '<a target="_blank" href="' . QLWCDC_DOCUMENTATION_URL . '">' . esc_html__( 'Documentation', 'woocommerce-direct-checkout' ) . '</a>';
		$links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=' . QLWCDC_PREFIX ) . '">' . esc_html__( 'Settings', 'woocommerce-direct-checkout' ) . '</a>';
		return $links;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

}

QLWCDC_Admin_Links::instance();

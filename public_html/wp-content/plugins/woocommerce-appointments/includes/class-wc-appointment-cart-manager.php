<?php
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WC_Appointment_Cart_Manager class.
 */
class WC_Appointment_Cart_Manager {

	/**
	 * The class id used for identification in logging.
	 *
	 * @var $id
	 */
	public $id;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'add_product_object' ), 0 );
		add_action( 'woocommerce_before_appointment_form_output', array( $this, 'add_product_object' ), 0 );
		add_action( 'woocommerce_after_appointment_form_output', array( $this, 'add_product_object' ), 0 );
		add_action( 'woocommerce_appointment_add_to_cart', array( $this, 'add_to_cart' ), 30 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_add_cart_item' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'cart_item_quantity' ), 15, 3 );
		add_filter( 'woocommerce_add_cart_item', array( $this, 'add_cart_item' ), 9, 1 ); #9 to allow others to hook after.
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 9, 3 ); #9 to allow others to hook after.
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_loaded_from_session' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
		add_action( 'woocommerce_new_order_item', array( $this, 'order_item_meta' ), 50, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'validate_appointment_requires_confirmation' ), 20, 2 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'cart_item_removed' ), 20 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'cart_item_restored' ), 20 );
		#add_action( 'woocommerce_cart_emptied', array( $this, 'cart_emptied' ), 20 );

		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'add_to_cart_redirect' ) );
		}

		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'woocommerce_product_link_querystring' ), 10, 2 );
		add_filter( 'woocommerce_loop_product_link', array( $this, 'woocommerce_product_link_querystring' ), 10, 2 );

		$this->id = 'wc_appointment_cart_manager';

		// Active logs.
		if ( class_exists( 'WC_Logger' ) ) {
			$this->log = new WC_Logger();
		}

	}

	/**
	 * Add product object when global variable is missing.
	 */
	public function add_product_object() {
		global $product;

		if ( ! $product && isset( $GLOBALS['product'] ) ) {
			$product = $GLOBALS['product'];
		}
	}


	/**
	 * Add to cart for appointments
	 */
	public function add_to_cart() {
		global $product;

		// Prepare form
		$appointment_form = new WC_Appointment_Form( $product );

		// Get template
		wc_get_template(
			'single-product/add-to-cart/appointment.php',
			array(
				'appointment_form' => $appointment_form,
			),
			'',
			WC_APPOINTMENTS_TEMPLATE_PATH
		);
	}

	/**
	 * When an appointment is added to the cart, validate it
	 *
	 * @param mixed $passed
	 * @param mixed $product_id
	 * @param mixed $qty
	 * @return bool
	 */
	public function validate_add_cart_item( $passed, $product_id, $qty ) {
		$product = wc_get_product( $product_id );

		if ( ! is_wc_appointment_product( $product ) ) {
			return $passed;
		}

		$data     = wc_appointments_get_posted_data( $_POST, $product );
		$validate = $product->is_appointable( $data );

		if ( is_wp_error( $validate ) ) {
			wc_add_notice( $validate->get_error_message(), 'error' );
			return false;
		}

		return $passed;
	}

	/**
	 * Make appointment quantity in cart readonly
	 *
	 * @param mixed $product_quantity
	 * @param mixed $cart_item_key
	 * @param mixed $cart_item
	 * @return string
	 */
	public function cart_item_quantity( $product_quantity, $cart_item_key ) {
		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		if ( ! empty( $cart_item['appointment'] ) && ! empty( $cart_item['appointment']['_qty'] ) ) {
			$product_quantity = sprintf( '%1$s <input type="hidden" name="cart[%2$s][qty]" value="%1$s" />', $cart_item['quantity'], $cart_item_key );
		}

		return $product_quantity;
	}

	/**
	 * Adjust the price of the appointment product based on appointment properties
	 *
	 * @param mixed $cart_item
	 *
	 * @return array cart item
	 */
	public function add_cart_item( $cart_item ) {
		if ( ! empty( $cart_item['appointment'] ) && isset( $cart_item['appointment']['_cost'] ) && '' !== $cart_item['appointment']['_cost'] ) {
			$quantity = isset( $cart_item['appointment']['_qty'] ) && 0 !== $cart_item['appointment']['_qty'] ? $cart_item['appointment']['_qty'] : 1;
			$cart_item['data']->set_price( $cart_item['appointment']['_cost'] / $quantity );
		}

		return $cart_item;
	}

	/**
	 * Get data from the session and add to the cart item's meta
	 *
	 * @param mixed $cart_item
	 * @param mixed $values
	 * @return array cart item
	 */
	public function get_cart_item_from_session( $cart_item, $values, $cart_item_key ) {
		if ( ! empty( $values['appointment'] ) ) {
			$cart_item['appointment'] = $values['appointment'];
			$cart_item                = $this->add_cart_item( $cart_item );
		}

		return $cart_item;
	}

	/**
	 * Before delete
	 *
	 * @param string $cart_item_key identifying which item in cart.
	 */
	public function cart_item_removed( $cart_item_key ) {
		$cart_item = WC()->cart->removed_cart_contents[ $cart_item_key ];

		if ( isset( $cart_item['appointment'] ) ) {
			$appointment_id = $cart_item['appointment']['_appointment_id'];
			$appointment    = get_wc_appointment( $appointment_id );
			if ( $appointment && $appointment->has_status( 'in-cart' ) ) {
				$appointment->update_status( 'was-in-cart' );
				WC_Cache_Helper::get_transient_version( 'appointments', true );
				as_unschedule_action( 'wc-appointment-remove-inactive-cart', array( $appointment_id ), 'wca' );

				if ( isset( $this->log ) ) {
					$message = sprintf( 'Appointment ID: %s removed from cart', $appointment->get_id() );
					$this->log->add( $this->id, $message );
				}
			}
		}
	}

	/**
	 * Restore item.
	 *
	 * @param string $cart_item_key identifying which item in cart.
	 */
	public function cart_item_restored( $cart_item_key ) {
		$cart      = WC()->cart->get_cart();
		$cart_item = $cart[ $cart_item_key ];

		if ( isset( $cart_item['appointment'] ) ) {
			$appointment_id = $cart_item['appointment']['_appointment_id'];
			$appointment    = get_wc_appointment( $appointment_id );
			if ( $appointment && $appointment->has_status( 'was-in-cart' ) ) {
				$appointment->update_status( 'in-cart' );
				WC_Cache_Helper::get_transient_version( 'appointments', true );
				$this->schedule_cart_removal( $appointment_id );

				if ( isset( $this->log ) ) {
					$message = sprintf( 'Appointment ID: %s was restored to cart', $appointment->get_id() );
					$this->log->add( $this->id, $message );
				}
			}
		}
	}

	/**
	 * Cart emmptied.
	 *
	 * Remove all 'in-cart' appointments and assign them 'was-in-cart' status.
	 */
	/*
	public function cart_emptied() {
		// Get all appointments with 'in-cart' status.
		$appointment_ids = WC_Appointment_Data_Store::get_appointment_ids_by(
			array(
				'status' => array( 'in-cart' ),
				'limit'  => 100,
			)
		);

		if ( $appointment_ids ) {
			foreach ( $appointment_ids as $appointment_id ) {
				$appointment = get_wc_appointment( $appointment_id );
				$appointment->update_status( 'was-in-cart' );
				as_unschedule_action( 'wc-appointment-remove-inactive-cart', array( $appointment_id ), 'wca' );
			}
			WC_Cache_Helper::get_transient_version( 'appointments', true );
		}

		#print '<pre>'; print_r( $appointment_ids ); print '</pre>';
	}
	*/

	/**
	 * Schedule appointment to be deleted if inactive.
	 */
	public function schedule_cart_removal( $appointment_id ) {
		$hold_stock_minutes = (int) get_option( 'woocommerce_hold_stock_minutes', 60 );
		$minutes            = apply_filters( 'woocommerce_appointments_remove_inactive_cart_time', $hold_stock_minutes );

		/**
		 * If this has been emptied, or set to 0, it will just exit. This means that in-cart appointments will need to be manually removed.
		 * Also take note that if the $minutes var is set to 5 or less, this means that it is possible for the in-cart appointment to be
		 * removed before the customer is able to check out.
		 */
		if ( empty( $minutes ) ) {
			return;
		}

		$timestamp = time() + MINUTE_IN_SECONDS * (int) $minutes;

		as_schedule_single_action( $timestamp, 'wc-appointment-remove-inactive-cart', array( $appointment_id ), 'wca' );
	}

	/**
	 * Check for invalid appointments
	 */
	public function cart_loaded_from_session() {
		$titles       = [];
		$count_titles = 0;

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['appointment'] ) ) {
				// If the appointment is gone, remove from cart!
				$appointment_id = $cart_item['appointment']['_appointment_id'];
				$appointment    = get_wc_appointment( $appointment_id );

				if ( ! $appointment || ! $appointment->has_status( array( 'was-in-cart', 'in-cart', 'unpaid', 'paid' ) ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ] );

					WC()->cart->calculate_totals();

					if ( $cart_item['product_id'] ) {
						$title = '<a href="' . get_permalink( $cart_item['product_id'] ) . '">' . get_the_title( $cart_item['product_id'] ) . '</a>';
						$count_titles++;
						if ( ! in_array( $title, $titles, true ) ) {
							$titles[] = $title;
						}
					}
				}
			}
		}

		if ( $count_titles < 1 ) {
			return;
		}
		$formatted_titles = wc_format_list_of_items( $titles );
		/* translators: Admin notice with title and link to bookable product removed from cart. */
		$notice = sprintf( __( 'An appointment for %s has been removed from your cart due to inactivity.', 'woocommerce-appointments' ), $formatted_titles );

		if ( $count_titles > 1 ) {
			/* translators: Admin notice with list of titles and links to bookable products removed from cart. */
			$notice = sprintf( __( 'Appointments for %s have been removed from your cart due to inactivity.', 'woocommerce-appointments' ), $formatted_titles );
		}

		wc_add_notice( $notice, 'notice' );
	}

	/**
	 * Add posted data to the cart item
	 *
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 * @return array $cart_item_meta
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! is_wc_appointment_product( $product ) ) {
			return $cart_item_meta;
		}

		$cart_item_meta['appointment']          = wc_appointments_get_posted_data( $_POST, $product );
		$cart_item_meta['appointment']['_cost'] = WC_Appointments_Cost_Calculation::calculate_appointment_cost( $_POST, $product );

		if ( $cart_item_meta['appointment']['_cost'] instanceof WP_Error ) {
			throw new Exception( $cart_item_meta['appointment']['_cost']->get_error_message() );
		}

		// Create the new appointment
		$new_appointment = $this->add_appointment_from_cart_data( $cart_item_meta, $product_id );

		// Store in cart
		$cart_item_meta['appointment']['_appointment_id'] = $new_appointment->get_id();

		// Schedule this item to be removed from the cart if the user is inactive
		$this->schedule_cart_removal( $new_appointment->get_id() );

		return $cart_item_meta;
	}

	/**
	 * Create appointment from cart data
	 *
	 * @param        $cart_item_meta
	 * @param        $product_id
	 * @param string $status
	 *
	 * @return object
	 */
	private function add_appointment_from_cart_data( $cart_item_meta, $product_id, $status = 'in-cart' ) {
		// Create the new appointment
		$new_appointment_data = array(
			'product_id' => $product_id, // Appointment ID
			'cost'       => $cart_item_meta['appointment']['_cost'], // Cost of this appointment
			'start_date' => $cart_item_meta['appointment']['_start_date'],
			'end_date'   => $cart_item_meta['appointment']['_end_date'],
			'all_day'    => $cart_item_meta['appointment']['_all_day'],
			'qty'        => $cart_item_meta['appointment']['_qty'],
			'timezone'   => $cart_item_meta['appointment']['_timezone'],
		);

		// Check if the appointment has staff
		if ( isset( $cart_item_meta['appointment']['_staff_id'] ) ) {
			$new_appointment_data['staff_id'] = $cart_item_meta['appointment']['_staff_id']; // ID of the staff
		}

		// Pass all staff selected
		if ( isset( $cart_item_meta['appointment']['_staff_ids'] ) ) {
			$new_appointment_data['staff_ids'] = $cart_item_meta['appointment']['_staff_ids']; // IDs of the staff
		}

		$new_appointment = get_wc_appointment( $new_appointment_data );
		$new_appointment->create( $status );

		return $new_appointment;
	}

	/**
	 * Put meta data into format which can be displayed
	 *
	 * @param mixed $other_data
	 * @param mixed $cart_item
	 * @return array meta
	 */
	public function get_item_data( $other_data, $cart_item ) {
		if ( empty( $cart_item['appointment'] ) ) {
			return $other_data;
		}

		if ( ! empty( $cart_item['appointment']['_appointment_id'] ) ) {
			$appointment = get_wc_appointment( $cart_item['appointment']['_appointment_id'] );
		}

		if ( ! empty( $cart_item['appointment'] ) ) {
			foreach ( $cart_item['appointment'] as $key => $value ) {
				if ( substr( $key, 0, 1 ) !== '_' ) {
					$other_data[] = array(
						'name'    => get_wc_appointment_data_label( $key, $cart_item['data'] ),
						'value'   => $value,
						'display' => '',
					);
				}
			}
		}

		return $other_data;
	}

	/**
	 * order_item_meta function.
	 *
	 * @param mixed $item_id
	 * @param mixed $values
	 */
	public function order_item_meta( $item_id, $values ) {
		$appointment_cost = 0;

		if ( ! empty( $values['appointment'] ) ) {
			$product          = $values['data'];
			$appointment_id   = $values['appointment']['_appointment_id'];
			$appointment_cost = (float) $values['appointment']['_cost'];
		}

		if ( ! isset( $appointment_id ) && property_exists( $values, 'legacy_values' ) && ! empty( $values->legacy_values ) && is_array( $values->legacy_values ) && ! empty( $values->legacy_values['appointment'] ) ) {
			$product          = $values->legacy_values['data'];
			$appointment_id   = $values->legacy_values['appointment']['_appointment_id'];
			$appointment_cost = (float) $values->legacy_values['appointment']['_cost'];
		}

		if ( isset( $appointment_id ) ) {
			$appointment = get_wc_appointment( $appointment_id );

			// Set status as Confirmed for free appointments.
			if ( $appointment_cost ) {
				$appointment_status = 'unpaid';
			} else {
				$appointment_status = 'confirmed';
			}

			if ( function_exists( 'wc_get_order_id_by_order_item_id' ) ) {
				$order_id = wc_get_order_id_by_order_item_id( $item_id );
			} else {
				global $wpdb;
				$order_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d",
						$item_id
					)
				);
			}

			// Set as pending when the appointment requires confirmation
			if ( wc_appointment_requires_confirmation( $values['product_id'] ) ) {
				$appointment_status = 'pending-confirmation';
			}

			/*
			// Testing.
			update_option( 'xxx1', $order_id );
			update_option( 'xxx2', $item_id );
			update_option( 'xxx3', $values );
			update_option( 'xxx4', $values['product_id'] );
			update_option( 'xxx5', $product );
			update_option( 'xxx6', $appointment_id );
			*/

			$appointment->set_order_id( $order_id );
			$appointment->set_order_item_id( $item_id );
			$appointment->set_status( $appointment_status );
			$appointment->save();
		}
	}

	/**
	 * Redirects directly to the cart the products they need confirmation.
	 *
	 * @since 1.0.0
	 * @version 3.4.0
	 *
	 * @param string $url URL.
	 */
	public function add_to_cart_redirect( $url ) {
		if ( isset( $_REQUEST['add-to-cart'] ) && is_numeric( $_REQUEST['add-to-cart'] ) && wc_appointment_requires_confirmation( intval( $_REQUEST['add-to-cart'] ) ) ) {
			// Remove add to cart messages only in case there's no error.
			$notices = wc_get_notices();
			if ( empty( $notices['error'] ) ) {
				wc_clear_notices();

				// Go to checkout.
				return wc_get_cart_url();
			}
		}

		return $url;
	}

	/**
	 * Add querystring to product link.
	 *
	 * @since 3.4.0
	 * @version 3.4.0
	 *
	 * @param string $url URL.
	 * @param object $product.
	 */
	public function woocommerce_product_link_querystring( $permalink, $product ) {
		if ( ! is_wc_appointment_product( $product ) ) {
			return $permalink;
		}

		// Querystrings exist?
		$date  = isset( $_GET['min_date'] ) ? wc_clean( wp_unslash( $_GET['min_date'] ) ) : ''; // WPCS: input var ok, CSRF ok.
		$time  = isset( $_GET['time'] ) ? wc_clean( wp_unslash( $_GET['time'] ) ) : ''; // WPCS: input var ok, CSRF ok.
		$staff = isset( $_GET['staff'] ) ? wc_clean( wp_unslash( $_GET['staff'] ) ) : ''; // WPCS: input var ok, CSRF ok.

		if ( $date ) {
			$permalink = add_query_arg( 'date', $date, $permalink );
		}
		if ( $time ) {
			$permalink = add_query_arg( 'time', $time, $permalink );
		}
		if ( $staff ) {
			$permalink = add_query_arg( 'staff', $staff, $permalink );
		}

		return apply_filters( 'woocommerce_appointment_get_permalink', $permalink, $this );
	}

	/**
	 * Remove all appointments that require confirmation.
	 *
	 * @return void
	 */
	protected function remove_appointment_that_requires_confirmation() {
		foreach ( WC()->cart->cart_contents as $item_key => $item ) {
			if ( wc_appointment_requires_confirmation( $item['product_id'] ) ) {
				WC()->cart->set_quantity( $item_key, 0 );
			}
		}
	}

	/**
	 * Removes all products when cart have an appointment which requires confirmation
	 *
	 * @param  bool $passed
	 * @param  int  $product_id
	 *
	 * @return bool
	 */
	public function validate_appointment_requires_confirmation( $passed, $product_id ) {
		if ( wc_appointment_requires_confirmation( $product_id ) ) {

			$items = WC()->cart->get_cart();

			foreach ( $items as $item_key => $item ) {
				if ( ! isset( $item['appointment'] ) || ! wc_appointment_requires_confirmation( $item['product_id'] ) ) {
					WC()->cart->remove_cart_item( $item_key );
					/* translators: %s: product name in quotes */
					$product_name = ( $item['quantity'] > 1 ? absint( $item['quantity'] ) . ' &times; ' : '' ) . apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce-appintments' ), wp_strip_all_tags( get_the_title( $item['product_id'] ) ) ), $item['product_id'] );
					/* translators: %s: product name */
					wc_add_notice( sprintf( __( '%s has been removed from your cart. It is not possible to complete the purchase along with an appointment that requires confirmation.', 'woocommerce-appointments' ), $product_name ), 'notice' );
				}
			}
		} elseif ( wc_appointment_cart_requires_confirmation() ) {
			// Remove appointment that requires confirmation.
			$this->remove_appointment_that_requires_confirmation();

			wc_add_notice( __( 'An appointment that requires confirmation has been removed from your cart. It is not possible to complete the purchase along with an appointment that doesn\'t require confirmation.', 'woocommerce-appointments' ), 'notice' );
		}

		return $passed;
	}
}

$GLOBALS['wc_appointment_cart_manager'] = new WC_Appointment_Cart_Manager();

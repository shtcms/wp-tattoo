<?php
/**
 * Checkbox/radios field
 */
class WC_Product_Addons_Field_List extends WC_Product_Addons_Field {

	/**
	 * Validate an addon
	 *
	 * @return bool|WP_Error
	 */
	public function validate() {
		if ( ! empty( $this->addon['required'] ) ) {
			if ( ! $this->value || sizeof( $this->value ) == 0 ) {
				return new WP_Error( 'error', sprintf( __( '"%s" is a required field.', 'woocommerce-appointments' ), $this->addon['name'] ) );
			}
		}

		return true;
	}

	/**
	 * Process this field after being posted
	 *
	 * @return array|WP_Error Array on success and WP_Error on failure
	 */
	public function get_cart_item_data() {
		$cart_item_data = [];
		$value          = $this->value;

		if ( empty( $value ) ) {
			return false;
		}

		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		if ( is_array( current( $value ) ) ) {
			$value = current( $value );
		}

		foreach ( $this->addon['options'] as $option ) {
			if ( in_array( strtolower( sanitize_title( $option['label'] ) ), array_map( 'strtolower', array_values( $value ) ) ) ) {
				$cart_item_data[] = array(
					'name'          => sanitize_text_field( $this->addon['name'] ),
					'value'         => $option['label'],
					'price'         => floatval( sanitize_text_field( $this->get_option_price( $option ) ) ),
					'field_name'    => $this->addon['field_name'],
					'field_type'    => $this->addon['type'],
					'price_type'    => $option['price_type'],
					'duration'      => isset( $option['duration'] ) ? $option['duration'] : 0,
					'duration_type' => isset( $option['duration_type'] ) ? $option['duration_type'] : '',
					'hide_duration' => isset( $this->addon['wc_appointment_hide_duration_label'] ) && $this->addon['wc_appointment_hide_duration_label'] ? 1 : 0,
					'hide_price'    => isset( $this->addon['wc_appointment_hide_price_label'] ) && $this->addon['wc_appointment_hide_price_label'] ? 1 : 0,
				);
			}
		}

		return $cart_item_data;
	}
}

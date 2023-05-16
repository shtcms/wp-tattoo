<?php
/**
 * The Template for displaying quantity/input multiplier field.
 *
 * @version 3.0.27
 */

$field_name      = ! empty( $addon['field_name'] ) ? $addon['field_name'] : '';
$addon_key       = 'addon-' . sanitize_title( $field_name );
$current_value   = isset( $_POST[ $addon_key ] ) && isset( $_POST[ $addon_key ] ) ? wc_clean( $_POST[ $addon_key ] ) : '';
$adjust_price    = ! empty( $addon['adjust_price'] ) ? $addon['adjust_price'] : '';
$price           = ! empty( $addon['price'] ) ? $addon['price'] : '';
$price_type      = ! empty( $addon['price_type'] ) ? $addon['price_type'] : '';
$price_raw       = apply_filters( 'woocommerce_product_addons_price_raw', '1' == $adjust_price && $price ? $price : '', $addon );
$adjust_duration = ! empty( $addon['adjust_duration'] ) ? $addon['adjust_duration'] : '';
$duration        = ! empty( $addon['duration'] ) ? absint( $addon['duration'] ) : '';
$duration_type   = ! empty( $addon['duration_type'] ) ? $addon['duration_type'] : '';
$duration_raw    = apply_filters( 'woocommerce_product_addons_duration_raw', '1' == $adjust_duration && $duration ? $duration : '', $addon );
$min             = ! empty( $addon['min'] ) ? $addon['min'] : 0;
$max             = ! empty( $addon['max'] ) ? $addon['max'] : '';
$restrictions    = ! empty( $addon['restrictions'] ) ? $addon['restrictions'] : '';

$price_display = apply_filters(
	'woocommerce_product_addons_price',
	'1' == $adjust_price && $price_raw ? WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw ) : '',
	$addon,
	0,
	$addons,
	'input_multiplier'
);

if ( 'percentage_based' === $price_type ) {
	$price_display = $price_raw;
}

$duration_display = apply_filters(
	'woocommerce_product_addons_duration',
	'1' == $adjust_duration && $duration_raw ? ' ' . wc_appointment_pretty_addon_duration( $duration_raw ) : '',
	$addon,
	0,
	$addons,
	'input_multiplier'
);
?>

<p class="form-row form-row-wide wc-pao-addon-wrap wc-pao-addon-<?php echo sanitize_title( $field_name ); ?>">
	<input
		type="number"
		class="input-text wc-pao-addon-field wc-pao-addon-input-multiplier"
		data-raw-price="<?php echo esc_attr( $price_raw ); ?>"
		data-price="<?php echo esc_attr( $price_display ); ?>"
		data-price-type="<?php echo esc_attr( $price_type ); ?>"
		data-raw-duration="<?php echo esc_attr( $duration_raw ); ?>"
		data-duration="<?php echo esc_attr( $duration_display ); ?>"
		data-duration-type="<?php echo esc_attr( $duration_type ); ?>"
		name="<?php echo esc_attr( $addon_key ); ?>"
		id="<?php echo esc_attr( $addon_key ); ?>"
		value=""
		<?php if ( 1 === $restrictions ) { echo 'min="' . esc_attr( $min ) .'"'; } ?>
		<?php if ( ! empty( $max ) && 1 === $restrictions ) { echo 'max="' . esc_attr( $max ) .'"'; } ?>
	 	<?php if ( WC_Product_Addons_Helper::is_addon_required( $addon ) ) { echo 'required'; } ?>
	/>
</p>

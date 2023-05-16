<?php
/**
 * The Template for displaying image swatches field.
 *
 * @version 3.0.0
 */
$loop          = 0;
$field_name    = ! empty( $addon['field_name'] ) ? $addon['field_name'] : '';
$required      = ! empty( $addon['required'] ) ? $addon['required'] : '';
$current_value = isset( $_POST['addon-' . sanitize_title( $field_name ) ] ) ? wc_clean( $_POST[ 'addon-' . sanitize_title( $field_name ) ] ) : '';
?>

<p class="form-row form-row-wide wc-pao-addon-wrap wc-pao-addon-<?php echo sanitize_title( $field_name ); ?>">
<?php
/*
if ( empty( $required ) ) { ?>
	<a href="#" title="<?php echo esc_attr__( 'None', 'woocommerce-appointments' ); ?>" class="wc-pao-addon-image-swatch" data-value="" data-price="">
		<img src="<?php echo esc_url( WC_Product_Addons_Helper::no_image_select_placeholder_src() ); ?>" />
	</a>
<?php }
*/
?>
<?php foreach ( $addon['options'] as $i => $option ) {
	$loop++;
	$price           = ! empty( $option['price'] ) ? $option['price'] : '';
	$price_prefix    = 0 < $price ? '+' : '';
	$price_type      = $option['price_type'];
	$price_raw       = apply_filters( 'woocommerce_product_addons_option_price_raw', $price, $option );
	$duration        = ! empty( $option['duration'] ) ? absint( $option['duration'] ) : '';
	$duration_prefix = 0 < $duration ? '+' : '';
	$duration_type   = $option['duration_type'];
	$duration_raw    = apply_filters( 'woocommerce_product_addons_option_duration_raw', $duration, $option );

	if ( 'percentage_based' === $price_type ) {
		$price_display = apply_filters(
			'woocommerce_product_addons_option_price',
			$price_raw ? '(' . $price_prefix . $price_raw . '%)' : '',
			$option,
			$i,
			$addon,
			'image'
		);
		$price_tip     = $price_prefix && $price_display ? ' ' . $price_prefix . $price_raw . '%' : '';
	} else {
		$price_display = apply_filters(
			'woocommerce_product_addons_option_price',
			$price_raw ? '(' . $price_prefix . wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw ) ) . ')' : '',
			$option,
			$i,
			$addon,
			'image'
		);
		$price_tip     = $price_prefix && $price_display ? '<br/>' . $price_prefix . wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw ) ) : '';
	}

	$duration_display = apply_filters(
		'woocommerce_product_addons_option_duration',
		$duration_raw ? ' ' . wc_appointment_pretty_addon_duration( $duration_raw ) : '',
		$option,
		$i,
		$addon,
		'image'
	);
	$duration_tip     = $duration_prefix && $duration_display ? '<br/>' . wc_appointment_pretty_addon_duration( $duration_raw ) : '';

	$image_src = wp_get_attachment_image_src( $option['image'], apply_filters( 'woocommerce_product_addons_image_swatch_size', 'thumbnail', $option ) );
	?>
		<a href="#" title="<?php echo esc_attr( $option['label'] . $price_tip . $duration_tip ); ?>" class="wc-pao-addon-image-swatch" data-value="<?php echo sanitize_title( $option['label'] ) . '-' . $loop; ?>" data-price="<?php echo esc_attr( '<span class="wc-pao-addon-image-swatch-price">' . wptexturize( $option['label'] ) . ' ' . $price_display . $duration_display . '</span>' ); ?>">
			<img src="<?php echo esc_url( $image_src && isset( $image_src[0] ) ? $image_src[0] : wc_placeholder_img_src() ); ?>" />
		</a>
<?php } ?>

<select class="wc-pao-addon-image-swatch-select wc-pao-addon-field" name="addon-<?php echo sanitize_title( $field_name ); ?>" <?php if ( WC_Product_Addons_Helper::is_addon_required( $addon ) ) { echo 'required'; } ?>>
	<?php if ( empty( $required ) ) { ?>
		<option value=""><?php esc_html_e( 'None', 'woocommerce-appointments' ); ?></option>
	<?php } else { ?>
		<option value=""><?php esc_html_e( 'Select an option...', 'woocommerce-appointments' ); ?></option>
	<?php }
	$loop = 0;

	foreach ( $addon['options'] as $i => $option ) {
		$loop++;

		$price      = ! empty( $option['price'] ) ? $option['price'] : '';
		$price_raw  = apply_filters( 'woocommerce_product_addons_option_price_raw', $price, $option );
		$price_type = ! empty( $option['price_type'] ) ? $option['price_type'] : '';
		$label      = ! empty( $option['label'] ) ? $option['label'] : '';

		$price_for_display = apply_filters(
			'woocommerce_product_addons_option_price',
			$price_raw ? '(' . wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw ) ) . ')' : '',
			$option,
			$i,
			$addon,
			'image'
		);

		$price_display = WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw );

		if ( 'percentage_based' === $price_type ) {
			$price_display = $price_raw;
		}

		$duration        = ! empty( $option['duration'] ) ? absint( $option['duration'] ) : '';
		$duration_prefix = 0 < $duration ? '+' : '';
		$duration_type   = $option['duration_type'];
		$duration_raw    = apply_filters( 'woocommerce_product_addons_option_duration_raw', $duration, $option );

		$duration_display = apply_filters(
			'woocommerce_product_addons_option_duration',
			$duration_raw ? ' ' . wc_appointment_pretty_addon_duration( $duration_raw ) : '',
			$option,
			$i,
			$addon,
			'image'
		);
		?>
		<option
			data-raw-price="<?php echo esc_attr( $price_raw ); ?>"
			data-price="<?php echo esc_attr( $price_display ); ?>"
			data-price-type="<?php echo esc_attr( $price_type ); ?>"
			data-raw-duration="<?php echo esc_attr( $duration_raw ); ?>"
			data-duration="<?php echo esc_attr( $duration_display ); ?>"
			data-duration-type="<?php echo esc_attr( $duration_type ); ?>"
			value="<?php echo sanitize_title( $option['label'] ) . '-' . $loop; ?>"
			data-label="<?php echo esc_attr( wptexturize( $label ) ); ?>"
		><?php echo wptexturize( $label ) . ' ' . $price_for_display . $duration_display; ?></option>
	<?php } ?>

</select>
</p>

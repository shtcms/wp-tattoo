<?php
/**
 * HIDDEN appointment form field
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/appointment-form/hidden.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @version     4.10.7
 * @since       3.4.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$after = $field['after'] ?? null;
$class = $field['class'];
$label = $field['label'];
$max   = $field['max'] ?? null;
$min   = $field['min'] ?? null;
$name  = $field['name'];
$step  = $field['step'] ?? null;
?>
<p class="form-field form-field-wide <?php echo esc_attr( implode( ' ', $class ) ); ?>" style="display: none;">
	<label for="<?php echo esc_html( $name ); ?>"><?php echo esc_html( $label ); ?>:</label>
	<input
		type="hidden"
		value="<?php echo ( ! empty( $min ) ) ? esc_html( $min ) : 0; ?>"
		step="<?php echo ( isset( $step ) ) ? esc_html( $step ) : ''; ?>"
		min="<?php echo ( isset( $min ) ) ? esc_html( $min ) : ''; ?>"
		max="<?php echo ( isset( $max ) ) ? esc_html( $max ) : ''; ?>"
		name="<?php echo esc_html( $name ); ?>"
		id="<?php echo esc_html( $name ); ?>"
	/> <?php echo ( ! empty( $after ) ) ? esc_html( $after ) : ''; ?>
</p>

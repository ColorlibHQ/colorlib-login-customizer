<?php
/**
 * Sanitization functions for Colorlib Login Customizer.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize color values (hex, rgb, rgba).
 *
 * @param string $color Color value to sanitize.
 * @return string Sanitized color or empty string.
 */
function clc_sanitize_color( string $color ): string {
	$color = trim( $color );

	// Allow empty values.
	if ( empty( $color ) ) {
		return '';
	}

	// Allow WordPress color keywords.
	$allowed_keywords = array( 'transparent', 'initial', 'inherit', 'unset' );
	if ( in_array( strtolower( $color ), $allowed_keywords, true ) ) {
		return strtolower( $color );
	}

	// Hex color (3 or 6 characters).
	if ( preg_match( '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color ) ) {
		return $color;
	}

	// RGB color.
	if ( preg_match( '/^rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\)$/i', $color ) ) {
		return $color;
	}

	// RGBA color.
	if ( preg_match( '/^rgba\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*(0|1|0?\.\d+)\s*\)$/i', $color ) ) {
		return $color;
	}

	return '';
}

/**
 * Sanitize dimension values (px, em, rem, %, vh, vw).
 *
 * @param string $value Dimension value to sanitize.
 * @return string Sanitized value or empty string.
 */
function clc_sanitize_dimension( string $value ): string {
	$value = trim( $value );

	if ( empty( $value ) ) {
		return '';
	}

	// Allow 'unset', 'auto', 'initial', 'inherit'.
	$allowed_keywords = array( 'unset', 'auto', 'initial', 'inherit', 'none' );
	if ( in_array( strtolower( $value ), $allowed_keywords, true ) ) {
		return strtolower( $value );
	}

	// Allow just numbers (will have px added later).
	if ( is_numeric( $value ) ) {
		return $value;
	}

	// Allow number + unit.
	if ( preg_match( '/^-?\d+(\.\d+)?(px|em|rem|%|vh|vw|pt)?$/i', $value ) ) {
		return $value;
	}

	return '';
}

/**
 * Sanitize CSS property value (generic sanitization for borders, shadows, etc.).
 *
 * @param string $value CSS value to sanitize.
 * @return string Sanitized CSS value.
 */
function clc_sanitize_css_value( string $value ): string {
	$value = trim( $value );

	if ( empty( $value ) ) {
		return '';
	}

	// Remove potentially dangerous content.
	$value = wp_strip_all_tags( $value );

	// Remove dangerous CSS expressions.
	$value = preg_replace( '/expression\s*\(/i', '', $value );
	$value = preg_replace( '/javascript\s*:/i', '', $value );
	$value = preg_replace( '/behavior\s*:/i', '', $value );
	$value = preg_replace( '/-moz-binding\s*:/i', '', $value );

	return $value;
}

/**
 * Sanitize URL value.
 *
 * @param string $url URL to sanitize.
 * @return string Sanitized URL.
 */
function clc_sanitize_url( string $url ): string {
	$url = trim( $url );

	if ( empty( $url ) ) {
		return '';
	}

	return esc_url_raw( $url );
}

/**
 * Sanitize text field (single line).
 *
 * @param string $text Text to sanitize.
 * @return string Sanitized text.
 */
function clc_sanitize_text( string $text ): string {
	return sanitize_text_field( $text );
}

/**
 * Sanitize textarea (multi-line text, allows some HTML).
 *
 * @param string $text Text to sanitize.
 * @return string Sanitized text.
 */
function clc_sanitize_textarea( string $text ): string {
	return wp_kses_post( $text );
}

/**
 * Sanitize checkbox/toggle value.
 *
 * @param mixed $value Value to sanitize.
 * @return bool Sanitized boolean value.
 */
function clc_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

/**
 * Sanitize select/radio value against allowed choices.
 *
 * @param string $value   Value to sanitize.
 * @param array  $choices Allowed choices.
 * @param string $default Default value if invalid.
 * @return string Sanitized value.
 */
function clc_sanitize_select( string $value, array $choices, string $default = '' ): string {
	if ( array_key_exists( $value, $choices ) || in_array( $value, $choices, true ) ) {
		return $value;
	}

	return $default;
}

/**
 * Sanitize image URL (must be a valid image).
 *
 * @param string $url Image URL to sanitize.
 * @return string Sanitized image URL.
 */
function clc_sanitize_image( string $url ): string {
	$url = trim( $url );

	if ( empty( $url ) ) {
		return '';
	}

	// Get the file extension.
	$ext = strtolower( pathinfo( wp_parse_url( $url, PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );

	// Allowed image extensions.
	$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico' );

	if ( ! in_array( $ext, $allowed, true ) ) {
		return '';
	}

	return esc_url_raw( $url );
}

/**
 * Sanitize custom CSS.
 *
 * @param string $css CSS to sanitize.
 * @return string Sanitized CSS.
 */
function clc_sanitize_css( string $css ): string {
	if ( empty( $css ) ) {
		return '';
	}

	// Use WordPress built-in CSS sanitization if available.
	if ( function_exists( 'wp_strip_all_tags' ) ) {
		$css = wp_strip_all_tags( $css );
	}

	// Remove potentially dangerous CSS.
	$css = preg_replace( '/expression\s*\(/i', '', $css );
	$css = preg_replace( '/javascript\s*:/i', '', $css );
	$css = preg_replace( '/behavior\s*:/i', '', $css );
	$css = preg_replace( '/-moz-binding\s*:/i', '', $css );
	$css = preg_replace( '/@import/i', '', $css );
	$css = preg_replace( '/url\s*\(\s*["\']?\s*data:/i', 'url(', $css );

	return $css;
}

/**
 * Sanitize columns width array.
 *
 * @param mixed $value Value to sanitize.
 * @return array Sanitized columns width array.
 */
function clc_sanitize_columns_width( $value ): array {
	$defaults = array(
		'left'  => 6,
		'right' => 6,
	);

	if ( ! is_array( $value ) ) {
		return $defaults;
	}

	$sanitized = array();
	$sanitized['left']  = isset( $value['left'] ) ? absint( $value['left'] ) : 6;
	$sanitized['right'] = isset( $value['right'] ) ? absint( $value['right'] ) : 6;

	// Ensure values are between 1 and 11.
	$sanitized['left']  = max( 1, min( 11, $sanitized['left'] ) );
	$sanitized['right'] = max( 1, min( 11, $sanitized['right'] ) );

	return $sanitized;
}

<?php
/**
 * Sanitization functions for Colorlib Login Customizer.
 *
 * Every function here is a Customizer `sanitize_callback`, so it may be handed
 * whatever a client submitted — including null, arrays or objects. They all
 * accept `mixed` and normalise internally rather than type-hinting `string`,
 * which would raise a TypeError under `strict_types` for a null setting.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Coerce an arbitrary setting value to a trimmed string.
 *
 * @param mixed $value Raw value.
 * @return string Scalar value as a trimmed string, or '' when not scalar.
 */
function clc_stringify( $value ): string {
	if ( is_string( $value ) ) {
		return trim( $value );
	}

	if ( is_bool( $value ) ) {
		return $value ? '1' : '';
	}

	if ( is_scalar( $value ) ) {
		return trim( (string) $value );
	}

	return '';
}

/**
 * Characters that must never survive into a CSS declaration.
 *
 * `<` and `>` could close the surrounding <style> element; `{`, `}` and `;`
 * would let a value break out of its declaration or rule block; a backslash
 * enables CSS escape sequences that reconstruct any of the above.
 *
 * @param string $value CSS fragment.
 * @return bool True when the value contains a structural character.
 */
function clc_css_has_structural_chars( string $value ): bool {
	return (bool) preg_match( '/[;{}<>\\\\]/', $value );
}

/**
 * Detect CSS functions that can load or execute external content.
 *
 * @param string $value CSS fragment.
 * @return bool True when a disallowed function is present.
 */
function clc_css_has_unsafe_function( string $value ): bool {
	return (bool) preg_match( '/(url|expression|image-set|-webkit-image-set|element|attr)\s*\(/i', $value );
}

/**
 * Strip directives that can execute script or pull in remote resources.
 *
 * Applied repeatedly until the string stops changing. A single pass is not
 * enough: removing the inner match of a nested token such as
 * `java` + `javascript:` + `script:` rejoins the outer halves into a working
 * `javascript:`. Each pass can only shorten the string, so the loop always
 * terminates; the counter is a defensive backstop that fails closed.
 *
 * @param string $value CSS fragment.
 * @return string Cleaned fragment, or '' if it would not converge.
 */
function clc_css_strip_dangerous_tokens( string $value ): string {
	$patterns = array(
		'/expression\s*\(/i',
		'/javascript\s*:/i',
		'/vbscript\s*:/i',
		'/behaviou?r\s*:/i',
		'/-moz-binding\s*:/i',
		'/@\s*import/i',
		'/@\s*charset/i',
	);

	$passes = 0;

	do {
		$previous = $value;

		$value = (string) preg_replace( $patterns, '', $value );

		/*
		 * Strip a data: URI anywhere inside url(), not just immediately after
		 * the opening paren, so padding the scheme (url(dadata:ta:...)) cannot
		 * smuggle one through. The character class stops at the closing paren,
		 * so this can never reach past the url() it started in.
		 */
		$value = (string) preg_replace( '/url\s*\(\s*["\']?[^)"\']*?data\s*:/i', 'url(', $value );

		++$passes;

		if ( $passes > 100 ) {
			return '';
		}
	} while ( $value !== $previous );

	return $value;
}

/**
 * Escape a value for output inside a CSS declaration.
 *
 * The saved value has already been validated; this is the last line of defence
 * applied at print time, so a legacy option stored before validation existed
 * still cannot break out of the stylesheet.
 *
 * @param string $value CSS declaration value.
 * @return string Safe declaration value.
 */
function clc_escape_css_value( string $value ): string {
	$value = str_replace( array( '<', '>', '{', '}', ';', '\\' ), '', $value );

	return trim( clc_css_strip_dangerous_tokens( $value ) );
}

/**
 * Sanitize color values.
 *
 * Accepts named colors and CSS-wide keywords, 3/4/6/8-digit hex, the legacy
 * comma and modern space-separated forms of rgb()/rgba()/hsl()/hsla(), plus
 * var() and single-level color-mix().
 *
 * @param mixed $color Color value to sanitize.
 * @return string Sanitized color or empty string.
 */
function clc_sanitize_color( $color ): string {
	$color = clc_stringify( $color );

	if ( '' === $color ) {
		return '';
	}

	if ( clc_css_has_structural_chars( $color ) || clc_css_has_unsafe_function( $color ) ) {
		return '';
	}

	$lower = strtolower( $color );

	// Named colors, `transparent`, `currentcolor` and the CSS-wide keywords.
	if ( preg_match( '/^[a-z]+$/', $lower ) ) {
		return $lower;
	}

	// Hex: #rgb, #rgba, #rrggbb, #rrggbbaa.
	if ( preg_match( '/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $color ) ) {
		return $color;
	}

	/*
	 * rgb()/rgba()/hsl()/hsla(). The character class excludes parentheses, so
	 * no nested function can hide inside the argument list.
	 */
	if ( preg_match( '#^(rgba?|hsla?)\(\s*[0-9a-z%.,/\s+-]+\)$#i', $color ) ) {
		return $color;
	}

	// Custom properties, with an optional fallback.
	if ( preg_match( '/^var\(\s*--[a-z0-9_-]+\s*(,[^()]*)?\)$/i', $color ) ) {
		return $color;
	}

	// Single-level color-mix(), e.g. color-mix(in srgb, #fff 40%, #000).
	if ( preg_match( '/^color-mix\(\s*in\s+[a-z0-9-]+\s*,[^()]*\)$/i', $color ) ) {
		return $color;
	}

	return '';
}

/**
 * Sanitize dimension values.
 *
 * Accepts bare numbers, a number with any modern CSS length unit, sizing
 * keywords, and the calc()/clamp()/min()/max()/var() function family.
 *
 * @param mixed $value Dimension value to sanitize.
 * @return string Sanitized value or empty string.
 */
function clc_sanitize_dimension( $value ): string {
	$value = clc_stringify( $value );

	if ( '' === $value ) {
		return '';
	}

	if ( clc_css_has_structural_chars( $value ) || clc_css_has_unsafe_function( $value ) ) {
		return '';
	}

	$lower = strtolower( $value );

	$keywords = array(
		'unset',
		'auto',
		'initial',
		'inherit',
		'none',
		'revert',
		'revert-layer',
		'fit-content',
		'max-content',
		'min-content',
	);

	if ( in_array( $lower, $keywords, true ) ) {
		return $lower;
	}

	// Bare number; a unit is appended later where the property needs one.
	if ( is_numeric( $value ) ) {
		return $value;
	}

	$units = 'px|em|rem|%|vh|vw|vmin|vmax|dvh|dvw|svh|svw|lvh|lvw|pt|pc|cm|mm|in|ch|ex|cap|ic|lh|rlh|q';

	if ( preg_match( '/^-?\d*\.?\d+(' . $units . ')$/i', $value ) ) {
		return $value;
	}

	// Math and custom-property functions.
	if ( preg_match( '/^(calc|clamp|min|max|var)\s*\(/i', $value )
		&& clc_css_parens_balanced( $value )
		&& preg_match( '#^[a-z0-9\s%.,()/*+_-]+$#i', $value )
	) {
		return $value;
	}

	return '';
}

/**
 * Check that parentheses in a CSS fragment are balanced and never go negative.
 *
 * @param string $value CSS fragment.
 * @return bool True when balanced.
 */
function clc_css_parens_balanced( string $value ): bool {
	$depth = 0;

	foreach ( str_split( $value ) as $char ) {
		if ( '(' === $char ) {
			++$depth;
		} elseif ( ')' === $char ) {
			--$depth;

			if ( $depth < 0 ) {
				return false;
			}
		}
	}

	return 0 === $depth;
}

/**
 * Sanitize a CSS property value (borders, shadows, padding, margins, etc.).
 *
 * @param mixed $value CSS value to sanitize.
 * @return string Sanitized CSS value.
 */
function clc_sanitize_css_value( $value ): string {
	$value = clc_stringify( $value );

	if ( '' === $value ) {
		return '';
	}

	$value = wp_strip_all_tags( $value );

	if ( clc_css_has_structural_chars( $value ) || clc_css_has_unsafe_function( $value ) ) {
		return '';
	}

	if ( ! clc_css_parens_balanced( $value ) ) {
		return '';
	}

	return trim( clc_css_strip_dangerous_tokens( $value ) );
}

/**
 * Sanitize URL value.
 *
 * @param mixed $url URL to sanitize.
 * @return string Sanitized URL.
 */
function clc_sanitize_url( $url ): string {
	$url = clc_stringify( $url );

	if ( '' === $url ) {
		return '';
	}

	return esc_url_raw( $url, array( 'http', 'https', 'mailto', 'tel' ) );
}

/**
 * Sanitize text field (single line).
 *
 * @param mixed $text Text to sanitize.
 * @return string Sanitized text.
 */
function clc_sanitize_text( $text ): string {
	return sanitize_text_field( clc_stringify( $text ) );
}

/**
 * Sanitize textarea (multi-line text, allows some HTML).
 *
 * @param mixed $text Text to sanitize.
 * @return string Sanitized text.
 */
function clc_sanitize_textarea( $text ): string {
	return wp_kses_post( clc_stringify( $text ) );
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
 * @param mixed  $value   Value to sanitize.
 * @param array  $choices Allowed choices.
 * @param string $default Default value if invalid.
 * @return string Sanitized value.
 */
function clc_sanitize_select( $value, array $choices, string $default = '' ): string {
	$value = clc_stringify( $value );

	if ( array_key_exists( $value, $choices ) || in_array( $value, $choices, true ) ) {
		return $value;
	}

	return $default;
}

/**
 * Sanitize image URL (must point at a permitted image type).
 *
 * @param mixed $url Image URL to sanitize.
 * @return string Sanitized image URL.
 */
function clc_sanitize_image( $url ): string {
	$url = clc_stringify( $url );

	if ( '' === $url ) {
		return '';
	}

	$path = wp_parse_url( $url, PHP_URL_PATH );

	if ( ! is_string( $path ) || '' === $path ) {
		return '';
	}

	$ext = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );

	$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico' );

	if ( ! in_array( $ext, $allowed, true ) ) {
		return '';
	}

	return esc_url_raw( $url, array( 'http', 'https' ) );
}

/**
 * Sanitize custom CSS.
 *
 * @param mixed $css CSS to sanitize.
 * @return string Sanitized CSS.
 */
function clc_sanitize_css( $css ): string {
	$css = clc_stringify( $css );

	if ( '' === $css ) {
		return '';
	}

	// Removes any tag, including a `</style>` that would end the stylesheet.
	$css = wp_strip_all_tags( $css );

	return clc_css_strip_dangerous_tokens( $css );
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

	$sanitized = array(
		'left'  => isset( $value['left'] ) ? absint( $value['left'] ) : 6,
		'right' => isset( $value['right'] ) ? absint( $value['right'] ) : 6,
	);

	// Ensure values are between 1 and 11.
	$sanitized['left']  = max( 1, min( 11, $sanitized['left'] ) );
	$sanitized['right'] = max( 1, min( 11, $sanitized['right'] ) );

	return $sanitized;
}

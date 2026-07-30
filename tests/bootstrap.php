<?php
/**
 * PHPUnit bootstrap.
 *
 * The sanitization layer is deliberately free of WordPress state — it only
 * calls a handful of core helpers — so it can be unit tested without booting
 * WordPress. The stubs below reproduce the behaviour of those helpers closely
 * enough for these assertions; they are NOT general-purpose replacements.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );
define( 'COLORLIB_LOGIN_CUSTOMIZER_VERSION', '2.3.0' );
define( 'COLORLIB_LOGIN_CUSTOMIZER_BASE', dirname( __DIR__ ) . '/' );
define( 'COLORLIB_LOGIN_CUSTOMIZER_URL', 'https://example.test/wp-content/plugins/colorlib-login-customizer/' );

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Stub of wp_strip_all_tags().
	 *
	 * @param string $text          Text to strip.
	 * @param bool   $remove_breaks Whether to collapse whitespace.
	 * @return string
	 */
	function wp_strip_all_tags( $text, $remove_breaks = false ): string {
		if ( ! is_scalar( $text ) ) {
			return '';
		}

		$text = (string) $text;
		$text = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $text );
		$text = strip_tags( (string) $text );

		if ( $remove_breaks ) {
			$text = preg_replace( '/[\r\n\t ]+/', ' ', (string) $text );
		}

		return trim( (string) $text );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Stub of absint().
	 *
	 * @param mixed $maybeint Value to convert.
	 * @return int
	 */
	function absint( $maybeint ): int {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * Stub of wp_parse_url().
	 *
	 * @param string $url       URL to parse.
	 * @param int    $component Component to retrieve.
	 * @return mixed
	 */
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Stub of sanitize_text_field().
	 *
	 * @param string $str Value to sanitize.
	 * @return string
	 */
	function sanitize_text_field( $str ): string {
		$filtered = wp_strip_all_tags( (string) $str, true );

		return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', $filtered ) );
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	/**
	 * Stub of wp_kses_post(): conservative allowlist of post-content tags.
	 *
	 * @param string $data Value to filter.
	 * @return string
	 */
	function wp_kses_post( $data ): string {
		return strip_tags( (string) $data, '<a><b><strong><em><i><br><p><span><ul><ol><li><h1><h2><h3>' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Stub of esc_url_raw(): strips whitespace and enforces a protocol allowlist.
	 *
	 * @param string     $url       URL to filter.
	 * @param array|null $protocols Allowed protocols.
	 * @return string
	 */
	function esc_url_raw( $url, $protocols = null ): string {
		$url = str_replace( array( ' ', "\t", "\n", "\r" ), '', (string) $url );

		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '#^([a-z][a-z0-9+.-]*):#i', $url, $matches ) ) {
			$allowed = is_array( $protocols ) ? $protocols : array( 'http', 'https', 'mailto', 'tel' );

			if ( ! in_array( strtolower( $matches[1] ), $allowed, true ) ) {
				return '';
			}
		}

		return $url;
	}
}

// Hook registry, option store and the remaining core helpers.
require_once __DIR__ . '/wp-stubs.php';

require_once dirname( __DIR__ ) . '/includes/class-colorlib-login-customizer-sanitization.php';

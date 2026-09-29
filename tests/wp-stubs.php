<?php
/**
 * Minimal WordPress shims for unit testing.
 *
 * These reproduce just enough of WordPress — the hook registry, option
 * storage and the escaping helpers — to execute the plugin's CSS generation
 * and gettext filters without booting WordPress or a database. They are NOT
 * general-purpose replacements for core.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

$GLOBALS['clc_test_hooks']   = array();
$GLOBALS['clc_test_options'] = array();

/**
 * Reset the shim state between tests.
 *
 * @return void
 */
function clc_test_reset(): void {
	$GLOBALS['clc_test_hooks']     = array();
	$GLOBALS['clc_test_options']   = array();
	$GLOBALS['clc_test_localized'] = array();
	$GLOBALS['clc_test_preview']   = false;
}

/**
 * Register a callback against a hook.
 *
 * @param string   $tag      Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Accepted args.
 * @return bool
 */
function add_action( $tag, $callback, $priority = 10, $args = 1 ): bool {
	$GLOBALS['clc_test_hooks'][ $tag ][ $priority ][] = $callback;
	return true;
}

/**
 * Register a filter callback.
 *
 * @param string   $tag      Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Priority.
 * @param int      $args     Accepted args.
 * @return bool
 */
function add_filter( $tag, $callback, $priority = 10, $args = 1 ): bool {
	return add_action( $tag, $callback, $priority, $args );
}

/**
 * Whether any callback (or a specific one) is attached to a hook.
 *
 * @param string        $tag      Hook name.
 * @param callable|bool $callback Optional specific callback.
 * @return bool
 */
function has_action( $tag, $callback = false ): bool {
	if ( ! isset( $GLOBALS['clc_test_hooks'][ $tag ] ) ) {
		return false;
	}

	if ( false === $callback ) {
		return true;
	}

	foreach ( $GLOBALS['clc_test_hooks'][ $tag ] as $callbacks ) {
		foreach ( $callbacks as $registered ) {
			if ( $registered === $callback ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Alias of has_action() for filters.
 *
 * @param string        $tag      Hook name.
 * @param callable|bool $callback Optional specific callback.
 * @return bool
 */
function has_filter( $tag, $callback = false ): bool {
	return has_action( $tag, $callback );
}

/**
 * Fire every callback attached to a hook.
 *
 * @param string $tag  Hook name.
 * @param mixed  ...$args Arguments.
 * @return void
 */
function do_action( $tag, ...$args ): void {
	if ( ! isset( $GLOBALS['clc_test_hooks'][ $tag ] ) ) {
		return;
	}

	$hooks = $GLOBALS['clc_test_hooks'][ $tag ];
	ksort( $hooks );

	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

/**
 * Run a value through every filter attached to a hook.
 *
 * @param string $tag   Hook name.
 * @param mixed  $value Value to filter.
 * @param mixed  ...$args Extra arguments.
 * @return mixed
 */
function apply_filters( $tag, $value, ...$args ) {
	if ( ! isset( $GLOBALS['clc_test_hooks'][ $tag ] ) ) {
		return $value;
	}

	$hooks = $GLOBALS['clc_test_hooks'][ $tag ];
	ksort( $hooks );

	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$value = call_user_func_array( $callback, array_merge( array( $value ), $args ) );
		}
	}

	return $value;
}

/**
 * Read a stored option.
 *
 * @param string $name    Option name.
 * @param mixed  $default Default value.
 * @return mixed
 */
function get_option( $name, $default = false ) {
	return $GLOBALS['clc_test_options'][ $name ] ?? $default;
}

/**
 * Write an option.
 *
 * @param string $name  Option name.
 * @param mixed  $value Value.
 * @return bool
 */
function update_option( $name, $value ): bool {
	$GLOBALS['clc_test_options'][ $name ] = $value;
	return true;
}

/**
 * Merge args over defaults.
 *
 * @param mixed $args     Provided args.
 * @param array $defaults Default args.
 * @return array
 */
function wp_parse_args( $args, $defaults = array() ): array {
	if ( ! is_array( $args ) ) {
		$args = array();
	}

	return array_merge( $defaults, $args );
}

/** @return string */
function esc_html( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/** @return string */
function esc_attr( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/** @return string */
function esc_url( $url ): string {
	return esc_url_raw( $url );
}

/** @return string */
function site_url( $path = '' ): string {
	return 'https://example.test' . $path;
}

/** @return string */
function get_site_url(): string {
	return 'https://example.test';
}

/** @return string */
function home_url( $path = '' ): string {
	return 'https://example.test' . $path;
}

/** @return string */
function admin_url( $path = '' ): string {
	return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
}

/** @return string */
function plugins_url( $path = '', $plugin = '' ): string {
	return 'https://example.test/wp-content/plugins/colorlib-login-customizer' . $path;
}

/** @return string */
function trailingslashit( $string ): string {
	return rtrim( (string) $string, '/\\' ) . '/';
}

/** @return string */
function plugin_basename( $file ): string {
	return basename( dirname( (string) $file ) ) . '/' . basename( (string) $file );
}

/** @return bool */
function register_activation_hook( $file, $callback ): bool {
	return true;
}

/** @return bool */
function load_plugin_textdomain( $domain, $deprecated = false, $path = '' ): bool {
	return true;
}

/** @return bool */
function is_customize_preview(): bool {
	return ! empty( $GLOBALS['clc_test_preview'] );
}

/** @return bool */
function is_multisite(): bool {
	return false;
}

/** @return bool */
function wp_localize_script( $handle, $name, $data ): bool {
	$GLOBALS['clc_test_localized'][ $name ] = $data;
	return true;
}

/** @return void */
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ): void {
	$GLOBALS['clc_test_styles'][ $handle ] = $src;
}

/** @return void */
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $footer = false ): void {
	$GLOBALS['clc_test_scripts'][ $handle ] = $src;
}

/** @return string */
function __( $text, $domain = 'default' ): string {
	return (string) $text;
}

/** @return string */
function esc_attr__( $text, $domain = 'default' ): string {
	return esc_attr( $text );
}

/** @return string */
function esc_html__( $text, $domain = 'default' ): string {
	return esc_html( $text );
}

/** @return string */
function _x( $text, $context, $domain = 'default' ): string {
	return (string) $text;
}

/** @return string */
function get_bloginfo( $show = '', $filter = 'raw' ): string {
	return 'Example Site';
}

/** @return string */
function get_locale(): string {
	return 'en_US';
}

/** @return string */
function sanitize_html_class( $class, $fallback = '' ): string {
	return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $class );
}

/** @return string */
function __return_empty_string(): string {
	return '';
}

/** @return bool */
function __return_false(): bool {
	return false;
}

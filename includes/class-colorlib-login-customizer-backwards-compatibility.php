<?php
/**
 * Backwards compatibility handling for legacy option structures.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles migration of legacy plugin options to the current format.
 */
class CLC_Backwards_Compatibility {

	/**
	 * The singleton instance of the class.
	 *
	 * @var CLC_Backwards_Compatibility
	 */
	public static $instance;

	/**
	 * Constructor. Registers backwards compatibility hooks.
	 */
	public function __construct() {

		// Backwards compatibility to ver. 1.2.96.
		// Add action to admin init so we can update the options if needed.
		// Filter clc_backwards_compatibility_front for front-end.
		add_action( 'admin_init', array( $this, 'backwards_update_options' ), 25 );
		add_filter( 'clc_backwards_compatibility_front', array( $this, 'logo_settings_compatibility' ), 16, 1 );
	}

	/**
	 * Ensure the logo-settings option exists for older saved options.
	 *
	 * @param array $options The plugin options array.
	 *
	 * @return mixed
	 */
	public function logo_settings_compatibility( $options ) {

		if ( ! isset( $options['logo-settings'] ) ) {
			if ( isset( $options['hide-logo'] ) && $options['hide-logo'] ) {
				$options['logo-settings'] = 'hide-logo';
			} elseif ( isset( $options['use-text-logo'] ) && $options['use-text-logo'] ) {
					$options['logo-settings'] = 'show-text-only';
			} else {
				$options['logo-settings'] = 'show-image-only';
			}
		}
		return $options;
	}

	/**
	 * Update our options on admin init if needed
	 */
	public function backwards_update_options() {
		// Backwards compatibility on admin_init.
		$options = get_option( 'clc-options', array() );

		if ( ! isset( $options['logo-settings'] ) ) {
			if ( isset( $options['hide-logo'] ) && $options['hide-logo'] ) {
				$options['logo-settings'] = 'hide-logo';
			} elseif ( isset( $options['use-text-logo'] ) && $options['use-text-logo'] ) {
					$options['logo-settings'] = 'show-text-only';
			} else {
				$options['logo-settings'] = 'show-image-only';
			}

			update_option( 'clc-options', $options );
		}
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since 1.0.0
	 *
	 * @return object The Modula_Deeplink object.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof CLC_Backwards_Compatibility ) ) {
			self::$instance = new CLC_Backwards_Compatibility();
		}

		return self::$instance;
	}
}

$clc_backwards_compatibility = CLC_Backwards_Compatibility::get_instance();

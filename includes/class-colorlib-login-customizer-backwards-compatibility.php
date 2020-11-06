<?php

/**
 *
 *
 */
class CLC_Backwards_Compatibility {

	public static $instance;

	function __construct() {

		// Backwards compatibility to ver. 1.2.96
		// Add action to admin init so we can update the options if needed
		// Filter clc_backwards_compatibility_front for front-end
		add_action( 'admin_init', array( $this, 'backwards_update_options' ), 25 );
		add_filter( 'clc_backwards_compatibility_front', array( $this, 'logo_settings_compatibility' ), 16, 1 );

	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function logo_settings_compatibility( $options ) {

		if ( !isset( $options['logo-settings'] ) ) {
			if ( isset( $options['hide-logo'] ) && $options['hide-logo'] ) {
				$options['logo-settings'] = 'hide-logo';
			} else {
				if ( isset( $options['use-text-logo'] ) && $options['use-text-logo'] ) {
					$options['logo-settings'] = 'show-text-only';
				} else {
					$options['logo-settings'] = 'show-image-only';
				}
			}

			return $options;
		}
		// If we don't have to update anything return false
		// So that the update_option function won't trigger
		return false;
	}

	/**
	 * Update our options on admin init if needed
	 */
	public function backwards_update_options(){
		// Backwards compatibility on admin_init
		$options = get_option( 'clc-options', array() );

		if ( !isset( $options['logo-settings'] ) ) {
			if ( isset( $options['hide-logo'] ) && $options['hide-logo'] ) {
				$options['logo-settings'] = 'hide-logo';
			} else {
				if ( isset( $options['use-text-logo'] ) && $options['use-text-logo'] ) {
					$options['logo-settings'] = 'show-text-only';
				} else {
					$options['logo-settings'] = 'show-image-only';
				}
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
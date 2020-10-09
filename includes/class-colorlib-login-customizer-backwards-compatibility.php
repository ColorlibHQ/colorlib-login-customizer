<?php

/**
 *
 *
 */
class CLC_Backwards_Compatibility {

	public static $instance;

	function __construct() {

		// Backwards compatibility to ver. 1.2.96
		// Filter clc_backwards_compatibility_front for front-end
		// Filter clc_backwards_compatibility_options for setting options

		add_filter( 'clc_backwards_compatibility_options', array( $this, 'logo_settings_compatibility' ), 16, 1 );
		add_filter( 'clc_backwards_compatibility_front', array( $this, 'logo_settings_compatibility' ), 16, 1 );

	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function logo_settings_compatibility($options){


		if ( isset( $options['hide-logo'] ) && $options['hide-logo'] ) {
			$options['logo-settings'] = 'hide-logo';
			unset($options['hide-logo']);
		} else {
			if ( isset( $options['use-text-logo'] ) && $options['use-text-logo'] ) {
				$options['logo-settings'] = 'show-text-only';
				unset($options['use-text-logo']);
			} else {
				$options['logo-settings'] = 'show-image-only';
			}
		}

		return $options;
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
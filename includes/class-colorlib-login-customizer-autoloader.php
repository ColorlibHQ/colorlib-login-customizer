<?php
/**
 * Autoloader for the Colorlib Login Customizer plugin classes.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Colorlib_Login_Customizer_Autoloader
 */
class Colorlib_Login_Customizer_Autoloader {
	/**
	 * Colorlib_Login_Customizer_Autoloader constructor.
	 */
	public function __construct() {
		spl_autoload_register( array( $this, 'load' ) );
	}

	/**
	 * Autoload a plugin class file when it is requested.
	 *
	 * @param string $class The fully qualified class name to load.
	 */
	public function load( $class ) {
		$parts = explode( '_', $class );
		$bind  = implode( '-', $parts );

		if ( 'Colorlib' == $parts[0] ) {
			/*
			 * Core library autoload.
			 */
			$directories = array(
				COLORLIB_LOGIN_CUSTOMIZER_BASE . '/includes',
				COLORLIB_LOGIN_CUSTOMIZER_BASE . '/includes/lib',
				COLORLIB_LOGIN_CUSTOMIZER_BASE . '/includes/lib/controls',
			);

			foreach ( $directories as $directory ) {
				if ( file_exists( $directory . '/class-' . strtolower( $bind ) . '.php' ) ) {
					require_once $directory . '/class-' . strtolower( $bind ) . '.php';

					return;
				}
			}
		}
	}
}

$autoloader = new Colorlib_Login_Customizer_Autoloader();

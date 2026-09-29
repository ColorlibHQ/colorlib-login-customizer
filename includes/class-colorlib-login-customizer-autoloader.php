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
		/*
		 * Only this plugin's classes. A bare `Colorlib_` match also caught
		 * other Colorlib plugins' classes and cost three file_exists() calls
		 * for each of them.
		 */
		if ( 0 !== strpos( $class, 'Colorlib_Login_Customizer' ) ) {
			return;
		}

		$file = 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

		$directories = array(
			COLORLIB_LOGIN_CUSTOMIZER_BASE . 'includes/',
			COLORLIB_LOGIN_CUSTOMIZER_BASE . 'includes/lib/',
			COLORLIB_LOGIN_CUSTOMIZER_BASE . 'includes/lib/controls/',
		);

		foreach ( $directories as $directory ) {
			if ( file_exists( $directory . $file ) ) {
				require_once $directory . $file;

				return;
			}
		}
	}
}

// Not assigned to a variable: plugin files load at global scope, and a bare
// `$autoloader` global could clobber (or be clobbered by) another plugin's.
new Colorlib_Login_Customizer_Autoloader();

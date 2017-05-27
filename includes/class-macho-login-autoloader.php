<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Macho_Login_Autoloader
 */
class Macho_Login_Autoloader {
	/**
	 * Macho_Login_Autoloader constructor.
	 */
	public function __construct() {
		spl_autoload_register( array( $this, 'load' ) );
	}

	/**
	 * @param $class
	 */
	public function load( $class ) {
		$parts = explode( '_', $class );
		$bind  = implode( '-', $parts );

		if ( $parts[0] == 'Macho' || $parts[0] == 'Epsilon' ) {

			/*
			 * Core library autoload.
			 */
			$directories = array(
				MACHO_LOGIN_BASE . '/includes',
				MACHO_LOGIN_BASE . '/includes/lib',
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

$autoloader = new Macho_Login_Autoloader();

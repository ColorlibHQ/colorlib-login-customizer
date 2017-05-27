<?php
/*
 * Plugin Name: Macho Login
 * Version: 1.0
 * Plugin URI: https://www.machothemes.com/plugin/macho-login
 * Description: Login page customization
 * Author: Macho Themes
 * Author URI: https://www.machothemes.com
 * Requires at least: 4.0
 * Tested up to: 4.7
 *
 * Text Domain: macho-login
 * Domain Path: /languages/
 *
 * @package WordPress
 * @author Macho Themes
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MACHO_LOGIN_BASE', plugin_dir_path( __FILE__ ) );
define( 'MACHO_LOGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MACHO_LOGIN_SITE', rtrim( ABSPATH, '\\/' ) );

// Load plugin class files
require_once 'includes/class-macho-login-autoloader.php';

/**
 * Returns the main instance of Macho_Login to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Macho_Login
 */
function Macho_Login() {
	$instance = Macho_Login::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Macho_Login_Settings::instance( $instance );
	}

	return $instance;
}

Macho_Login();
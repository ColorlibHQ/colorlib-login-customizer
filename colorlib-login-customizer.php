<?php
/*
 * Plugin Name: Colorlib Login Customizer
 * Version: 1.1.0
 * Description: Colorlib Login Customizer is an awesome and intuitive plugin that helps you personalize your login form directly from the Customizer. The plugin fully supports the Live Customizer feature and you can see all the changes in real time and edit them.
 * Author: Colorlib
 * Author URI: https://colorlib.com/
 * Requires at least: 4.0
 * Tested up to: 4.7
 *
 * Text Domain: colorlib-login-customizer
 * Domain Path: /languages/
 *
 * @package WordPress
 * @author Colorlib
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'COLORLIB_LOGIN_CUSTOMIZER_BASE', plugin_dir_path( __FILE__ ) );
define( 'COLORLIB_LOGIN_CUSTOMIZER_URL', plugin_dir_url( __FILE__ ) );
define( 'COLORLIB_LOGIN_CUSTOMIZER_SITE', rtrim( ABSPATH, '\\/' ) );

// Load plugin class files
require_once 'includes/class-colorlib-login-customizer-autoloader.php';

/**
 * Returns the main instance of Colorlib_Login_Customizer to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Colorlib_Login_Customizer
 */
function colorlib_login_customizer() {
	$instance = Colorlib_Login_Customizer::instance( __FILE__, '1.1.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Colorlib_Login_Customizer_Settings::instance( $instance );
	}

	return $instance;
}

colorlib_login_customizer();

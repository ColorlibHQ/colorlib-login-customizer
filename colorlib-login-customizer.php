<?php
/**
* Plugin Name: Colorlib Login Customizer
* Version: 1.2.95
* Description: Colorlib Login Customizer is an awesome and intuitive plugin that helps you personalize your login form directly from the Customizer. The plugin fully supports the Live Customizer feature and you can see all the changes in real time and edit them.
* Author: Colorlib
* Author URI: https://colorlib.com/
* Tested up to: 5.3
* Requires: 4.6 or higher
* License: GPLv3 or later
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
* Requires PHP: 5.6
* Text Domain: colorlib-login-customizer
* Domain Path: /languages
*
* Copyright 2018-2019 Colorlib support@colorlib.com
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License, version 3, as
* published by the Free Software Foundation.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
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
	$instance = Colorlib_Login_Customizer::instance( __FILE__, '1.2.92' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Colorlib_Login_Customizer_Settings::instance( $instance );
	}

	return $instance;
}

function clc_check_for_review() {
	if ( ! is_admin() ) {
		return;
	}
	require_once COLORLIB_LOGIN_CUSTOMIZER_BASE . 'includes/class-colorlib-login-customizer-review.php';

	CLC_Review::get_instance( array(
		'slug' => 'colorlib-login-customizer',
	) );
}

colorlib_login_customizer();
clc_check_for_review();
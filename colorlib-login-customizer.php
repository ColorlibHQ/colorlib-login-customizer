<?php
/**
 * Plugin Name: Colorlib Login Customizer
 * Version: 2.1.0
 * Description: Colorlib Login Customizer is an awesome and intuitive plugin that helps you personalize your login form directly from the Customizer. The plugin fully supports the Live Customizer feature and you can see all the changes in real time and edit them.
 * Author: Colorlib
 * Author URI: https://colorlib.com/
 * Tested up to: 6.9
 * Requires at least: 6.0
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires PHP: 8.0
 * Text Domain: colorlib-login-customizer
 * Domain Path: /languages
 *
 * Copyright 2018-2025 Colorlib support@colorlib.com
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

// PHP version check.
if ( version_compare( PHP_VERSION, '8.0.0', '<' ) ) {
	add_action( 'admin_notices', 'clc_php_version_notice' );
	return;
}

/**
 * Display admin notice for PHP version requirement.
 *
 * @return void
 */
function clc_php_version_notice(): void {
	?>
	<div class="error">
		<p>
			<?php
			printf(
				/* translators: 1: Required PHP version, 2: Current PHP version */
				esc_html__( 'Colorlib Login Customizer requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade your PHP version.', 'colorlib-login-customizer' ),
				'8.0',
				PHP_VERSION
			);
			?>
		</p>
	</div>
	<?php
}

define( 'COLORLIB_LOGIN_CUSTOMIZER_VERSION', '2.1.0' );
define( 'COLORLIB_LOGIN_CUSTOMIZER_BASE', plugin_dir_path( __FILE__ ) );
define( 'COLORLIB_LOGIN_CUSTOMIZER_URL', plugin_dir_url( __FILE__ ) );

// Load plugin class files.
require_once 'includes/class-colorlib-login-customizer-autoloader.php';
require_once 'includes/class-colorlib-login-customizer-backwards-compatibility.php';
require_once 'includes/class-colorlib-login-customizer-sanitization.php';

/**
 * Returns the main instance of Colorlib_Login_Customizer to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object Colorlib_Login_Customizer
 */
function colorlib_login_customizer(): Colorlib_Login_Customizer {
	$instance = Colorlib_Login_Customizer::instance( __FILE__, COLORLIB_LOGIN_CUSTOMIZER_VERSION );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = Colorlib_Login_Customizer_Settings::instance( $instance );
	}

	return $instance;
}

function clc_check_for_review(): void {
	require_once COLORLIB_LOGIN_CUSTOMIZER_BASE . 'includes/class-colorlib-login-customizer-review.php';

	CLC_Review::get_instance( array(
		'slug' => 'colorlib-login-customizer',
	) );
}

add_action( 'admin_init', 'clc_check_for_review' );
colorlib_login_customizer();

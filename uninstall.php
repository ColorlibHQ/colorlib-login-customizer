<?php
/**
 * Colorlib Login Customizer Uninstall
 *
 * This file runs when the plugin is uninstalled (deleted).
 * This will not run when the plugin is deactivated.
 * Cleans up all plugin data from the database.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

// If plugin is not being uninstalled, exit (do nothing).
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete main plugin options.
delete_option( 'clc-options' );
delete_option( 'colorlib-login-customizer_version' );

// Delete review notice options and transients.
delete_option( 'colorlib-login-customizer_review_notice' );
delete_transient( 'colorlib-login-customizer_review_notice' );

// Clean up any user meta for dismissed notices.
$users = get_users( array( 'fields' => 'ID' ) );
foreach ( $users as $user_id ) {
	delete_user_meta( $user_id, 'colorlib-login-customizer_dismiss_notice' );
}

// Clear object cache.
wp_cache_flush();

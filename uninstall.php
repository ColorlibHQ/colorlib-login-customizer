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

/**
 * Remove every option and transient the plugin creates on a single site.
 *
 * Keep this list in sync with the keys written by the plugin:
 * - `clc-options`                      main settings blob.
 * - `clc-admin-menu-location`          admin menu placement preference.
 * - `colorlib-login-customizer_version` version stamp written on activation.
 * - `clc_review`                       install-date transient for the review notice.
 *
 * @return void
 */
function clc_uninstall_site_data(): void {
	delete_option( 'clc-options' );
	delete_option( 'clc-admin-menu-location' );
	delete_option( 'colorlib-login-customizer_version' );

	delete_transient( 'clc_review' );
}

if ( is_multisite() ) {
	$clc_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $clc_sites as $clc_site_id ) {
		switch_to_blog( (int) $clc_site_id );
		clc_uninstall_site_data();
		restore_current_blog();
	}
} else {
	clc_uninstall_site_data();
}

/*
 * Remove the per-user review-notice state for every user in one query rather
 * than loading every user ID into memory.
 */
delete_metadata( 'user', 0, 'clc_review_state', '', true );

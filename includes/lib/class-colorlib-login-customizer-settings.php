<?php
/**
 * Settings page handler for Colorlib Login Customizer.
 *
 * @package Colorlib_Login_Customizer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the plugin admin menu item and the plugins-screen settings link.
 */
class Colorlib_Login_Customizer_Settings {

	/**
	 * The single instance of Colorlib_Login_Customizer_Settings.
	 *
	 * @var    object
	 * @access   private
	 * @since    1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var    object
	 * @access   public
	 * @since    1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Constructor.
	 *
	 * @param object $parent The main plugin object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Tools: export / import / reset and the menu-location preference.
		add_action( 'admin_post_clc_export_settings', array( $this, 'handle_export' ) );
		add_action( 'admin_post_clc_import_settings', array( $this, 'handle_import' ) );
		add_action( 'admin_post_clc_reset_settings', array( $this, 'handle_reset' ) );
		add_action( 'admin_post_clc_save_display', array( $this, 'handle_save_display' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);
	}

	/**
	 * Get the stored admin-menu location preference.
	 *
	 * @return string One of 'top', 'settings', 'appearance'.
	 */
	public function get_menu_location() {
		$location = get_option( 'clc-admin-menu-location', 'top' );

		return in_array( $location, array( 'top', 'settings', 'appearance' ), true ) ? $location : 'top';
	}

	/**
	 * Build the URL of the plugin settings page for the current menu location.
	 *
	 * @return string
	 */
	public function get_settings_url() {
		$slug     = $this->parent->_token . '_settings';
		$location = $this->get_menu_location();

		if ( 'settings' === $location ) {
			return admin_url( 'options-general.php?page=' . $slug );
		}

		if ( 'appearance' === $location ) {
			return admin_url( 'themes.php?page=' . $slug );
		}

		return admin_url( 'admin.php?page=' . $slug );
	}


	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		$page_title = esc_html__( 'Colorlib Login Customizer', 'colorlib-login-customizer' );
		$menu_title = esc_html__( 'Login Customizer', 'colorlib-login-customizer' );
		$capability = 'manage_options';
		$slug       = $this->parent->_token . '_settings';
		$callback   = array( $this, 'settings_page' );
		$location   = $this->get_menu_location();

		if ( 'settings' === $location ) {
			add_options_page( $page_title, $menu_title, $capability, $slug, $callback );
		} elseif ( 'appearance' === $location ) {
			add_theme_page( $page_title, $menu_title, $capability, $slug, $callback );
		} else {
			add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, 'dashicons-share-alt' );
		}
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 *
	 * @return array        Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( $this->get_settings_url() ) . '">' . esc_html__( 'Settings', 'colorlib-login-customizer' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page() {

		$customize_url = add_query_arg( 'url', rawurlencode( wp_login_url() ), admin_url( 'customize.php' ) );
		$action_url    = admin_url( 'admin-post.php' );
		$location      = $this->get_menu_location();

		echo '<div class="wrap" id="' . esc_attr( $this->parent->_token ) . '_settings">';
		echo '<h1>' . esc_html__( 'Colorlib Login Customizer', 'colorlib-login-customizer' ) . '</h1>';
		echo '<p>' . esc_html__( 'Customize your WordPress login page from the Customizer, with a live preview.', 'colorlib-login-customizer' ) . '</p>';
		echo '<p><a href="' . esc_url( $customize_url ) . '" class="button button-primary">' . esc_html__( 'Start Customizing!', 'colorlib-login-customizer' ) . '</a></p>';

		// Export / Import.
		echo '<hr><h2>' . esc_html__( 'Export / Import settings', 'colorlib-login-customizer' ) . '</h2>';
		echo '<p>' . esc_html__( 'Move your login design between sites, or keep a backup.', 'colorlib-login-customizer' ) . '</p>';

		echo '<form method="post" action="' . esc_url( $action_url ) . '" style="margin-bottom:12px;">';
		wp_nonce_field( 'clc_export_settings' );
		echo '<input type="hidden" name="action" value="clc_export_settings" />';
		echo '<button type="submit" class="button">' . esc_html__( 'Export settings', 'colorlib-login-customizer' ) . '</button>';
		echo '</form>';

		echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( $action_url ) . '">';
		wp_nonce_field( 'clc_import_settings' );
		echo '<input type="hidden" name="action" value="clc_import_settings" />';
		echo '<input type="file" name="clc_import_file" accept="application/json,.json" required /> ';
		echo '<button type="submit" class="button">' . esc_html__( 'Import settings', 'colorlib-login-customizer' ) . '</button>';
		echo '</form>';

		// Admin menu location.
		echo '<hr><h2>' . esc_html__( 'Admin menu location', 'colorlib-login-customizer' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( $action_url ) . '">';
		wp_nonce_field( 'clc_save_display' );
		echo '<input type="hidden" name="action" value="clc_save_display" />';
		echo '<select name="clc_menu_location">';
		$choices = array(
			'top'        => __( 'Top level menu', 'colorlib-login-customizer' ),
			'settings'   => __( 'Under Settings', 'colorlib-login-customizer' ),
			'appearance' => __( 'Under Appearance', 'colorlib-login-customizer' ),
		);
		foreach ( $choices as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"';
			selected( $location, $value );
			echo '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> ';
		echo '<button type="submit" class="button">' . esc_html__( 'Save', 'colorlib-login-customizer' ) . '</button>';
		echo '</form>';

		// Reset.
		echo '<hr><h2>' . esc_html__( 'Reset', 'colorlib-login-customizer' ) . '</h2>';
		echo '<p>' . esc_html__( 'Remove all customizations and restore the default login design.', 'colorlib-login-customizer' ) . '</p>';
		echo '<form method="post" action="' . esc_url( $action_url ) . '" onsubmit="return confirm(\'' . esc_js( __( 'Reset all Login Customizer settings to defaults? This cannot be undone.', 'colorlib-login-customizer' ) ) . '\');">';
		wp_nonce_field( 'clc_reset_settings' );
		echo '<input type="hidden" name="action" value="clc_reset_settings" />';
		echo '<button type="submit" class="button button-link-delete">' . esc_html__( 'Reset to defaults', 'colorlib-login-customizer' ) . '</button>';
		echo '</form>';

		echo '</div>';
	}

	/**
	 * Handle the "Export settings" request: stream the saved options as a JSON file.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'colorlib-login-customizer' ) );
		}

		check_admin_referer( 'clc_export_settings' );

		$options = get_option( 'clc-options', array() );
		$json    = wp_json_encode( $options );

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=colorlib-login-customizer-' . gmdate( 'Y-m-d' ) . '.json' );
		header( 'Content-Length: ' . strlen( $json ) );

		echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw JSON file download; escaping would corrupt the file.
		exit;
	}

	/**
	 * Handle the "Import settings" request: validate, sanitize and store the upload.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'colorlib-login-customizer' ) );
		}

		check_admin_referer( 'clc_import_settings' );

		$tmp  = isset( $_FILES['clc_import_file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['clc_import_file']['tmp_name'] ) ) : '';
		$size = isset( $_FILES['clc_import_file']['size'] ) ? (int) $_FILES['clc_import_file']['size'] : 0;

		if ( '' === $tmp || ! is_uploaded_file( $tmp ) || $size <= 0 || $size > 100000 ) {
			$this->redirect_with_status( 'import_error' );
		}

		$raw  = file_get_contents( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading a validated uploaded temp file.
		$data = json_decode( (string) $raw, true );

		if ( ! is_array( $data ) ) {
			$this->redirect_with_status( 'import_error' );
		}

		update_option( 'clc-options', $this->sanitize_imported_options( $data ) );
		$this->redirect_with_status( 'imported' );
	}

	/**
	 * Handle the "Reset to defaults" request.
	 *
	 * @return void
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'colorlib-login-customizer' ) );
		}

		check_admin_referer( 'clc_reset_settings' );

		delete_option( 'clc-options' );
		$this->redirect_with_status( 'reset' );
	}

	/**
	 * Handle saving the admin-menu location preference.
	 *
	 * @return void
	 */
	public function handle_save_display() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'colorlib-login-customizer' ) );
		}

		check_admin_referer( 'clc_save_display' );

		$location = isset( $_POST['clc_menu_location'] ) ? sanitize_text_field( wp_unslash( $_POST['clc_menu_location'] ) ) : 'top';

		if ( ! in_array( $location, array( 'top', 'settings', 'appearance' ), true ) ) {
			$location = 'top';
		}

		update_option( 'clc-admin-menu-location', $location );
		$this->redirect_with_status( 'display_saved' );
	}

	/**
	 * Whitelist + sanitize an imported settings array against the plugin defaults.
	 *
	 * Unknown keys are dropped, and each value is sanitized according to the kind
	 * of setting it represents, so an import file cannot inject arbitrary data.
	 *
	 * @param array $data Decoded import data.
	 * @return array Clean options array.
	 */
	public function sanitize_imported_options( $data ) {
		$defaults = $this->parent->get_defaults();
		$url_keys = array( 'custom-logo', 'custom-background', 'custom-background-link', 'custom-background-form', 'form-background-image' );
		$clean    = array();

		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $defaults ) ) {
				continue;
			}

			if ( is_bool( $defaults[ $key ] ) ) {
				$clean[ $key ] = clc_sanitize_checkbox( $value );
				continue;
			}

			if ( is_array( $value ) ) {
				$clean[ $key ] = clc_sanitize_columns_width( $value );
				continue;
			}

			$value = (string) $value;

			if ( 'custom-css' === $key ) {
				$clean[ $key ] = clc_sanitize_css( $value );
			} elseif ( 'above-form-text' === $key || 'footer-text' === $key ) {
				$clean[ $key ] = clc_sanitize_textarea( $value );
			} elseif ( in_array( $key, $url_keys, true ) || preg_match( '/-url$/', $key ) ) {
				$clean[ $key ] = clc_sanitize_url( $value );
			} elseif ( false !== strpos( $key, 'color' ) ) {
				$clean[ $key ] = clc_sanitize_color( $value );
			} else {
				$clean[ $key ] = clc_sanitize_css_value( $value );
			}
		}

		return $clean;
	}

	/**
	 * Redirect back to the settings page with a status flag.
	 *
	 * @param string $status Status slug.
	 * @return void
	 */
	private function redirect_with_status( $status ) {
		wp_safe_redirect( add_query_arg( 'clc_status', $status, $this->get_settings_url() ) );
		exit;
	}

	/**
	 * Show a status notice after an export/import/reset/display action.
	 *
	 * @return void
	 */
	public function admin_notices() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only display of a post-redirect status flag; the action itself was nonce-verified.
		if ( ! isset( $_GET['clc_status'], $_GET['page'] ) ) {
			return;
		}

		if ( ( $this->parent->_token . '_settings' ) !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		$status = sanitize_text_field( wp_unslash( $_GET['clc_status'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$messages = array(
			'imported'      => array( 'success', __( 'Settings imported successfully.', 'colorlib-login-customizer' ) ),
			'import_error'  => array( 'error', __( 'Import failed. Please upload a valid settings file exported from this plugin.', 'colorlib-login-customizer' ) ),
			'reset'         => array( 'success', __( 'Settings reset to defaults.', 'colorlib-login-customizer' ) ),
			'display_saved' => array( 'success', __( 'Admin menu location saved.', 'colorlib-login-customizer' ) ),
		);

		if ( ! isset( $messages[ $status ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $messages[ $status ][0] ),
			esc_html( $messages[ $status ][1] )
		);
	}

	/**
	 * Main Colorlib_Login_Customizer_Settings Instance
	 *
	 * Ensures only one instance of Colorlib_Login_Customizer_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see   Colorlib_Login_Customizer()
	 *
	 * @param  object $parent The main plugin object.
	 * @return Colorlib_Login_Customizer_Settings Main instance.
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'colorlib-login-customizer' ), esc_html( $this->parent->_version ) );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'colorlib-login-customizer' ), esc_html( $this->parent->_version ) );
	} // End __wakeup()
}

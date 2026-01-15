<?php
declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Colorlib_Login_Customizer {

	/**
	 * The single instance of Colorlib_Login_Customizer.
	 *
	 * @var self|null
	 */
	private static ?self $_instance = null;

	/**
	 * Settings class object
	 *
	 * @var Colorlib_Login_Customizer_Settings|null
	 */
	public ?Colorlib_Login_Customizer_Settings $settings = null;

	/**
	 * The version number.
	 *
	 * @var string
	 */
	public string $_version;

	/**
	 * The token.
	 *
	 * @var string
	 */
	public string $_token;

	/**
	 * The main plugin file.
	 *
	 * @var string
	 */
	public string $file;

	/**
	 * The main plugin directory.
	 *
	 * @var string
	 */
	public string $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var string
	 */
	public string $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var string
	 */
	public string $assets_url;

	/**
	 * Suffix for Javascripts.
	 *
	 * @var string
	 */
	public string $script_suffix;

	/**
	 * Prefix base of plugin.
	 *
	 * @var string
	 */
	public string $base;

	/**
	 * CLC WP options name
	 *
	 * @var string
	 */
	public string $key_name;

	/**
	 * Constructor function.
	 *
	 * @param string $file    Main plugin file path.
	 * @param string $version Plugin version.
	 */
	public function __construct( string $file = '', string $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'colorlib-login-customizer';
		$this->base     = 'clc_';
		$this->key_name = 'clc-options';

		// Load plugin environment variables
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// Remove this after Grunt
		$this->script_suffix = '';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		add_action( 'admin_init', array( $this, 'redirect_customizer' ) );

		// Load customizer settings
		add_action( 'customize_register', array( $this, 'load_customizer' ), 10, 1 );

		add_filter( 'template_include', array( $this, 'change_template_if_necessary' ), 99 );

		// Handle localisation
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// Generate plugins css
		add_action( 'init', array( $this, 'load_customizer_css' ) );

		// Compatibility fix with All In One WP Security
        add_action('init', array($this, 'clc_aio_wp_security_comp_fix'));

	} // End __construct ()

	/**
	 * Load the customizer controls.
	 *
	 * @param WP_Customize_Manager $manager Customizer manager instance.
	 * @return void
	 */
	public function load_customizer( WP_Customize_Manager $manager ): void {
		new Colorlib_Login_Customizer_Customizer( $this, $manager );
	}

	/**
	 * Load customizer CSS generation.
	 *
	 * @return void
	 */
	public function load_customizer_css(): void {
		new Colorlib_Login_Customizer_CSS_Customization();
	}

	/**
	 * Hook to redirect the page for the Customizer.
	 *
	 * @return void
	 */
	public function redirect_customizer(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce not required for page redirect.
		if ( ! empty( $_GET['page'] ) && 'colorlib-login-customizer_settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			$url = add_query_arg(
				array(
					'autofocus[panel]' => 'clc_main_panel',
				),
				admin_url( 'customize.php' )
			);

			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Load plugin localisation.
	 *
	 * @return void
	 */
	public function load_localisation(): void {
		load_plugin_textdomain( 'colorlib-login-customizer', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
	}

	/**
	 * Main Colorlib_Login_Customizer Instance
	 *
	 * Ensures only one instance of Colorlib_Login_Customizer is loaded or can be loaded.
	 *
	 * @param string $file    Main plugin file path.
	 * @param string $version Plugin version.
	 * @return self Main Colorlib_Login_Customizer instance.
	 */
	public static function instance( string $file = '', string $version = '1.0.0' ): self {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @return void
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'colorlib-login-customizer' ), esc_html( $this->_version ) );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @return void
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing is forbidden.', 'colorlib-login-customizer' ), esc_html( $this->_version ) );
	}

	/**
	 * Installation. Runs on activation.
	 *
	 * @return void
	 */
	public function install(): void {
		$this->_log_version_number();

		// Backward compatibility.
		$options = get_option( $this->key_name, array() );
		if ( $options ) {
			if ( isset( $options['templates'] ) && '01' === $options['templates'] ) {
				$options['templates'] = 'default';
				$options['columns']   = 2;
			}

			update_option( $this->key_name, $options );
		}
	}

	/**
	 * Log the plugin version number.
	 *
	 * @return void
	 */
	private function _log_version_number(): void {
		update_option( $this->_token . '_version', $this->_version );
	}


	/**
	 * Change template to custom login template when in customizer preview.
	 *
	 * @param string $template Current template path.
	 * @return string Modified template path.
	 */
	public function change_template_if_necessary( string $template ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Customizer preview context.
		if ( is_customize_preview()
			&& isset( $_GET['colorlib-login-customizer-customization'] )
			&& is_user_logged_in()
			&& current_user_can( 'edit_theme_options' )
		) {
			return plugin_dir_path( __FILE__ ) . 'login-template.php';
		}

		return $template;
	}

	/**
	 * Get default options for the plugin.
	 *
	 * @return array<string, mixed> Default options array.
	 */
	public function get_defaults(): array {
		return array(
			/**
			 * Templates
			 */
			'templates'                 => 'default',
			/**
			 * Layout
			 */
			'columns'                  => '1',
			'columns-width'            => array(
				'left'  => 6,
				'right' => 6,
			),
			'form-column-align'        => '3',
			'form-vertical-align'      => '2',
			/**
			 * Logo section
			 */
			'logo-settings'             => 'show-image-only',
			'logo-url'                  => site_url(),
			'custom-logo'               => '',
			'logo-text-color'           => '#444',
			'logo-text-size'            => '20',
			'logo-text-color-hover'     => '#00a0d2',
			'logo-width'                => '',
			'logo-height'               => '',
			/**
			 * Background section
			 */
			'custom-background'             => '',
			'custom-background-link'        => '',
			'custom-background-form'        => '',
			'custom-background-color'       => '',
			'custom-background-color-form'  => '',
			/**
			 * Form section
			 */
			'form-width'                => '',
			'form-height'               => '',
			'form-background-image'     => '',
			'form-background-color'     => '#fff',
			'form-padding'              => '',
			'form-border'               => '',
			'form-border-radius'        => '',
			'form-shadow'               => '',
			'form-field-width'          => '',
			'form-field-margin'         => '',
			'form-field-border-radius'  => 'unset',
			'form-field-border'         => '1px solid #ddd',
			'form-field-background'     => '',
			'form-field-color'          => '',
			'username-label'            => 'Username or Email Address',
			'password-label'            => 'Password',
			'rememberme-label'          => 'Remember Me',
			'lost-password-text'        => 'Lost your password?',
			'back-to-text'              => '&larr; Back to %s',
			'register-link-label'       => 'Register',

			'login-label'               => 'Log In',
			'form-label-color'          => '',
			'hide-extra-links'          => false,
            /**
             * Registration section
             */
            'register-username-label'     => 'Username',
			'register-email-label'        => 'Email',
			'register-button-label'       => 'Register',
			'register-confirmation-email' => 'Registration confirmation will be emailed to you.',
			'login-link-label'            => 'Log in',
			/**
             * Lost Password
             */
			'lostpassword-username-label' => 'Username or Email Address',
			'lostpassword-button-label'   => 'Get New Password',
			/**
			 * Others section ( misc )
			 */
			'button-background'         => '',
			'button-background-hover'   => '',
			'button-border-color'       => '',
			'button-border-color-hover' => '',
			'button-shadow'             => '',
			'button-text-shadow'        => '',
			'button-color'              => '',
			'link-color'                => '',
			'link-color-hover'          => '',
			'hide-rememberme'           => false,
			/**
			 * Custom CSS
			 */
			'custom-css'                => '',
			/**
			 * Reset value is not dynamic
			 */
			'initial'                   => 'initial',
		);
	}

	/**
	 * All In One WP Security customizer fix.
	 *
	 * @return void
	 */
	public function clc_aio_wp_security_comp_fix(): void {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! class_exists( 'AIO_WP_Security' ) ) {
			return;
		}

		global $aio_wp_security;

		if ( ! is_a( $aio_wp_security, 'AIO_WP_Security' ) ) {
			return;
		}

		if ( remove_action( 'wp_loaded', array( $aio_wp_security, 'aiowps_wp_loaded_handler' ) ) ) {
			add_filter( 'option_aio_wp_security_configs', array( $this, 'clc_aio_wp_security_filter_options' ) );
		}
	}

	/**
	 * Filter options aio_wp_security_configs.
	 *
	 * @param array<string, mixed> $option Options array.
	 * @return array<string, mixed> Filtered options.
	 */
	public function clc_aio_wp_security_filter_options( array $option ): array {
		unset( $option['aiowps_enable_rename_login_page'] );
		return $option;
	}
}

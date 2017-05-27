<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

class Macho_Login_Settings {

	/**
	 * The single instance of Macho_Login_Settings.
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

	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'ml_';

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ), array(
			$this,
			'add_settings_link'
		) );
	}


	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		$page = add_menu_page( esc_html__( 'Macho Login', 'macho-login' ), esc_html__( 'Macho Login', 'macho-login' ), 'manage_options', $this->parent->_token . '_settings', array(
			$this,
			'settings_page'
		), 'dashicons-share-alt' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links
	 *
	 * @return array        Modified links
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'macho-login' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
		$html .= '<h2>' . esc_html__( 'Macho Login', 'macho-login' ) . '</h2>' . "\n";
		$html .= '<p>' . esc_html__( 'Login Customizer plugin allows you to easily customize your login page straight from your WordPress Customizer! You can preview your changes before you save them! Awesome, right?', 'macho-login' ) . '</p>';
		$html .= '<a href="' . get_admin_url() . 'customize.php?url=' . wp_login_url() . '" id="submit" class="button button-primary">' . __( 'Start Customizing!', 'macho-login' ) . '</a>';
		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * Main Macho_Login_Settings Instance
	 *
	 * Ensures only one instance of Macho_Login_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see   Macho_Login()
	 * @return Main Macho_Login_Settings instance
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
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->parent->_version );
	} // End __wakeup()

}
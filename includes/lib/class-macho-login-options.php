<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Macho_Login_Options
 */
class Macho_Login_Options {
	/**
	 * @var array
	 */
	private $options = array();
	/**
	 * @var string
	 */
	private $base = '';

	/**
	 * Macho_Login_Options constructor.
	 */
	public function __construct() {
		$plugin     = Macho_Login::instance();
		$this->base = $plugin->base;

		$arr = array(
			'logo_url' => get_theme_mod( $this->base . 'custom_logo_url' ),
		);

		$this->options = array_filter( $arr );

		$this->init_options();
	}

	/**
	 * Init options dynamically
	 */
	private function init_options() {
		foreach ( $this->options as $k => $v ) {
			$method = 'init_' . $k;
			$this->{$method}( $v );
		}
	}

	/**
	 * Initiate the Logo URL
	 *
	 * @param $args
	 */
	private function init_logo_url( $args ) {
		add_filter( 'login_headerurl', array( $this, 'change_logo_url' ) );
	}

	/**
	 * Change the logo url
	 *
	 * @return mixed
	 */
	public function change_logo_url() {
		return $this->options['logo_url'];
	}
}

<?php
/**
 * Background image control for the Customizer.
 *
 * @package Colorlib_Login_Customizer
 */

/**
 * Renders an image control with a gallery of default backgrounds.
 */
class Colorlib_Login_Customizer_Background_Control extends WP_Customize_Image_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.1.0
	 * @access public
	 * @var    string
	 */
	public $type = 'clc-background';

	/**
	 * The list of default background images.
	 *
	 * @var array
	 */
	public $default_backgrounds;

	/**
	 * Colorlib_Login_Customizer_Background_Control constructor.
	 *
	 * @param WP_Customize_Manager $manager Customizer manager instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Control arguments.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$manager->register_control_type( 'Colorlib_Login_Customizer_Background_Control' );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function json() {
		$json = parent::json();

		$json['id']      = $this->id;
		$json['link']    = $this->get_link();
		$json['gallery'] = $this->generate_gallery();

		return $json;
	}

	/**
	 * Build the gallery of default background images.
	 *
	 * @return array
	 */
	private function generate_gallery() {

		if ( ! is_array( $this->default_backgrounds ) ) {
			return array();
		}
	}
}

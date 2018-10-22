<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if WP_Customize_Control does not exsist.
if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return null;
}

/**
 * This class is for the gallery selector in the Customizer.
 *
 * @access  public
 */
class Colorlib_Login_Customizer_Template_Control extends WP_Customize_Control {

	/**
	 * The control type.
	 *
	 * @access public
	 * @var string
	 */
	public $type = 'clc-templates';

	/**
	 * Colorlib_Login_Customizer_Template_Control constructor.
	 *
	 * @param WP_Customize_Manager $manager
	 * @param string               $id
	 * @param array                $args
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		$manager->register_section_type( 'Colorlib_Login_Customizer_Template_Control' );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @access public
	 * @since  1.1.7
	 * @return void
	 */
	public function to_json() {
		parent::to_json();

		$arrays = $this->generate_arrays();

		// The setting value.
		$this->json['id']      = $this->id;
		$this->json['value']   = $this->value();
		$this->json['link']    = $this->get_link();
		$this->json['choices'] = $arrays['choices'];
		$this->json['options'] = $arrays['options'];

	}

	private function generate_name( $id ) {
		return 'clc-options[' . $id . ']';
	}

	public function generate_arrays() {
		$arrays = array(
			'choices' => array(),
			'options' => array(),
		);

		foreach ( $this->choices as $key => $choice ) {
			$arrays['choices'][ $key ] = $choice['url'];
			$arrays['options'][ $key ] = array();
			foreach ( $choice['options'] as $option_key => $option_value ) {
				$name = $this->generate_name( $option_key );
				$arrays['options'][ $key ][ $option_key ] = array(
					'name' => $name,
					'value' => $option_value
				);
			}
		}

		return $arrays;

	}

	/**
	 * Don't render the content via PHP.  This control is handled with a JS template.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function render_content() {}

	/**
	 * An Underscore (JS) template for this control's content.
	 *
	 * Class variables for this control class are available in the `data` JS object;
	 * export custom variables by overriding {@see WP_Customize_Control::to_json()}.
	 *
	 * @see    WP_Customize_Control::print_template()
	 *
	 * @access protected
	 * @since  1.1.7
	 * @return void
	 */
	protected function content_template() {
		?>

		<# if ( ! data.choices ) {
			return;
		} #>

		<# if ( data.description ) { #>
			<span class="customize-control-description">{{ data.description }}</span>
		<# } #>

		<div id="colorlib-login-customizer-templates" class="colorlib-login-customizer-templates">

			<# for ( choice in data.choices ) { #>

				<input type="radio" value="{{ choice }}" name="_customize-{{ data.id }}" id="{{ data.id }}{{ choice }}" class="colorlib-login-customizer-templates__input" />

				<label for="{{ data.id }}{{ choice }}" class="colorlib-login-customizer-templates__label">
					<div class="colorlib-login-customizer-templates__intrinsic">
						<div class="colorlib-login-customizer-templates__screenshot" style="background-image: url( {{ data.choices[ choice ] }} );"></div>
					</div>
				</label>

			<# } #>

		</div>

		<?php
	}
}

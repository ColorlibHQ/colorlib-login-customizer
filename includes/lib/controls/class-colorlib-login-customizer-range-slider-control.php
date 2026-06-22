<?php
/**
 * Custom range slider control for the Customizer.
 *
 * @deprecated 2.0.0 Use native HTML5 range input instead.
 * @package Colorlib_Login_Customizer
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Range slider control class.
 *
 * @deprecated 2.0.0 This class is no longer used internally. Native HTML5 range input is used instead.
 *                   Kept for backwards compatibility with any code that may extend this class.
 *
 * @since  1.0.0
 * @access public
 */
class Colorlib_Login_Customizer_Range_Slider_Control extends WP_Customize_Control {
	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'clc-range-slider';

	/**
	 * Provide a default
	 *
	 * @var string
	 */
	public $default = '';

	/**
	 * Epsilon_Control_Slider constructor.
	 *
	 * @param WP_Customize_Manager $manager Customizer manager instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Control arguments.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		$manager->register_section_type( 'Colorlib_Login_Customizer_Range_Slider_Control' );
		parent::__construct( $manager, $id, $args );
		if ( isset( $args['default'] ) ) {
			$this->default = $args['default'];
		}
	}

	/**
	 * Enqueue scripts/styles.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-slider' );
	}

	/**
	 * Get the control value, falling back to the default.
	 *
	 * @return mixed
	 */
	public function get_value() {
		$value = $this->value();
		if ( ! $value && isset( $this->default ) ) {
			return $this->default;
		}

		return $value;
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @return void
	 */
	public function to_json() {

		$default_choices = array(
			'min'  => 1,
			'max'  => 10,
			'step' => 1,
		);

		$this->choices = wp_parse_args( $this->choices, $default_choices );

		parent::to_json();
		$this->json['value']   = $this->get_value();
		$this->json['id']      = $this->id;
		$this->json['link']    = $this->get_link();
		$this->json['choices'] = $this->choices;
	}

	/**
	 * Displays the control content.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	public function render_content() {
	}

	/**
	 * Display the control's content.
	 *
	 * @return void
	 */
	public function content_template() {
		?>
		<label>
			<span class="customize-control-title">
				<# if ( data.label != '' ){ #>
					{{ data.label }}
				<# } #>
				<# if ( data.description != '' ){ #>
					<i class="dashicons dashicons-editor-help" style="vertical-align: text-bottom; position: relative;">
					<span class="clc-tooltip">{{ data.description }}</span>
				</i>
				<# } #>
			</span>
			<input type="text" class="clc-slider" id="input_{{ data.id }}" value="{{ data.value }}"/>
		</label>
		<div id="slider_{{ data.id }}" class="clc-slider"></div>
		<?php
	}
}


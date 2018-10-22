<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Slider control
 *
 * @since  1.0.0
 * @access public
 *
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
	 * @param WP_Customize_Manager $manager
	 * @param string               $id
	 * @param array                $args
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

	public function get_value() {
		$value = $this->value();
		if ( ! $value && isset( $this->default ) ) {
			return $this->default;
		}

		return $value;
	}

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


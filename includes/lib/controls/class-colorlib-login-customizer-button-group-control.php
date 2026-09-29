<?php
/**
 * Button group control for the Customizer.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Renders a group of buttons as a single radio-style control.
 */
class Colorlib_Login_Customizer_Button_Group_Control extends WP_Customize_Control {
	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.1.0
	 * @access public
	 * @var    string
	 */
	public $type = 'clc-button-group';

	/**
	 * The default value for the control.
	 *
	 * @var string
	 */
	public $default = '';

	/**
	 * The list of button choices.
	 *
	 * @var array
	 */
	public $choices = array();

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function json() {
		$json              = parent::json();
		$json['id']        = $this->id;
		$json['link']      = $this->get_link();
		$json['value']     = $this->value();
		$json['default']   = $this->default;
		$json['choices']   = $this->choices;
		$json['groupType'] = $this->set_group_type();
		$json['labels']    = $this->choice_labels();

		return $json;
	}

	/**
	 * Accessible names for the image-only buttons, keyed like the choices.
	 *
	 * @return array<string|int, string>
	 */
	public function choice_labels() {
		$names = array(
			'left'   => __( 'Left', 'colorlib-login-customizer' ),
			'right'  => __( 'Right', 'colorlib-login-customizer' ),
			'top'    => __( 'Top', 'colorlib-login-customizer' ),
			'bottom' => __( 'Bottom', 'colorlib-login-customizer' ),
			'middle' => __( 'Middle', 'colorlib-login-customizer' ),
		);

		$labels = array();

		foreach ( array_keys( $this->choices ) as $key ) {
			if ( isset( $names[ $key ] ) ) {
				$labels[ $key ] = $names[ $key ];
			} else {
				/* translators: %d: number of columns. */
				$labels[ $key ] = sprintf( _n( '%d column', '%d columns', (int) $key, 'colorlib-login-customizer' ), (int) $key );
			}
		}

		return $labels;
	}

	/**
	 * Set group type
	 */
	public function set_group_type() {
		$arr = array(
			0 => 'none',
			1 => 'one',
			2 => 'two',
			3 => 'three',
			4 => 'four',
		);

		return $arr[ count( $this->choices ) ] ?? 'four';
	}

	/**
	 * Don't render the content via PHP: the JS template below replaces it.
	 *
	 * Without this override core's default markup was built (and discarded)
	 * for every instance, and for the column widths it called esc_attr() on
	 * the array value, logging "Array to string conversion".
	 *
	 * @return void
	 */
	public function render_content() {}

	/**
	 * Display the control's content
	 */
	public function content_template() {
		// @formatter:off ?>
		<div class="colorlib-login-customizer-control-container">
			<label>
				<span class="customize-control-title">
					{{{ data.label }}}
					<# if( data.description ){ #>
						<i class="dashicons dashicons-editor-help" style="vertical-align: text-bottom; position: relative;">
							<span class="mte-tooltip">
								{{{ data.description }}}
							</span>
						</i>
					<# } #>
				</span>
			</label>
			<div class="colorlib-login-customizer-control-set">
				<div class="colorlib-login-customizer-control-group colorlib-login-customizer-group-{{ data.groupType }}">
					<# for( var i in data.choices ) { #>
						<a href="#" role="button" data-value="{{ data.choices[i].value }}" aria-label="{{ data.labels[i] }}" <# if( data.value == data.choices[i].value ) { #> class="active" aria-pressed="true" <# } else { #> aria-pressed="false" <# } #> >
							<# if( ! _.isUndefined( data.choices[i].icon ) ) { #>
								<i class="dashicons {{ data.choices[i].icon }}" aria-hidden="true"></i>
							<# } #>

							<# if( ! _.isUndefined( data.choices[i].png ) ) { #>
								<img src="{{ data.choices[i].png }}" alt="" />
							<# } #>
						</a>
					<# } #>
				</div>
			</div>
		</div>
		<?php
		// @formatter: on
	}
}

<?php
/**
 * Custom color picker control for the Customizer.
 *
 * @deprecated 2.0.0 Use WP_Customize_Color_Control instead.
 * @package Colorlib_Login_Customizer
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Color picker control class.
 *
 * @deprecated 2.0.0 This class is no longer used internally. WP_Customize_Color_Control is used instead.
 *                   Kept for backwards compatibility with any code that may extend this class.
 */
class Colorlib_Login_Customizer_Control_Color_Picker extends WP_Customize_Control {
	/**
	 * The type of customize control being rendered.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $type = 'clc-color-picker';
	/**
	 * The default value for the control.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $default = '';
	/**
	 * The color picker mode.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $mode = '';
	/**
	 * Whether to render the lite variant of the control.
	 *
	 * @since 1.3.4
	 * @var bool
	 */
	public $lite = false;

	/**
	 * Colorlib_Login_Customizer_Control_Color_Picker constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manager $manager Customizer manager instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Control arguments.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$manager->register_control_type( 'Colorlib_Login_Customizer_Control_Color_Picker' );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.2.0
	 * @access public
	 */
	public function json() {
		$json = parent::json();

		$json['id']      = $this->id;
		$json['link']    = $this->get_link();
		$json['value']   = $this->value();
		$json['default'] = $this->setting->default;
		$json['mode']    = '' !== $this->mode ? $this->mode : 'hex';
		$json['lite']    = $this->lite;

		return $json;
	}

	/**
	 * Display the control's content
	 */
	public function content_template() {
		// @formatter:off ?>
		<label <# if( data.lite ) { #>class="lite"<# } #>>
			<input class="clc-color-picker" type="text" <# if( data.default ){ #>placeholder="{{ data.default }}"<# } #> <# if(data.value){ #> value="{{ data.value }}" <# } #> />
			<span class="customize-control-title clc-color-picker-title">
				{{{ data.label }}}
				<# if( data.default ){ #>
				<a href="#" data-default="{{ data.default }}" class="clc-color-picker-default"><?php echo esc_html__( '(clear)', 'colorlib-login-customizer' ); ?></a>
				<# } #>

				<# if( data.description ){ #>
					<span class="clc-color-picker-description">{{{ data.description }}}</span>
				<# } #>
			</span>
		</label>
		<?php
		// @formatter:on
	}

	/**
	 * Empty, as it should be
	 *
	 * @since 1.0.0
	 */
	public function render_content() {
	}
}

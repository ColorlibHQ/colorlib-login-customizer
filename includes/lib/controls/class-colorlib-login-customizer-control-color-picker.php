<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Colorlib_Login_Customizer_Control_Color_Picker
 */
class Colorlib_Login_Customizer_Control_Color_Picker extends WP_Customize_Control {
	/**
	 * @since 1.0.0
	 * @var string
	 */
	public $type = 'clc-color-picker';
	/**
	 * @since 1.0.0
	 * @var string
	 */
	public $default = '';
	/**
	 * @since 1.0.0
	 * @var string
	 */
	public $mode = '';
	/**
	 * @since 1.3.4
	 * @var bool
	 */
	public $lite = false;

	/**
	 * Colorlib_Login_Customizer_Control_Color_Picker constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Customize_Manager $manager
	 * @param string               $id
	 * @param array                $args
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
		//@formatter:off ?>
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
	<?php //@formatter:on
	}

	/**
	 * Empty, as it should be
	 *
	 * @since 1.0.0
	 */
	public function render_content() {
	}
}

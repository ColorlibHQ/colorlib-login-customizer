<?php
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Colorlib_Login_Customizer_Column_Width
 */
class Colorlib_Login_Customizer_Column_Width extends WP_Customize_Control {
	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.1.0
	 * @access public
	 * @var    string
	 */
	public $type = 'clc-column-width';

	/**
	 * Colorlib_Login_Customizer_Column_Width constructor.
	 *
	 * @since 1.1.0
	 *
	 * @param WP_Customize_Manager $manager
	 * @param string               $id
	 * @param array                $args
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		parent::__construct( $manager, $id, $args );
		$manager->register_control_type( 'Colorlib_Login_Customizer_Column_Width' );
	}

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
		$json['value']     = $this->get_columns();

		return $json;
	}

	/**
	 * Set value
	 */
	public function get_columns() {
		$default = array(
			'left'  => 6,
			'right' => 6
		);
		$current_columns = $this->value();
		$current_columns = is_array( $current_columns ) ? $current_columns : array();

		return wp_parse_args( $current_columns, $default );
	}

	/**
	 * Display the control's content
	 */
	public function content_template() {
		//@formatter:off ?>
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
			<div class="clc-layouts-container-advanced">
				<div class="clc-layouts-setup">
					<div class="clc-column clc-column-left col{{data.value.left}}">
						<a href="#" data-action="left"><span class="dashicons dashicons-arrow-right"></span></a>
					</div>
					<div class="clc-column clc-column-right col{{data.value.right}}">
						<a href="#" data-action="right"><span class="dashicons dashicons-arrow-left"></span></a>
					</div>
				</div>
			</div>
		</div>
	<?php //@formatter: on
	}
}

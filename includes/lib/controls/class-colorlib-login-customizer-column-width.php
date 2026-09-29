<?php
/**
 * Column width control for the Customizer.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Renders a control for setting left and right column widths.
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
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.1.0
	 * @access public
	 */
	public function json() {
		$json          = parent::json();
		$json['id']    = $this->id;
		$json['link']  = $this->get_link();
		$json['value'] = $this->get_columns();

		return $json;
	}

	/**
	 * Set value
	 */
	public function get_columns() {
		$default         = array(
			'left'  => 6,
			'right' => 6,
		);
		$current_columns = $this->value();
		$current_columns = is_array( $current_columns ) ? $current_columns : array();

		return wp_parse_args( $current_columns, $default );
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
			<div class="clc-layouts-container-advanced">
				<div class="clc-layouts-setup">
					<div class="clc-column clc-column-left col{{data.value.left}}">
						<a href="#" role="button" data-action="left" aria-label="<?php esc_attr_e( 'Widen the left column', 'colorlib-login-customizer' ); ?>"><span class="dashicons dashicons-arrow-right" aria-hidden="true"></span></a>
					</div>
					<div class="clc-column clc-column-right col{{data.value.right}}">
						<a href="#" role="button" data-action="right" aria-label="<?php esc_attr_e( 'Widen the right column', 'colorlib-login-customizer' ); ?>"><span class="dashicons dashicons-arrow-left" aria-hidden="true"></span></a>
					</div>
				</div>
			</div>
		</div>
		<?php
		// @formatter: on
	}
}

<?php

/**
 * Class Epsilon_Section_Recommended_Actions
 */
class Epsilon_Section_Predefined_Schemes extends WP_Customize_Section {
	/**
	 * The type of customize section being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'epsilon-section-predefined-schemes';

	/**
	 * @var array
	 */
	public $color_schemes = array();

	/**
	 * Epsilon_Section_Predefined_Schemes constructor.
	 *
	 * @param WP_Customize_Manager $manager
	 * @param string               $id
	 * @param array                $args
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		$manager->register_section_type( 'Epsilon_Section_Predefined_Schemes' );
		parent::__construct( $manager, $id, $args );
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function json() {
		$json = parent::json();

		$color_schemes = array();
		foreach ( $this->color_schemes as $k => $v ) {
			$color_schemes[] = array(
				'id'      => $k,
				'thumb'   => $v['thumb'],
				'options' => json_encode( $v['options'] )
			);
		}

		$json['color_schemes'] = $color_schemes;
		$json['type']          = $this->type;

		return $json;
	}

	/**
	 * Outputs the Underscore.js template.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	protected function render_template() {
		//@formatter:off
		?>
		<li id="accordion-section-{{ data.id }}" class="accordion-section control-section control-section-{{ data.type }} cannot-expand">
			<h3 class="accordion-section-title">
				{{{data.title}}}
			</h3>
			<div class="predefined-color-schemes-container" id="macho-login-color-schemes">
				<span>
					<# if ( data.description ) { #>
						<div class="description">
							{{{ data.description }}}
						</div>
					<# } #>
				</span>
				<ul class="ml-color-schemes-list">
				<# for (color_scheme in data.color_schemes) { #>
					<li data-scheme-id="{{data.color_schemes[color_scheme].id}}">
						<a href="#" data-scheme-json="{{data.color_schemes[color_scheme].options}}">
							<img src="{{data.color_schemes[color_scheme].thumb}}" />
						</a>
					</li>
				<# } #>
				</ul>
			</div>
		</li>
		<?php
		//@formatter:om
	}

	protected function render_content() {

	}
}

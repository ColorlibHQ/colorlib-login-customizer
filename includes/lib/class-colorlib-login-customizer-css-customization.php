<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Colorlib_Login_Customizer_Customization
 */
class Colorlib_Login_Customizer_CSS_Customization {
	/**
	 * @var array
	 */
	private $options = array();
	/**
	 * @var array
	 */
	private $selectors = array();
	/**
	 * @var string
	 */
	private $base = '';

	/**
	 * Colorlib_Login_Customizer_CSS_Customization constructor.
	 */
	public function __construct() {
		$plugin         = Colorlib_Login_Customizer::instance();
		$this->key_name = $plugin->key_name;
		$this->set_options();
		add_action( 'login_head', array( $this, 'generate_css' ), 15 );
		add_action( 'login_header', array( $this, 'add_extra_div' ) );
		add_action( 'login_footer', array( $this, 'close_extra_div' ) );

		add_filter( 'login_body_class', array( $this, 'body_class' ) );
		add_filter( 'login_headerurl', array( $this, 'logo_url' ), 99 );

		// 
		add_action( 'customize_preview_init', array( $this, 'output_css_object' ), 26 );
	}

	private function generate_name( $id ) {
		return $this->key_name . '[' . $id . ']';
	}

	public function output_css_object() {

		$css_object = array(
			'selectors' => array(),
			'settings' => array(),
		);

		foreach ( $this->selectors as $selector => $settings ) {
			$css_object['selectors'][ $selector ] = $settings['options'];
			foreach ( $settings['options'] as $index => $setting ) {
				$css_object['settings'][ $setting ] = array(
					'name' => $this->generate_name( $setting ),
					'value' => $this->options[ $setting ],
					'attribute' => $settings['attributes'][ $index ],
				);
			}
		}

		wp_localize_script( 'colorlib-login-customizer-preview', 'CLC', $css_object );

	}

	/**
	 * Set the options array, it returns nothing
	 */
	public function set_options() {

		$options = get_option( $this->key_name, array() );

		$defaults = array(
			/**
			 * Templates
			 */
			'templates'                 => 'default',
			/**
			 * Logo section
			 */
			'custom-logo-url'           => '',
			'custom-logo'               => '',
			'logo-width'                => '',
			'logo-height'               => '',
			/**
			 * Background section
			 */
			'custom-background'         => '',
			'custom-background-color'   => '',
			/**
			 * Form section
			 */
			'form-width'                => '',
			'form-height'               => '',
			'form-background-image'     => '',
			'form-background-color'     => '',
			'form-padding'              => '',
			'form-border'               => '',
			'form-field-width'          => '',
			'form-field-margin'         => '',
			'form-field-background'     => '',
			'form-field-color'          => '',
			'form-label-color'          => '',
			/**
			 * Others section ( misc )
			 */
			'button-background'         => '',
			'button-background-hover'   => '',
			'button-border-color'       => '',
			'button-border-color-hover' => '',
			'button-shadow'             => '',
			'button-color'              => '',
			'link-color'                => '',
			'link-color-hover'          => '',
			/**
			 * Reset value is not dynamic
			 */
			'initial'                   => 'initial',
		);

		$this->options = wp_parse_args( $options, $defaults );

		$this->selectors = array(
			'.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary.hover, .wp-core-ui .button-primary:focus, .wp-core-ui .button-primary:hover' => array(
				'attributes' => array(
					'background',
					'border-color',
				),
				'options' => array(
					'button-background-hover',
					'button-border-color-hover',
				),
			),
			'.wp-core-ui .button-primary' => array(
				'attributes' => array(
					'background',
					'border-color',
					'box-shadow',
					'color',
				),
				'options' => array(
					'button-background',
					'button-border-color',
					'button-shadow',
					'button-color',
				),
			),
			'.login #backtoblog a, .login #nav a' => array(
				'attributes' => array(
					'color',
				),
				'options' => array(
					'link-color',
				),
			),
			'.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover' => array(
				'attributes' => array(
					'color',
				),
				'options' => array(
					'link-color-hover',
				),
			),
			'.ml-container #login' => array(
				'attributes' => array(
					'max-width',
				),
				'options' => array(
					'form-width',
				),
			),
			'#loginform' => array(
				'attributes' => array(
					'height',
					'background-image',
					'background-color',
					'padding',
					'border',
				),
				'options' => array(
					'form-height',
					'form-background-image',
					'form-background-color',
					'form-padding',
					'form-border',
				),
			),
			'.login form .input, .login input[type="text"]' => array(
				'attributes' => array(
					'max-width',
					'margin',
					'background',
					'color',
				),
				'options' => array(
					'form-field-width',
					'form-field-margin',
					'form-field-background',
					'form-field-color',
				),
			),
			'.login label' => array(
				'attributes' => array(
					'color',
				),
				'options' => array(
					'form-label-color',
				),
			),
			'.ml-container .ml-extra-div' => array(
				'attributes' => array(
					'background-image',
					'background-color',
				),
				'options' => array(
					'custom-background',
					'custom-background-color',
				),
			),
			'.login h1 a' => array(
				'attributes' => array(
					'background-image',
					'background-size',
					'width',
					'height',
				),
				'options' => array(
					'custom-logo',
					'logo-width',
					'logo-width',
					'logo-height',
				),
			),
		);

	}

	/**
	 * Create the CSS string for output
	 *
	 * @return mixed|string
	 */
	public function create_css() {
		/**
		 * Get an instance of the plugin so we can get the token
		 */
		//$instance = Colorlib_Login_Customizer::instance();

		$string = '';
		/**
		 * In case the array is empty, we return an empty string
		 */
		if ( empty( $this->options ) ) {
			return $string;
		}

		/**
		 * Start building the CSS file
		 */
		$string .= $this->_set_background_options();
		$string .= $this->_set_logo_options();
		$string .= $this->_set_form_options();
		$string .= $this->_set_miscellaneous_options();

		return $string;
	}

	/**
	 * @return string
	 */
	public function _set_miscellaneous_options() {
		$string = '';

		$string .= $this->create_css_lines(
			'.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary.hover, .wp-core-ui .button-primary:focus, .wp-core-ui .button-primary:hover',
			array(
				'background',
				'border-color',
			),
			array(
				'button-background-hover',
				'button-border-color-hover',
			)
		);

		$string .= $this->create_css_lines(
			'.wp-core-ui .button-primary',
			array(
				'background',
				'border-color',
				'box-shadow',
				'color',
			),
			array(
				'button-background',
				'button-border-color',
				'button-shadow',
				'button-color',
			)
		);

		$string .= $this->create_css_lines(
			'.login #backtoblog a, .login #nav a',
			array(
				'color',
			),
			array(
				'link-color',
			)
		);

		$string .= $this->create_css_lines(
			'.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover',
			array(
				'color',
			),
			array(
				'link-color-hover',
			)
		);

		return $string;
	}

	/**
	 * @return string
	 */
	public function _set_form_options() {
		$string = '';

		$string .= $this->create_css_lines(
			'.ml-container #login',
			array(
				'max-width',
			),
			array(
				'form-width',
			)
		);

		/**
		 * Set form variables
		 */
		$string .= $this->create_css_lines(
			'#loginform',
			array(
				'height',
				'background-image',
				'background-color',
				'padding',
				'border',
			),
			array(
				'form-height',
				'form-background-image',
				'form-background-color',
				'form-padding',
				'form-border',
			)
		);

		/**
		 * Set form field variables
		 */
		$string .= $this->create_css_lines(
			'.login form .input, .login input[type="text"]',
			array(
				'max-width',
				'margin',
				'background',
				'color',
			),
			array(
				'form-field-width',
				'form-field-margin',
				'form-field-background',
				'form-field-color',
			)
		);

		/**
		 * Set form field labels
		 */
		$string .= $this->create_css_lines(
			'.login label',
			array( 'color' ),
			array( 'form-label-color' )
		);

		return $string;
	}

	/**
	 * @return string
	 */
	public function _set_background_options() {
		$string = '';
		/**
		 * Set background-image
		 */
		$string .= $this->create_css_lines(
			'.ml-container .ml-extra-div',
			array(
				'background-image',
				'background-color',
			),
			array(
				'custom-background',
				'custom-background-color',
			)
		);

		return $string;

	}

	/**
	 * @return string
	 */
	public function _set_logo_options() {
		$string = '';
		/**
		 * Set logo dimensions
		 */
		$string .= $this->create_css_lines(
			'.login h1 a',
			array(
				'background-image',
				'background-size',
				'width',
				'height',
			),
			array(
				'custom-logo',
				'logo-width',
				'logo-width',
				'logo-height',
			)
		);

		return $string;
	}

	/**
	 * @param $selector
	 * @param $properties
	 * @param $options
	 *
	 * @return string
	 */
	private function create_css_lines( $selector, $properties, $options ) {
		$string = '';
		$valued = array();

		$i = 0;
		foreach ( $options as $option ) {
			if ( ! empty( $this->options[ $option ] ) ) {
				$valued[ $properties[ $i ] ] = $this->options[ $option ];
			}
			$i ++;
		}

		if ( ! empty( $valued ) ) {
			$string .= $selector . '{' . "\n";

			foreach ( $valued as $index => $value ) {
				$string .= $index . ':' . esc_attr( $this->add_artifacts( $index, $value ) ) . ';' . "\n";
			}
			$string .= '}' . "\n";
		}

		return $string;
	}

	/**
	 *
	 *
	 * @param $property
	 * @param $value
	 *
	 * @return string
	 */
	private function add_artifacts( $property, $value ) {
		switch ( $property ) {
			case 'background-image':
				$value = 'url(' . $value . ')';
				break;

			case 'width':
			case 'min-width':
			case 'max-width':
			case 'background-size':
			case 'height':
			case 'min-height':
			case 'max-height':
				$value = $value . 'px';
				break;

			default:
				break;
		}

		return $value;
	}

	public function body_class( $classes ) {
		if ( 'default' != $this->options['templates'] ) {
			$classes[] = 'ml-half-screen';
		}

		return $classes;
	}

	public function logo_url( $url ) {
		if ( '' != $this->options['custom-logo-url'] ) {
			return esc_url( $this->options['custom-logo-url'] );
		}

		return $url;
	}

	/**
	 * Output the inline CSS
	 */
	public function generate_css() {
		$instance = Colorlib_Login_Customizer::instance();
		$css      = $this->create_css();

		echo '<style type="text/css">.login h1 a{background-position: center;}.ml-container{position:relative;width100%;min-height:100vh;display:flex;}.ml-container .ml-extra-div{background-position: center;background-size: cover;background-repeat: no-repeat;}body:not( .ml-half-screen ) .ml-container .ml-extra-div{position:absolute;top:0;left:0;width:100%;height:100%;}body:not( .ml-half-screen ) .ml-container .ml-form-container{width:100%;min-height:100vh;display:flex;align-items:center;}.ml-container #login{ position:relative;padding: 0;width:100%;max-width:320px;}body.ml-half-screen .ml-container{ flex-wrap: wrap; }body.ml-half-screen .ml-container > .ml-extra-div,body.ml-half-screen .ml-container > .ml-form-container{ width: 50%; }body.ml-half-screen .ml-form-container{display:flex;}#loginform{box-sizing: border-box;max-height: 100%;background-position: center;background-repeat: no-repeat;background-size: cover;}@media only screen and (max-width: 768px) {body.ml-half-screen .ml-container > .ml-extra-div, body.ml-half-screen .ml-container > .ml-form-container{width:100%;}body .ml-container .ml-extra-div{position:absolute;top:0;left:0;width:100%;height:100%;}}</style>';
		echo '<style type="text/css" id="clc-style">' . $css . '</style>';
	}

	public function add_extra_div() {
		echo '<div class="ml-container"><div class="ml-extra-div"></div><div class="ml-form-container">';
	}

	public function close_extra_div() {
		echo '</div></div>';
	}

}

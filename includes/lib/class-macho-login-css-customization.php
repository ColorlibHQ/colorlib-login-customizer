<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Macho_Login_Customization
 */
class Macho_Login_CSS_Customization {
	/**
	 * @var array
	 */
	private $options = array();
	/**
	 * @var string
	 */
	private $base = '';

	/**
	 * Macho_Login_CSS_Customization constructor.
	 */
	public function __construct() {
		$plugin     = Macho_Login::instance();
		$this->base = $plugin->base;
		$this->set_options();
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue' ), 15 );
	}

	/**
	 * Set the options array, it returns nothing
	 */
	public function set_options() {
		/**
		 * @todo adding themes
		 *
		 *       IDEA :
		 *       Themes will be stored as JSON, and decoded here
		 *
		 *       Theme overrides will be possible by merging the two arrays,
		 *       basically overwriting the theme array with what option is modified by the user
		 *
		 *       Probably defaults should be removed, having a default in the customizer, will save the option in the   database
		 *
		 *       Switched to get_option, while get_theme_mod is easier and does not flood the database with options,
		 *       changes are "lost" if you change theme, the changes should persist on all themes
		 *
		 */
		$arr = array(
			/**
			 * Logo section
			 */
			'custom-logo'               => get_option( $this->base . 'custom_logo' ),
			'logo-width'                => get_option( $this->base . 'logo_width' ),
			'logo-height'               => get_option( $this->base . 'logo_height' ),
			/**
			 * Background section
			 */
			'background-image'          => get_option( $this->base . 'custom_background' ),
			'background-color'          => get_option( $this->base . 'custom_background_color' ),
			/**
			 * Form section
			 */
			'form-width'                => get_option( $this->base . 'form_width' ),
			'form-height'               => get_option( $this->base . 'form_height' ),
			'form-background-image'     => get_option( $this->base . 'form_background_image' ),
			'form-background-color'     => get_option( $this->base . 'form_background_color' ),
			'form-padding'              => get_option( $this->base . 'form_padding' ),
			'form-border'               => get_option( $this->base . 'form_border' ),
			'form-field-width'          => get_option( $this->base . 'form_field_width' ),
			'form-field-margin'         => get_option( $this->base . 'form_field_margin' ),
			'form-field-background'     => get_option( $this->base . 'form_field_background' ),
			'form-field-color'          => get_option( $this->base . 'form_field_color' ),
			'form-label-color'          => get_option( $this->base . 'form_label_color' ),
			/**
			 * Others section ( misc )
			 */
			'button-background'         => get_option( $this->base . 'button_background' ),
			'button-background-hover'   => get_option( $this->base . 'button_background_hover' ),
			'button-border-color'       => get_option( $this->base . 'button_border_color' ),
			'button-border-color-hover' => get_option( $this->base . 'button_border_color_hover' ),
			'button-shadow'             => get_option( $this->base . 'button_shadow' ),
			'button-color'              => get_option( $this->base . 'button_color' ),
			'link-color'                => get_option( $this->base . 'link_color' ),
			'link-color-hover'          => get_option( $this->base . 'link_color_hover' ),
			/**
			 * Reset value is not dynamic
			 */
			'initial'                   => 'initial',
		);

		$this->options = array_filter( $arr );
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
		//$instance = Macho_Login::instance();

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
				'color'
			),
			array(
				'button-background',
				'button-border-color',
				'button-shadow',
				'button-color'
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

		/**
		 * Set form variables
		 */
		$string .= $this->create_css_lines(
			'#loginform',
			array(
				'width',
				'height',
				'background-image',
				'background-color',
				'padding',
				'border',
			),
			array(
				'form-width',
				'form-height',
				'form-background-image',
				'form-background-color',
				'form-padding',
				'form-border'
			) );

		/**
		 * Set form field variables
		 */
		$string .= $this->create_css_lines(
			'.login form .input, .login input[type="text"]',
			array(
				'width',
				'margin',
				'background',
				'color'
			),
			array(
				'form-field-width',
				'form-field-margin',
				'form-field-background',
				'form-field-color'
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
			'body',
			array(
				'background-image',
				'background-color',
			),
			array(
				'background-image',
				'background-color',
			) );

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
				'height'
			),
			array(
				'custom-logo',
				'logo-width',
				'logo-width',
				'logo-height',
			) );

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
			case 'background-size':
			case 'height':
				$value = $value . 'px';
				break;

			default:
				break;
		}

		return $value;
	}

	/**
	 * Enqueue the inline CSS string
	 */
	public function enqueue() {
		$instance = Macho_Login::instance();
		$css      = $this->create_css();

		wp_add_inline_style( $instance->_token . '-login', $css );
	}
}

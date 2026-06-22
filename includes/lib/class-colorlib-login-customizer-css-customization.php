<?php
/**
 * Dynamic CSS generation and login text customization.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Colorlib_Login_Customizer_CSS_Customization
 *
 * Handles CSS generation and text customization for the login page.
 */
class Colorlib_Login_Customizer_CSS_Customization {

	/**
	 * Plugin options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = array();

	/**
	 * CSS selectors mapping.
	 *
	 * @var array<string, array<string, array<int, string>>>
	 */
	private array $selectors = array();

	/**
	 * CLC WP options name.
	 *
	 * @var string
	 */
	public string $key_name;

	/**
	 * Default options.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$plugin         = Colorlib_Login_Customizer::instance();
		$this->key_name = $plugin->key_name;
		$this->defaults = $plugin->get_defaults();
		$this->set_options();

		add_action( 'login_init', array( $this, 'check_general_texts' ) );

		add_action( 'login_form_login', array( $this, 'check_login_texts' ) );
		add_action( 'login_form_register', array( $this, 'check_register_texts' ) );
		add_action( 'login_form_lostpassword', array( $this, 'check_lostpasswords_texts' ) );

		add_action( 'login_header', array( $this, 'add_extra_div' ) );
		add_action( 'login_head', array( $this, 'generate_css' ), 15 );
		add_action( 'login_footer', array( $this, 'close_extra_div' ) );

		// Footer & Links: custom text above the form and a custom footer below it.
		add_action( 'login_header', array( $this, 'render_above_form' ), 11 );
		add_action( 'login_footer', array( $this, 'render_footer' ), 5 );

		// Footer & Links: optionally hide the core privacy-policy link and language switcher.
		if ( ! empty( $this->options['hide-privacy-link'] ) ) {
			add_filter( 'the_privacy_policy_link', '__return_empty_string' );
		}

		if ( ! empty( $this->options['hide-language-switcher'] ) ) {
			add_filter( 'login_display_language_dropdown', '__return_false' );
		}
		add_filter( 'login_body_class', array( $this, 'body_class' ) );
		add_filter( 'login_headerurl', array( $this, 'logo_url' ), 99 );
		add_filter( 'login_headertext', array( $this, 'logo_title' ), 99 );
		add_filter( 'login_title', array( $this, 'login_page_title' ), 99 );

		add_action( 'customize_preview_init', array( $this, 'output_css_object' ), 26 );
	}

	/**
	 * Build a settings field name namespaced to the plugin option key.
	 *
	 * @param string $id Field identifier.
	 * @return string Namespaced field name.
	 */
	private function generate_name( $id ) {
		return $this->key_name . '[' . $id . ']';
	}

	/**
	 * Send the generated CSS object to the Customizer preview via postMessage.
	 *
	 * @return void
	 */
	public function output_css_object() {

		$css_object = array(
			'selectors' => array(),
			'settings'  => array(),
		);

		foreach ( $this->selectors as $selector => $settings ) {
			if ( isset( $settings['options'] ) ) {
				$css_object['selectors'][ $selector ] = $settings['options'];
				foreach ( $settings['options'] as $index => $setting ) {
					$css_object['settings'][ $setting ] = array(
						'name'      => $this->generate_name( $setting ),
						'value'     => $this->options[ $setting ],
						'attribute' => $settings['attributes'][ $index ],
					);
				}
			}
		}

		wp_localize_script( 'colorlib-login-customizer-preview', 'CLC', $css_object );
	}

	/**
	 * Set the options array, it returns nothing
	 */
	public function set_options() {

		$options       = get_option( $this->key_name, array() );
		$this->options = wp_parse_args( $options, $this->defaults );

		$this->selectors = array(
			'.wp-core-ui .button-primary.focus, .wp-core-ui .button-primary.hover, .wp-core-ui .button-primary:focus, .wp-core-ui .button-primary:hover' => array(
				'attributes' => array(
					'background',
					'border-color',
				),
				'options'    => array(
					'button-background-hover',
					'button-border-color-hover',
				),
			),
			'.wp-core-ui .button-primary'                => array(
				'attributes' => array(
					'background',
					'border-color',
					'box-shadow',
					'text-shadow',
					'color',
					'width',
				),
				'options'    => array(
					'button-background',
					'button-border-color',
					'button-shadow',
					'button-text-shadow',
					'button-color',
					'button-width',
				),
			),
			'.login #backtoblog a, .login #nav a'        => array(
				'attributes' => array(
					'color',
				),
				'options'    => array(
					'link-color',
				),
			),
			'.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover,.login.clc-both-logo #backtoblog a:hover, .login.clc-both-logo #nav a:hover, .login.clc-both-logo h1 a:hover' => array(
				'attributes' => array(
					'color',
				),
				'options'    => array(
					'link-color-hover',
				),
			),
			'.ml-container #login'                       => array(
				'attributes' => array(
					'max-width',
				),
				'options'    => array(
					'form-width',
				),
			),
			'#loginform,#registerform,#lostpasswordform' => array(
				'attributes' => array(
					'min-height',
					'background-image',
					'background-color',
					'padding',
					'border',
					'border-radius',
					'box-shadow',
				),
				'options'    => array(
					'form-height',
					'form-background-image',
					'form-background-color',
					'form-padding',
					'form-border',
					'form-border-radius',
					'form-shadow',
				),
			),
			'.login form .input, .login input[type="text"]' => array(
				'attributes' => array(
					'max-width',
					'margin',
					'border-radius',
					'border',
					'background',
					'color',
				),
				'options'    => array(
					'form-field-width',
					'form-field-margin',
					'form-field-border-radius',
					'form-field-border',
					'form-field-background',
					'form-field-color',
				),
			),
			'.login label'                               => array(
				'attributes' => array(
					'color',
				),
				'options'    => array(
					'form-label-color',
				),
			),
			'.ml-container .ml-extra-div'                => array(
				'attributes' => array(
					'background-image',
					'background-color',
				),
				'options'    => array(
					'custom-background',
					'custom-background-color',
				),
			),
			'.ml-half-screen div.ml-form-container'      => array(
				'attributes' => array(
					'background-color',
				),
				'options'    => array(
					'custom-background-color',
				),
			),
			'.ml-container .ml-form-container'           => array(
				'attributes' => array(
					'background-image',
					'background-color',
				),
				'options'    => array(
					'custom-background-form',
					'custom-background-color-form',
				),
			),
			'.login h1 a'                                => array(
				'attributes' => array(
					'background-image',
					'width',
					'height',
				),
				'options'    => array(
					'custom-logo',
					'logo-width',
					'logo-height',
				),
			),
			'.login.clc-text-logo h1 a,.login.clc-both-logo h1 a' => array(
				'attributes' => array(
					'color',
					'font-size',
				),
				'options'    => array(
					'logo-text-color',
					'logo-text-size',
				),
			),
			'.login.clc-text-logo h1 a:hover,.login.clc-both-logo h1 a:hover' => array(
				'attributes' => array(
					'color',
				),
				'options'    => array(
					'logo-text-color-hover',
				),
			),
			'#login > h1'                                => array(
				'attributes' => array(
					'display',
				),
				'options'    => array(
					'logo-settings',
				),
			),
			'#login > #nav,#login > #backtoblog'         => array(
				'attributes' => array(
					'display',
				),
				'options'    => array(
					'hide-extra-links',
				),
			),
			'#login form .forgetmenot'                   => array(
				'attributes' => array(
					'display',
				),
				'options'    => array(
					'hide-rememberme',
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
		$string = '';
		// In case the array is empty, we return an empty string.
		if ( empty( $this->options ) ) {
			return $string;
		}

		/**
		 * Start building the CSS file
		 */
		$string .= $this->_set_background_options();
		$string .= $this->_set_background_filter();
		$string .= $this->_set_logo_options();
		$string .= $this->_set_form_options();
		$string .= $this->_set_miscellaneous_options();

		return $string;
	}

	/**
	 * Build the CSS filter (blur + brightness) applied to the background image.
	 *
	 * @return string
	 */
	public function _set_background_filter() {
		$blur       = isset( $this->options['background-blur'] ) ? absint( $this->options['background-blur'] ) : 0;
		$brightness = isset( $this->options['background-brightness'] ) ? absint( $this->options['background-brightness'] ) : 100;

		$filters = array();

		if ( $blur > 0 ) {
			$filters[] = 'blur(' . $blur . 'px)';
		}

		if ( 100 !== $brightness ) {
			$filters[] = 'brightness(' . $brightness . '%)';
		}

		if ( empty( $filters ) ) {
			return '';
		}

		return '.ml-container .ml-extra-div{filter:' . implode( ' ', $filters ) . ';}';
	}

	/**
	 * Build CSS for the miscellaneous (links, custom background) options.
	 *
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
				'text-shadow',
				'color',
				'width',
			),
			array(
				'button-background',
				'button-border-color',
				'button-shadow',
				'button-text-shadow',
				'button-color',
				'button-width',
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

		$string .= $this->create_css_lines(
			'#login form .forgetmenot',
			array(
				'display',
			),
			array(
				'hide-rememberme',
			)
		);

		return $string;
	}

	/**
	 * Build CSS for the login form options.
	 *
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
			'#loginform,#registerform,#lostpasswordform',
			array(
				'min-height',
				'background-image',
				'background-color',
				'padding',
				'border',
				'border-radius',
				'box-shadow',
			),
			array(
				'form-height',
				'form-background-image',
				'form-background-color',
				'form-padding',
				'form-border',
				'form-border-radius',
				'form-shadow',
			)
		);

		/**
		 * Set form field variables
		 */
		$string .= $this->create_css_lines(
			'.login form .input, .login input[type="text"], .login input[type="password"]',
			array(
				'max-width',
				'margin',
				'border-radius',
				'border',
				'background',
				'color',
			),
			array(
				'form-field-width',
				'form-field-margin',
				'form-field-border-radius',
				'form-field-border',
				'form-field-background',
				'form-field-color',
			)
		);

		/**
		 * Set form field labels
		 */
		$string .= $this->create_css_lines(
			'.login label',
			array(
				'color',
			),
			array(
				'form-label-color',
			)
		);

		$string .= $this->create_css_lines(
			'#login > #nav,#login > #backtoblog',
			array(
				'display',
			),
			array(
				'hide-extra-links',
			)
		);

		return $string;
	}

	/**
	 * Build CSS for the page background options.
	 *
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

		$string .= $this->create_css_lines(
			'.ml-container .ml-form-container',
			array(
				'background-image',
				'background-color',
			),
			array(
				'custom-background-form',
				'custom-background-color-form',
			)
		);

		/**
		 * Set background-color for half screens
		 */
		$string .= $this->create_css_lines(
			'.ml-half-screen div.ml-form-container',
			array(
				'background-color',
			),
			array(
				'custom-background-color',
			)
		);
		return $string;
	}

	/**
	 * Build CSS for the logo options.
	 *
	 * @return string
	 */
	public function _set_logo_options() {
		$string = '';
		/**
		 * Set logo dimensions
		 */
		$string .= $this->create_css_lines(
			'.login:not(.clc-both-logo) h1 a',
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

		$string .= $this->create_css_lines(
			'.login.clc-both-logo h1 a',
			array(
				'background-image',
				'background-size',
			),
			array(
				'custom-logo',
				'logo-width',
			)
		);

		$string .= $this->create_css_lines(
			'.login.clc-text-logo h1 a',
			array(
				'color',
				'font-size',
			),
			array(
				'logo-text-color',
				'logo-text-size',
			)
		);

		$string .= $this->create_css_lines(
			'.login.clc-both-logo h1 a',
			array(
				'background-size',
				'padding-top',
			),
			array(
				'logo-width',
				'logo-width',
			)
		);

		$string .= $this->create_css_lines(
			'.login.clc-text-logo h1 a:hover,.login.clc-both-logo h1 a:hover',
			array(
				'color',
			),
			array(
				'logo-text-color-hover',
			)
		);

		$string .= $this->create_css_lines(
			'#login > h1',
			array(
				'display',
			),
			array(
				'logo-settings',
			)
		);

		return $string;
	}

	/**
	 * Build a CSS rule block for a selector from the given options.
	 *
	 * @param string $selector   CSS selector.
	 * @param array  $properties CSS properties to set, indexed alongside $options.
	 * @param array  $options    Option keys whose values feed the properties.
	 *
	 * @return string
	 */
	private function create_css_lines( $selector, $properties, $options ) {

		$string = '';
		$valued = array();
		$i      = 0;

		foreach ( $options as $option ) {
			if ( ! empty( $this->options[ $option ] ) ) {
				$val = $this->options[ $option ];

				// 3 toggle buttons were replaced with one select, so we need to make sure
				// h1 displays correctly.
				if ( 'logo-settings' === $option ) {
					$val = ( 'hide-logo' === $val ) ? '1' : '0';
				}

				$valued[ $properties[ $i ] ] = $val;
			}
			++$i;
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
	 * Append CSS units/wrappers to a raw value based on the property.
	 *
	 * @param string $property CSS property name.
	 * @param string $value    Raw value to decorate.
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
			case 'font-size':
				// Append px only to bare numbers, so values that already carry a
				// unit or keyword (e.g. 100%, auto) are passed through unchanged.
				if ( is_numeric( $value ) ) {
					$value = $value . 'px';
				}
				break;
			case 'display':
				if ( ! $value ) {
					$value = 'block';
				} else {
					$value = 'none';
				}
				// Fall through to default.
			default:
				break;
		}

		return $value;
	}

	/**
	 * Sanitize CSS input to prevent injection attacks.
	 *
	 * @param string $css Raw CSS input.
	 * @return string Sanitized CSS.
	 */
	private function sanitize_css( string $css ): string {
		if ( empty( $css ) ) {
			return '';
		}

		// Remove any potential script injections.
		$css = wp_strip_all_tags( $css );

		// Remove potentially dangerous CSS expressions and behaviors.
		$css = preg_replace( '/expression\s*\(/i', '', $css );
		$css = preg_replace( '/javascript\s*:/i', '', $css );
		$css = preg_replace( '/behavior\s*:/i', '', $css );
		$css = preg_replace( '/-moz-binding\s*:/i', '', $css );
		$css = preg_replace( '/@import/i', '', $css );
		$css = preg_replace( '/url\s*\(\s*["\']?\s*data:/i', 'url(', $css );

		return $css;
	}

	/**
	 * Filter the login page body classes based on the saved layout options.
	 *
	 * @param array $classes Existing body classes.
	 * @return array Modified body classes.
	 */
	public function body_class( $classes ) {

		if ( '2' === $this->options['columns'] ) {
			$classes[] = 'ml-half-screen';
			if ( isset( $this->options['form-column-align'] ) ) {
				$classes[] = 'ml-login-align-' . esc_attr( $this->options['form-column-align'] );
			}
		}

		if ( isset( $this->options['form-vertical-align'] ) ) {
			$classes[] = 'ml-login-vertical-align-' . esc_attr( $this->options['form-vertical-align'] );
		}

		if ( isset( $this->options['form-horizontal-align'] ) ) {
			$classes[] = 'ml-login-horizontal-align-' . esc_attr( $this->options['form-horizontal-align'] );
		}

		if ( isset( $this->options['logo-settings'] ) && 'show-text-only' == $this->options['logo-settings'] ) {
			$classes[] = 'clc-text-logo';
		}

		if ( isset( $this->options['logo-settings'] ) && 'use-both' == $this->options['logo-settings'] ) {
			$classes[] = 'clc-text-logo clc-both-logo';
		}

		return $classes;
	}

	/**
	 * Filter the login logo link URL.
	 *
	 * @param string $url Default logo URL.
	 * @return string Custom or default logo URL.
	 */
	public function logo_url( $url ) {
		if ( '' !== $this->options['logo-url'] ) {
			return esc_url( $this->options['logo-url'] );
		}

		return $url;
	}

	/**
	 * Filter the login logo link title/text.
	 *
	 * @param string $title Default logo title.
	 * @return string Custom or default logo title.
	 */
	public function logo_title( $title ) {
		if ( isset( $this->options['logo-title'] ) ) {
			return wp_kses_post( $this->options['logo-title'] );
		}

		return $title;
	}

	/**
	 * Filter the login page document title.
	 *
	 * @param string $title Default page title.
	 * @return string Custom or default page title.
	 */
	public function login_page_title( $title ) {
		if ( isset( $this->options['login-page-title'] ) ) {
			return esc_html( $this->options['login-page-title'] );
		}

		return $title;
	}

	/**
	 * Output the inline CSS.
	 *
	 * @return void
	 */
	public function generate_css(): void {
		$css         = $this->create_css();
		$custom_css  = $this->options['custom-css'];
		$columns_css = '';

		if ( 2 === (int) $this->options['columns'] ) {
			$widths = $this->options['columns-width'];

			$left_width  = ( 100 / 12 ) * absint( $widths['left'] );
			$right_width = ( 100 / 12 ) * absint( $widths['right'] );

			$columns_css .= '.ml-half-screen.ml-login-align-3 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-1 .ml-container .ml-form-container{ width:' . $left_width . '%; }';
			$columns_css .= '.ml-half-screen.ml-login-align-4 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-2 .ml-container .ml-form-container{ flex-basis:' . $left_width . '%; }';

			$columns_css .= '.ml-half-screen.ml-login-align-3 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-1 .ml-container .ml-extra-div{ width:' . $right_width . '%; }';
			$columns_css .= '.ml-half-screen.ml-login-align-4 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-2 .ml-container .ml-extra-div{ flex-basis:' . $right_width . '%; }';

		}

		if ( ! empty( $this->options['logo-height'] ) && ! empty( $this->options['logo-width'] ) ) {
			$backgriund_size = absint( $this->options['logo-width'] ) . 'px ' . absint( $this->options['logo-height'] ) . 'px';
		} else {
			$backgriund_size = '20px 20px';
		}

		if ( ! empty( $this->options['custom-logo'] ) ) {
			$background_image = $this->options['custom-logo'];
		} else {
			$background_image = get_site_url() . '/wp-admin/images/wordpress-logo.svg';
		}

		$logo_css = '.login.clc-both-logo h1 a{width:100%;height:100%;text-indent: unset;background-position:top center !important;padding-top:' . ( 30 + absint( $this->options['logo-height'] ) ) . 'px; background-size: ' . $backgriund_size . '; margin-top: -' . ( 15 + absint( $this->options['logo-height'] ) ) . 'px; position:relative;background-image:url(' . esc_url( $background_image ) . ')}';

		// CSS is built from values sanitized on save (colors, dimensions, image URLs) and via sanitize_css(); it cannot be passed through esc_html() without breaking the stylesheet.
		echo '<style type="text/css">' . $this->get_base_css() . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static, developer-defined base CSS.
		echo '<style type="text/css" id="clc-style">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from sanitized option values, escaped per-property in create_css_lines().
		echo '<style type="text/css" id="clc-columns-style">' . $columns_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from absint() column widths.
		echo '<style type="text/css" id="clc-logo-style">' . $logo_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from absint() dimensions and esc_url() logo path.
		echo '<style type="text/css" id="clc-custom-css">' . $this->sanitize_css( $custom_css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Passed through sanitize_css() which strips scripts and dangerous directives.
		echo '<style type="text/css" id="clc-custom-background-link"> body .ml-container .ml-extra-div .clc-custom-background-link {display:block; width:100%; height:100%;} </style>';
	}

	/**
	 * Output the opening markup for the two-column layout wrapper.
	 *
	 * @return void
	 */
	public function add_extra_div() {

		$options = get_option( 'clc-options' );

		if ( isset( $options['custom-background'] ) && '' !== $options['custom-background'] && isset( $options['custom-background-link'] ) && '' !== $options['custom-background-link'] ) {
			echo '<div class="ml-container"><div class="ml-extra-div"><a class="clc-custom-background-link" href="' . esc_url( $options['custom-background-link'] ) . '"></a></div><div class="ml-form-container">';
		} else {
			echo '<div class="ml-container"><div class="ml-extra-div"></div><div class="ml-form-container">';
		}
	}

	/**
	 * Output the closing markup for the two-column layout wrapper.
	 *
	 * @return void
	 */
	public function close_extra_div() {
		echo '</div></div>';
	}

	/**
	 * Output custom text/HTML above the login form.
	 *
	 * Hooked late on login_header so it renders inside the form container, just
	 * above the #login box.
	 *
	 * @return void
	 */
	public function render_above_form() {
		$content = isset( $this->options['above-form-text'] ) ? trim( (string) $this->options['above-form-text'] ) : '';

		if ( '' === $content ) {
			return;
		}

		echo '<div class="clc-above-form">' . wp_kses_post( $content ) . '</div>';
	}

	/**
	 * Output a custom footer (links + free text) below the login form.
	 *
	 * Hooked early on login_footer so it renders inside the form container,
	 * before the wrapper is closed.
	 *
	 * @return void
	 */
	public function render_footer() {
		$footer = isset( $this->options['footer-text'] ) ? trim( (string) $this->options['footer-text'] ) : '';
		$links  = array();

		for ( $i = 1; $i <= 3; $i++ ) {
			$text = isset( $this->options[ 'footer-link-' . $i . '-text' ] ) ? trim( (string) $this->options[ 'footer-link-' . $i . '-text' ] ) : '';
			$url  = isset( $this->options[ 'footer-link-' . $i . '-url' ] ) ? trim( (string) $this->options[ 'footer-link-' . $i . '-url' ] ) : '';

			if ( '' !== $text && '' !== $url ) {
				$links[] = array(
					'text' => $text,
					'url'  => $url,
				);
			}
		}

		if ( '' === $footer && empty( $links ) ) {
			return;
		}

		echo '<div class="clc-custom-footer">';

		if ( ! empty( $links ) ) {
			echo '<p class="clc-footer-links">';
			$first = true;
			foreach ( $links as $link ) {
				if ( ! $first ) {
					echo ' <span class="clc-footer-sep">&middot;</span> ';
				}
				echo '<a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['text'] ) . '</a>';
				$first = false;
			}
			echo '</p>';
		}

		if ( '' !== $footer ) {
			echo '<div class="clc-footer-text">' . wp_kses_post( $footer ) . '</div>';
		}

		echo '</div>';
	}

	/**
	 * Register gettext filters for the shared/general login texts.
	 *
	 * @return void
	 */
	public function check_general_texts() {

		add_filter( 'gettext', array( $this, 'change_lost_password_text' ), 99, 3 );
		add_filter( 'gettext_with_context', array( $this, 'change_back_to_text' ), 99, 4 );
	}

	/**
	 * Register gettext filters for the login-page texts.
	 *
	 * @return void
	 */
	public function check_login_texts() {

		add_filter( 'gettext', array( $this, 'change_username_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_password_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_rememberme_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_login_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_register_login_link_text' ), 99, 3 );
	}

	/**
	 * Register gettext filters for the registration-page texts.
	 *
	 * @return void
	 */
	public function check_register_texts() {

		add_filter( 'gettext', array( $this, 'change_register_username_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_register_email_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_register_register_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_register_confirmation_text' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_login_register_link_text' ), 99, 3 );
	}

	/**
	 * Register gettext filters for the lost-password-page texts.
	 *
	 * @return void
	 */
	public function check_lostpasswords_texts() {

		add_filter( 'gettext', array( $this, 'change_lostpasswords_username_label' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_lostpasswords_button_label' ), 99, 3 );

		add_filter( 'gettext', array( $this, 'change_register_login_link_text' ), 99, 3 );
		add_filter( 'gettext', array( $this, 'change_login_register_link_text' ), 99, 3 );
	}


	/**
	 * Customizer output for custom username label.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_username_label( $translated_text, $text, $domain ) {
		$default = 'Username or Email Address';
		$label   = $this->options['username-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = wp_kses_post( $label );
		}

		return $translated_text;
	}
	/**
	 * Customizer output for custom password label.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_password_label( $translated_text, $text, $domain ) {
		$default = 'Password';
		$label   = $this->options['password-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom remember me text.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_rememberme_label( $translated_text, $text, $domain ) {
		$default = 'Remember Me';
		$label   = $this->options['rememberme-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom lost your password text.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_lost_password_text( $translated_text, $text, $domain ) {
		$default = 'Lost your password?';
		$label   = $this->options['lost-password-text'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom back to text.
	 *
	 * @param string $translated_text The translated text.
	 * @param string $text            The label we want to replace.
	 * @param string $context         The gettext context.
	 * @param string $domain          The text domain of the site.
	 * @return string
	 */
	public function change_back_to_text( $translated_text, $text, $context, $domain ) {
		$default = '&larr; Back to %s';
		$label   = $this->options['back-to-text'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = '&larr; ' . esc_html( $label ) . ' %s';
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom login text.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_login_label( $translated_text, $text, $domain ) {
		$default = 'Log In';
		$label   = $this->options['login-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_attr( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom register username label.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_register_username_label( $translated_text, $text, $domain ) {
		$default = 'Username';
		$label   = $this->options['register-username-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = wp_kses_post( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom register email label.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_register_email_label( $translated_text, $text, $domain ) {
		$default = 'Email';
		$label   = $this->options['register-email-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom registration confirmation text.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_register_confirmation_text( $translated_text, $text, $domain ) {
		$default = 'Registration confirmation will be emailed to you.';
		$label   = $this->options['register-confirmation-email'];
		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = wp_kses_post( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for custom register button text.
	 *
	 * @param string|string $translated_text The translated text.
	 * @param string|string $text The label we want to replace.
	 * @param string|string $domain The text domain of the site.
	 * @return string
	 */
	public function change_register_register_label( $translated_text, $text, $domain ) {
		$default = 'Register';
		$label   = $this->options['register-button-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for the login link text.
	 *
	 * @param string $translated_text The translated text.
	 * @param string $text            The label we want to replace.
	 * @param string $domain          The text domain of the site.
	 * @return string
	 */
	public function change_login_register_link_text( $translated_text, $text, $domain ) {
		$default = 'Log in';
		$label   = $this->options['login-link-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for the register link text.
	 *
	 * @param string $translated_text The translated text.
	 * @param string $text            The label we want to replace.
	 * @param string $domain          The text domain of the site.
	 * @return string
	 */
	public function change_register_login_link_text( $translated_text, $text, $domain ) {
		$default = 'Register';
		$label   = $this->options['register-link-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for the lost-password username label.
	 *
	 * @param string $translated_text The translated text.
	 * @param string $text            The label we want to replace.
	 * @param string $domain          The text domain of the site.
	 * @return string
	 */
	public function change_lostpasswords_username_label( $translated_text, $text, $domain ) {
		$default = 'Username or Email Address';
		$label   = $this->options['lostpassword-username-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = wp_kses_post( $label );
		}

		return $translated_text;
	}

	/**
	 * Customizer output for the lost-password button label.
	 *
	 * @param string $translated_text The translated text.
	 * @param string $text            The label we want to replace.
	 * @param string $domain          The text domain of the site.
	 * @return string
	 */
	public function change_lostpasswords_button_label( $translated_text, $text, $domain ) {
		$default = 'Get New Password';
		$label   = $this->options['lostpassword-button-label'];

		// Check if this is our text.
		if ( $default !== $text ) {
			return $translated_text;
		}

		// Check if the label is changed.
		if ( $label === $text ) {
			return $translated_text;
		} else {
			$translated_text = esc_html( $label );
		}

		return $translated_text;
	}

	/**
	 * Get the base CSS for the login page
	 *
	 * @return string
	 * @since 1.3.2
	 */
	private function get_base_css() {
		return '
		/* Hide third-party theme customizer overlays (Astra, etc.) */
		.ast-style-guide-wrapper,
		.ast-quick-tour-body,
		.ast-close-tour,
		.ast-tour-inner-wrap {
			display: none !important;
		}
		.language-switcher{
			z-index:9;
			margin:0;
		}
		/* Footer & Links (#173 too): give the custom above-form text, custom footer,
		   the core language switcher and the privacy-policy link their own full-width
		   rows so they stack centered around the form instead of sitting beside
		   #login as flex siblings (.ml-form-container also gets flex-wrap below). */
		.ml-form-container > .clc-above-form,
		.ml-form-container > .clc-custom-footer,
		.ml-form-container > .language-switcher,
		.ml-form-container > .privacy-policy-page-link{
			flex:0 0 100%;
			max-width:100%;
			text-align:center;
			/* Stack above the absolute .ml-extra-div background layer, like #login,
			   so custom text/links stay visible when a background is set. */
			position:relative;
			z-index:1;
		}
		.clc-above-form{
			margin:0 0 16px;
			order:-1;
		}
		.ml-form-container > .clc-custom-footer{
			margin:16px 0 0;
			order:1;
		}
		.ml-form-container > .privacy-policy-page-link{
			order:2;
		}
		.ml-form-container > .language-switcher{
			order:3;
		}
		.clc-footer-links{
			margin:0 0 8px;
		}
		.clc-footer-links a{
			color:inherit;
			text-decoration:underline;
		}
		.clc-footer-sep{
			opacity:.5;
		}
		.clc-footer-text{
			font-size:13px;
			opacity:.85;
		}
		#registerform #wp-submit{
			float:none;
			margin-top:15px;
		}
		.login.clc-text-logo:not(.clc-both-logo) h1 a{
			 background-image: none !important;
			text-indent: unset;
			width:auto !important;
			height: auto !important;
		}
		#login form p label br{
			display:none
		}
		body:not( .ml-half-screen ) .ml-form-container{
			background:transparent !important;
		}
		.login:not(.clc-both-logo) h1 a{
			background-position: center;
			background-size:contain !important;
		}
		/* #70: center an image logo even when its width exceeds the form
		   container (flex centers an over-wide child; block margin:auto cannot). */
		.login:not(.clc-text-logo):not(.clc-both-logo) h1{
			display:flex;
			justify-content:center;
		}
		.ml-container #login{
			 position:relative;
			padding: 0;
			width:100%;
			max-width:320px;
			margin:0;
		}
		#loginform,#registerform,#lostpasswordform{
			box-sizing: border-box;
			max-height: 100%;
			background-position: center;
			background-repeat: no-repeat;
			background-size: cover;
		}
		/* Fix password field width to match username field */
		.login form .input,
		.login input[type="text"],
		.login input[type="password"],
		.login input[type="email"]{
			width: 100% !important;
			box-sizing: border-box !important;
		}
		.login .user-pass-wrap{
			display: block !important;
		}
		.login .user-pass-wrap > label{
			display: block !important;
		}
		.login .wp-pwd{
			position: relative !important;
			display: block !important;
		}
		.login .wp-pwd input[type="password"]{
			width: 100% !important;
			padding-right: 50px !important;
			box-sizing: border-box !important;
		}
		.login .wp-pwd .wp-hide-pw{
			position: absolute !important;
			right: 0 !important;
			top: 35% !important;
			transform: translateY(-50%) !important;
			height: auto !important;
			width: 44px !important;
			padding: 0 !important;
			margin: 0 !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			background: transparent !important;
			border: none !important;
			box-shadow: none !important;
			cursor: pointer !important;
			z-index: 10 !important;
		}
		.login .wp-pwd .wp-hide-pw .dashicons{
			position: static !important;
			top: auto !important;
			margin: 0 !important;
			padding: 0 !important;
		}
		.ml-container{
			position:relative;
			min-height:100vh;
			display:flex;
			height:100%;
			min-width:100%;
		}
		.ml-container .ml-extra-div{
			background-position:center;
			background-size:cover;
			background-repeat:no-repeat
		}
		body .ml-form-container{
			display:flex;
			flex-wrap:wrap;
			align-items:center;
			align-content:center;
			justify-content:center;
		}
		body:not( .ml-half-screen ) .ml-container .ml-extra-div{
			position:absolute;
			top:0;
			left:0;
			width:100%;
			height:100%
		}
		body:not( .ml-half-screen ) .ml-container .ml-form-container{
			width:100%;
			min-height:100vh;
		}
		body.ml-half-screen .ml-container{
			flex-wrap:wrap
		}
		body.ml-half-screen .ml-container>.ml-extra-div,body.ml-half-screen .ml-container>.ml-form-container{
			width:50%
		}
		body.ml-half-screen.ml-login-align-2 .ml-container>div,body.ml-half-screen.ml-login-align-4 .ml-container>div{
			width:100%;
			flex-basis:50%;
		}
		body.ml-half-screen.ml-login-align-2 .ml-container{
			flex-direction:column-reverse
		}
		body.ml-half-screen.ml-login-align-4 .ml-container{
			flex-direction:column
		}
		body.ml-half-screen.ml-login-align-1 .ml-container{
			flex-direction:row-reverse
		}
		body.ml-login-vertical-align-1 .ml-form-container{
			align-items:flex-start
		}
		body.ml-login-vertical-align-3 .ml-form-container{
			align-items:flex-end
		}
		body.ml-login-horizontal-align-1 .ml-form-container{
			justify-content:flex-start
		}
		body.ml-login-horizontal-align-3 .ml-form-container{
			justify-content:flex-end
		}
		@media only screen and (max-width: 768px) {
			body.ml-half-screen .ml-container > .ml-extra-div, body.ml-half-screen .ml-container > .ml-form-container{
				width:50% !important;
			}
			.login h1 a{
				max-width: 100%;
			}
		}
		.login input[type=text]:focus, .login input[type=search]:focus, .login input[type=radio]:focus, .login input[type=tel]:focus, .login input[type=time]:focus, .login input[type=url]:focus, .login input[type=week]:focus, .login input[type=password]:focus, .login input[type=checkbox]:focus, .login input[type=color]:focus, .login input[type=date]:focus, .login input[type=datetime]:focus, .login input[type=datetime-local]:focus, .login input[type=email]:focus, .login input[type=month]:focus, .login input[type=number]:focus, .login select:focus, .login textarea:focus{
			 box-shadow: none;
		}
		@media only screen and (max-width: 577px){
			body.ml-half-screen .ml-container > .ml-extra-div, body.ml-half-screen .ml-container > .ml-form-container{
				width:100% !important;
			}
			body.ml-half-screen.ml-login-align-1 .ml-container .ml-extra-div, body.ml-half-screen.ml-login-align-1 .ml-container .ml-form-container,body.ml-half-screen.ml-login-align-3 .ml-container .ml-extra-div,body.ml-half-screen.ml-login-align-3 .ml-container .ml-form-container{
				 width: 100%;
			}
			body.ml-half-screen.ml-login-align-1 .ml-container .ml-extra-div,body.ml-half-screen.ml-login-align-3 .ml-container .ml-extra-div{
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
			}
		}
		';
	}
}

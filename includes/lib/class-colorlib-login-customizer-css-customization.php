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
	 * Whether the option/selector data has been loaded yet.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Constructor.
	 *
	 * Deliberately cheap: this object is created on `init` for every request,
	 * so it only registers the two entry points it needs. Reading options and
	 * building the selector map is deferred to boot(), which runs solely on the
	 * login page and in the Customizer preview.
	 */
	public function __construct() {
		add_action( 'login_init', array( $this, 'init_login' ) );
		add_action( 'customize_preview_init', array( $this, 'output_css_object' ), 26 );
	}

	/**
	 * Load options and the selector map, once per request.
	 *
	 * @return void
	 */
	private function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted   = true;
		$plugin         = Colorlib_Login_Customizer::instance();
		$this->key_name = $plugin->key_name;
		$this->defaults = $plugin->get_defaults();

		$this->set_options();
	}

	/**
	 * Register every login-page hook.
	 *
	 * Runs on `login_init`, which fires both on wp-login.php and in the
	 * Customizer preview template, so none of these filters can leak onto the
	 * front end. (The privacy-policy filter in particular is global, and used
	 * to hide the link site-wide when it was registered on `init`.)
	 *
	 * @return void
	 */
	public function init_login(): void {
		$this->boot();

		$this->check_general_texts();

		add_action( 'login_form_login', array( $this, 'check_login_texts' ) );
		add_action( 'login_form_register', array( $this, 'check_register_texts' ) );
		add_action( 'login_form_lostpassword', array( $this, 'check_lostpasswords_texts' ) );

		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ) );

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

		$this->fix_selective_refresh_exports();
	}

	/**
	 * Print the selective-refresh export data core would normally emit.
	 *
	 * The Customizer preview template mirrors wp-login.php rather than a theme
	 * page, so it never calls wp_head() and `wp_enqueue_scripts` never fires.
	 * Core hooks WP_Customize_Selective_Refresh::export_preview_data() to that
	 * action, yet the selective-refresh script still loads because
	 * `customize-preview-nav-menus` lists it as a dependency — so the script
	 * runs without the `_customizePartialRefreshExports` global it reads and
	 * throws a JS error in the preview.
	 *
	 * Call core's own exporter just before wp_print_footer_scripts, which runs
	 * on `login_footer` at priority 20.
	 *
	 * @return void
	 */
	private function fix_selective_refresh_exports(): void {
		if ( ! is_customize_preview() || ! isset( $GLOBALS['wp_customize'] ) ) {
			return;
		}

		$selective_refresh = $GLOBALS['wp_customize']->selective_refresh;

		if ( $selective_refresh instanceof WP_Customize_Selective_Refresh ) {
			add_action( 'login_footer', array( $selective_refresh, 'export_preview_data' ), 19 );
		}
	}

	/**
	 * Enqueue the static base stylesheet for the login page.
	 *
	 * Kept as a real file rather than inline CSS so browsers can cache it
	 * between login page views.
	 *
	 * @return void
	 */
	public function enqueue_login_styles(): void {
		wp_enqueue_style(
			'colorlib-login-customizer-login',
			COLORLIB_LOGIN_CUSTOMIZER_URL . 'assets/css/clc-login.css',
			array(),
			COLORLIB_LOGIN_CUSTOMIZER_VERSION
		);
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

		$this->boot();

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
				/*
				 * Escaped with clc_escape_css_value(), not esc_attr(): HTML
				 * entities are not decoded inside a <style> element, so
				 * esc_attr() would corrupt legitimate values (quoted font
				 * names became &#039;) while blocking nothing.
				 */
				$declaration = clc_escape_css_value( (string) $this->add_artifacts( $index, $value ) );

				if ( '' === $declaration ) {
					continue;
				}

				$string .= $index . ':' . $declaration . ';' . "\n";
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
				// Quote the URL and drop characters that could close url() early.
				$value = 'url("' . str_replace( array( '"', "'", '(', ')' ), '', (string) $value ) . '")';
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
		if ( ! empty( $this->options['login-page-title'] ) ) {
			$title = $this->options['login-page-title'];
		}

		/*
		 * Core echoes the title into <title> without escaping, and <title> is
		 * RCDATA, so a stray "</title>" would break out of the element. kses
		 * strips tags while leaving core's own entities (&lsaquo;, &#8212;)
		 * intact, which esc_html() would double-encode.
		 */
		return wp_kses_post( (string) $title );
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

		/*
		 * The static base stylesheet is enqueued as a real file by
		 * enqueue_login_styles(); only the settings-derived rules are inlined
		 * here. Every value below is sanitized on save (colors, dimensions,
		 * image URLs) and re-filtered through clc_sanitize_css() on output, so
		 * it cannot be passed through esc_html() without breaking the CSS.
		 */
		echo '<style id="clc-style">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from sanitized option values, filtered per-property in create_css_lines().
		echo '<style id="clc-columns-style">' . $columns_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from absint() column widths.
		echo '<style id="clc-logo-style">' . $logo_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from absint() dimensions and esc_url() logo path.
		echo '<style id="clc-custom-css">' . clc_sanitize_css( (string) $custom_css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Passed through clc_sanitize_css(), which strips tags and dangerous directives.
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
			// Core prints this label with _e() (unescaped), so escape here.
			$translated_text = esc_html( $label );
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
			// Core prints this with esc_html_e(); escaping here would double-encode.
			$translated_text = $label;
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
		/*
		 * WordPress 5.7 renamed this string from "Back to %s" to "Go to %s"
		 * (and added the `login_site_html_link` filter). Match both so the
		 * setting keeps working on current and older installs.
		 */
		$patterns = array( '&larr; Back to %s', '&larr; Go to %s' );

		// Check if this is our text.
		if ( ! in_array( $text, $patterns, true ) ) {
			return $translated_text;
		}

		$label = isset( $this->options['back-to-text'] ) ? (string) $this->options['back-to-text'] : '';

		/*
		 * On an install that never saved this setting the value falls back to
		 * the full legacy pattern from get_defaults(), which is not something a
		 * user would ever type. Treat that as "not customised" and leave core's
		 * own wording alone.
		 */
		if ( '' === trim( $label ) || in_array( $label, $patterns, true ) ) {
			return $translated_text;
		}

		/*
		 * The stored value is meant to be a bare phrase ("Back to site"), but
		 * older versions saved the arrow and the placeholder too. Strip both so
		 * the result carries exactly one %s for core's sprintf() — two would
		 * raise ArgumentCountError and take the whole login page down.
		 */
		$label = str_replace( array( '&larr;', '←', '%s' ), '', $label );
		$label = trim( $label );

		if ( '' === $label ) {
			return $translated_text;
		}

		// Escape stray percent signs so sprintf() only ever sees our own %s.
		$label = str_replace( '%', '%%', $label );

		return '&larr; ' . esc_html( $label ) . ' %s';
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
			// Core prints the button value with esc_attr_e(); escaping here would double-encode.
			$translated_text = $label;
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
			// Core prints this label with _e() (unescaped), so escape here.
			$translated_text = esc_html( $label );
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
			// Core prints this with _e() (unescaped), so escape here.
			$translated_text = esc_html( $label );
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
			// Core prints the button value with esc_attr_e(); escaping here would double-encode.
			$translated_text = $label;
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
			// Core prints this label with _e() (unescaped), so escape here.
			$translated_text = esc_html( $label );
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
			// Core prints the button value with esc_attr_e(); escaping here would double-encode.
			$translated_text = $label;
		}

		return $translated_text;
	}
}

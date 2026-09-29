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
	 * The untranslated logo title stored by get_defaults().
	 *
	 * An install that never changed the title still has this English string
	 * saved as its default, so it is treated as "not customised" and core's
	 * own translated title is kept instead.
	 */
	const DEFAULT_LOGO_TITLE = 'Powered by WordPress';

	/**
	 * Plugin options.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = array();

	/**
	 * CLC WP options name.
	 *
	 * @var string
	 */
	public string $key_name = 'clc-options';

	/**
	 * Default options.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = array();

	/**
	 * Whether the option data has been loaded yet.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Core login strings replaced through gettext, keyed by source string.
	 *
	 * Filled per login action by the check_*_texts() methods and read by the
	 * single filter_login_text() callback. `escape` says whether core prints
	 * the string raw (so it must be escaped here); `empty` says what an empty
	 * setting renders as: 'blank', 'hide' (screen-reader only, so the field
	 * keeps an accessible name) or 'keep' (core's own text — a button can
	 * never be left without a label).
	 *
	 * @var array<string, array{option: string, escape: bool, empty: string}>
	 */
	private array $login_texts = array();

	/**
	 * Constructor.
	 *
	 * Deliberately cheap: this object is created on `init` for every request,
	 * so it only registers the two entry points it needs. Reading options is
	 * deferred to boot(), which runs solely on the login page and in the
	 * Customizer preview.
	 */
	public function __construct() {
		add_action( 'login_init', array( $this, 'init_login' ) );
		add_action( 'customize_preview_init', array( $this, 'output_css_object' ), 26 );
	}

	/**
	 * Load options, once per request.
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
	private function generate_name( string $id ): string {
		return $this->key_name . '[' . $id . ']';
	}

	/**
	 * Send the CSS rule map and current values to the Customizer preview.
	 *
	 * The preview script rebuilds #clc-style from exactly the same rules that
	 * create_css() prints on the real login page, so the live preview and the
	 * saved result cannot drift apart.
	 *
	 * @return void
	 */
	public function output_css_object(): void {
		$this->boot();

		$rules    = $this->get_css_rules();
		$settings = array();
		$options  = array( 'background-blur', 'background-brightness' );

		foreach ( $rules as $rule ) {
			foreach ( $rule['declarations'] as $option ) {
				$options[] = $option;
			}
		}

		foreach ( array_unique( $options ) as $option ) {
			$settings[ $option ] = array(
				'name'  => $this->generate_name( $option ),
				'value' => $this->options[ $option ] ?? '',
			);
		}

		wp_localize_script(
			'colorlib-login-customizer-preview',
			'CLC',
			array(
				'rules'       => $rules,
				'settings'    => $settings,
				'defaultLogo' => admin_url( 'images/wordpress-logo.svg' ),
			)
		);
	}

	/**
	 * Load the saved options merged over the defaults.
	 *
	 * @return void
	 */
	public function set_options(): void {
		$options = get_option( $this->key_name, array() );

		$this->options = wp_parse_args( is_array( $options ) ? $options : array(), $this->defaults );
	}

	/**
	 * The settings-driven CSS rules, in output order.
	 *
	 * Single source of truth for both the login page (create_css()) and the
	 * Customizer live preview (output_css_object()). Each declaration maps a
	 * CSS property to the option that supplies its value; a declaration is
	 * skipped while its option is empty.
	 *
	 * @return array<int, array{selector: string, declarations: array<string, string>}>
	 */
	public function get_css_rules(): array {
		return array(
			array(
				'selector'     => '.ml-container .ml-extra-div',
				'declarations' => array(
					'background-image' => 'custom-background',
					'background-color' => 'custom-background-color',
				),
			),

			/*
			 * Two-column fallback: paint the form column with the page colour.
			 * It must come before, and be no more specific than, the "Form
			 * Column background" rule below so that setting wins when set. (It
			 * used to be `div.ml-form-container`, which outranked it and made
			 * the form-column colour impossible to change.)
			 */
			array(
				'selector'     => '.ml-half-screen .ml-form-container',
				'declarations' => array(
					'background-color' => 'custom-background-color',
				),
			),
			array(
				'selector'     => '.ml-container .ml-form-container',
				'declarations' => array(
					'background-image' => 'custom-background-form',
					'background-color' => 'custom-background-color-form',
				),
			),
			array(
				'selector'     => '.login:not(.clc-both-logo) h1 a',
				'declarations' => array(
					'background-image' => 'custom-logo',
					'width'            => 'logo-width',
					'height'           => 'logo-height',
				),
			),
			array(
				'selector'     => '.login.clc-both-logo h1 a',
				'declarations' => array(
					'background-image' => 'custom-logo',
				),
			),
			array(
				'selector'     => '.login.clc-text-logo h1 a',
				'declarations' => array(
					'color'     => 'logo-text-color',
					'font-size' => 'logo-text-size',
				),
			),
			array(
				'selector'     => '.login.clc-text-logo h1 a:hover,.login.clc-both-logo h1 a:hover',
				'declarations' => array(
					'color' => 'logo-text-color-hover',
				),
			),
			array(
				'selector'     => '#login > h1',
				'declarations' => array(
					'display' => 'logo-settings',
				),
			),
			array(
				'selector'     => '.ml-container #login',
				'declarations' => array(
					'max-width' => 'form-width',
				),
			),
			array(
				'selector'     => '#loginform,#registerform,#lostpasswordform',
				'declarations' => array(
					'min-height'       => 'form-height',
					'background-image' => 'form-background-image',
					'background-color' => 'form-background-color',
					'padding'          => 'form-padding',
					'border'           => 'form-border',
					'border-radius'    => 'form-border-radius',
					'box-shadow'       => 'form-shadow',
				),
			),
			array(
				'selector'     => '.login form .input,.login input[type="text"],.login input[type="password"]',
				'declarations' => array(
					'max-width'     => 'form-field-width',
					'margin'        => 'form-field-margin',
					'border-radius' => 'form-field-border-radius',
					'border'        => 'form-field-border',
					'background'    => 'form-field-background',
					'color'         => 'form-field-color',
				),
			),
			array(
				'selector'     => '.login label',
				'declarations' => array(
					'color' => 'form-label-color',
				),
			),
			array(
				'selector'     => '#login > #nav,#login > #backtoblog',
				'declarations' => array(
					'display' => 'hide-extra-links',
				),
			),
			array(
				'selector'     => '.wp-core-ui .button-primary.focus,.wp-core-ui .button-primary.hover,.wp-core-ui .button-primary:focus,.wp-core-ui .button-primary:hover',
				'declarations' => array(
					'background'   => 'button-background-hover',
					'border-color' => 'button-border-color-hover',
				),
			),
			array(
				'selector'     => '.wp-core-ui .button-primary',
				'declarations' => array(
					'background'   => 'button-background',
					'border-color' => 'button-border-color',
					'box-shadow'   => 'button-shadow',
					'text-shadow'  => 'button-text-shadow',
					'color'        => 'button-color',
					'width'        => 'button-width',
				),
			),
			array(
				'selector'     => '.login #backtoblog a,.login #nav a',
				'declarations' => array(
					'color' => 'link-color',
				),
			),
			array(
				'selector'     => '.login #backtoblog a:hover,.login #nav a:hover,.login h1 a:hover',
				'declarations' => array(
					'color' => 'link-color-hover',
				),
			),
			array(
				'selector'     => '#login form .forgetmenot',
				'declarations' => array(
					'display' => 'hide-rememberme',
				),
			),
		);
	}

	/**
	 * Create the CSS string for output.
	 *
	 * @return string
	 */
	public function create_css(): string {
		if ( empty( $this->options ) ) {
			return '';
		}

		$css = '';

		foreach ( $this->get_css_rules() as $rule ) {
			$css .= $this->create_css_lines( $rule['selector'], $rule['declarations'] );
		}

		return $css . $this->background_filter_css();
	}

	/**
	 * Build the CSS filter (blur + brightness) applied to the background image.
	 *
	 * Mirrored by backgroundFilter() in clc-preview.js.
	 *
	 * @return string
	 */
	private function background_filter_css(): string {
		$blur       = absint( $this->options['background-blur'] ?? 0 );
		$brightness = absint( $this->options['background-brightness'] ?? 100 );

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
	 * Build a CSS rule block for a selector from the given declarations.
	 *
	 * @param string                $selector     CSS selector.
	 * @param array<string, string> $declarations CSS property => option key.
	 *
	 * @return string
	 */
	private function create_css_lines( string $selector, array $declarations ): string {
		$lines = '';

		foreach ( $declarations as $property => $option ) {
			if ( empty( $this->options[ $option ] ) ) {
				continue;
			}

			/*
			 * Escaped with clc_escape_css_value(), not esc_attr(): HTML
			 * entities are not decoded inside a <style> element, so
			 * esc_attr() would corrupt legitimate values (quoted font
			 * names became &#039;) while blocking nothing.
			 */
			$value = clc_escape_css_value( $this->css_value( $property, $option, $this->options[ $option ] ) );

			if ( '' !== $value ) {
				$lines .= $property . ':' . $value . ";\n";
			}
		}

		if ( '' === $lines ) {
			return '';
		}

		return $selector . "{\n" . $lines . "}\n";
	}

	/**
	 * Turn a saved option value into a CSS value for the given property.
	 *
	 * Mirrored by cssValue() in clc-preview.js.
	 *
	 * @param string $property CSS property name.
	 * @param string $option   Option key the value came from.
	 * @param mixed  $value    Saved option value.
	 *
	 * @return string
	 */
	private function css_value( string $property, string $option, $value ): string {
		switch ( $property ) {
			case 'background-image':
				// Quote the URL and drop characters that could close url() early.
				return 'url("' . str_replace( array( '"', "'", '(', ')' ), '', clc_stringify( $value ) ) . '")';

			case 'width':
			case 'max-width':
			case 'height':
			case 'min-height':
			case 'font-size':
				// Append px only to bare numbers, so values that already carry a
				// unit or keyword (e.g. 100%, auto) are passed through unchanged.
				$value = clc_stringify( $value );

				return is_numeric( $value ) ? $value . 'px' : $value;

			case 'display':
				// The logo used to be three toggles and is now one select.
				if ( 'logo-settings' === $option ) {
					return 'hide-logo' === $value ? 'none' : 'block';
				}

				return 'none';
		}

		return clc_stringify( $value );
	}

	/**
	 * Filter the login page body classes based on the saved layout options.
	 *
	 * @param array<int, string> $classes Existing body classes.
	 * @return array<int, string> Modified body classes.
	 */
	public function body_class( $classes ) {
		$classes = (array) $classes;

		// Compare as integers: the setting is saved as a string, but older
		// migrations stored the number 2.
		if ( 2 === (int) $this->options['columns'] ) {
			$classes[] = 'ml-half-screen';
			$classes[] = 'ml-login-align-' . sanitize_html_class( clc_stringify( $this->options['form-column-align'] ) );
		}

		$classes[] = 'ml-login-vertical-align-' . sanitize_html_class( clc_stringify( $this->options['form-vertical-align'] ) );
		$classes[] = 'ml-login-horizontal-align-' . sanitize_html_class( clc_stringify( $this->options['form-horizontal-align'] ) );

		if ( 'show-text-only' === $this->options['logo-settings'] ) {
			$classes[] = 'clc-text-logo';
		} elseif ( 'use-both' === $this->options['logo-settings'] ) {
			$classes[] = 'clc-text-logo';
			$classes[] = 'clc-both-logo';
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
		$custom = clc_stringify( $this->options['logo-url'] );

		return '' !== $custom ? esc_url( $custom ) : $url;
	}

	/**
	 * Filter the login logo link title/text.
	 *
	 * @param string $title Default logo title.
	 * @return string Custom or default logo title.
	 */
	public function logo_title( $title ) {
		$custom = $this->options['logo-title'];

		// Not customised: keep core's own (translated) text.
		if ( ! is_scalar( $custom ) || self::DEFAULT_LOGO_TITLE === $custom ) {
			return $title;
		}

		return wp_kses_post( (string) $custom );
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
		$in_preview = is_customize_preview();
		$css        = $this->create_css();
		$columns    = '';
		$logo_css   = '';

		// The preview needs these rules up front so switching layout or logo
		// mode live works without a refresh; both are scoped by body class.
		if ( $in_preview || 2 === (int) $this->options['columns'] ) {
			$widths = clc_sanitize_columns_width( $this->options['columns-width'] );

			$left_width  = ( 100 / 12 ) * $widths['left'];
			$right_width = ( 100 / 12 ) * $widths['right'];

			$columns .= '.ml-half-screen.ml-login-align-3 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-1 .ml-container .ml-form-container{ width:' . $left_width . '%; }';
			$columns .= '.ml-half-screen.ml-login-align-4 .ml-container .ml-extra-div,.ml-half-screen.ml-login-align-2 .ml-container .ml-form-container{ flex-basis:' . $left_width . '%; }';

			$columns .= '.ml-half-screen.ml-login-align-3 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-1 .ml-container .ml-extra-div{ width:' . $right_width . '%; }';
			$columns .= '.ml-half-screen.ml-login-align-4 .ml-container .ml-form-container,.ml-half-screen.ml-login-align-2 .ml-container .ml-extra-div{ flex-basis:' . $right_width . '%; }';
		}

		if ( $in_preview || 'use-both' === $this->options['logo-settings'] ) {
			$logo_width  = absint( $this->options['logo-width'] );
			$logo_height = absint( $this->options['logo-height'] );

			$background_size  = ( $logo_width && $logo_height ) ? $logo_width . 'px ' . $logo_height . 'px' : '20px 20px';
			$background_image = clc_stringify( $this->options['custom-logo'] );

			if ( '' === $background_image ) {
				$background_image = admin_url( 'images/wordpress-logo.svg' );
			}

			$logo_css = '.login.clc-both-logo h1 a{width:100%;height:100%;text-indent:unset;background-position:top center !important;padding-top:' . ( 30 + $logo_height ) . 'px;background-size:' . $background_size . ';margin-top:-' . ( 15 + $logo_height ) . 'px;position:relative;background-image:url(' . esc_url( $background_image ) . ')}';
		}

		/*
		 * The static base stylesheet is enqueued as a real file by
		 * enqueue_login_styles(); only the settings-derived rules are inlined
		 * here. The logo block is printed first so a custom logo in #clc-style
		 * (which the preview rewrites live) overrides its fallback image.
		 */
		echo '<style id="clc-logo-style">' . $logo_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from absint() dimensions and esc_url() logo path.
		echo '<style id="clc-style">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from sanitized option values, escaped per declaration in create_css_lines().
		echo '<style id="clc-columns-style">' . $columns . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from integer column widths.
		echo '<style id="clc-custom-css">' . clc_sanitize_css( $this->options['custom-css'] ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Passed through clc_sanitize_css(), which strips tags and dangerous directives.
	}

	/**
	 * Output the opening markup for the two-column layout wrapper.
	 *
	 * @return void
	 */
	public function add_extra_div() {
		$background = clc_stringify( $this->options['custom-background'] );
		$link       = clc_stringify( $this->options['custom-background-link'] );

		echo '<div class="ml-container"><div class="ml-extra-div">';

		if ( '' !== $background && '' !== $link ) {
			printf(
				'<a class="clc-custom-background-link" href="%1$s" aria-label="%2$s"></a>',
				esc_url( $link ),
				esc_attr__( 'Background image link', 'colorlib-login-customizer' )
			);
		}

		echo '</div><div class="ml-form-container">';
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
					echo ' <span class="clc-footer-sep" aria-hidden="true">&middot;</span> ';
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
	 * Register a core login string to be replaced by a setting.
	 *
	 * Whether a string needs escaping depends on how core prints it — see the
	 * "gettext Filter Escaping" table in CLAUDE.md before changing one.
	 *
	 * @param string $text   Core source string (domain `default`).
	 * @param string $option Option key holding the replacement.
	 * @param bool   $escape True when core prints the string unescaped.
	 * @param string $empty  What an empty setting renders: 'blank', 'hide' or 'keep'.
	 * @return void
	 */
	private function add_login_text( string $text, string $option, bool $escape, string $empty = 'blank' ): void {
		$this->login_texts[ $text ] = array(
			'option' => $option,
			'escape' => $escape,
			'empty'  => $empty,
		);
	}

	/**
	 * Register the gettext filters and the strings shared by every login action.
	 *
	 * @return void
	 */
	public function check_general_texts() {
		$this->add_login_text( 'Lost your password?', 'lost-password-text', true );

		add_filter( 'gettext', array( $this, 'filter_login_text' ), 99, 3 );
		add_filter( 'gettext_with_context', array( $this, 'change_back_to_text' ), 99, 4 );
	}

	/**
	 * Register the login-page texts.
	 *
	 * @return void
	 */
	public function check_login_texts() {
		$this->add_login_text( 'Username or Email Address', 'username-label', true, 'hide' );
		$this->add_login_text( 'Password', 'password-label', true, 'hide' );
		$this->add_login_text( 'Remember Me', 'rememberme-label', false );
		$this->add_login_text( 'Log In', 'login-label', false, 'keep' );
		$this->add_login_text( 'Register', 'register-link-label', true );
	}

	/**
	 * Register the registration-page texts.
	 *
	 * @return void
	 */
	public function check_register_texts() {
		$this->add_login_text( 'Username', 'register-username-label', true, 'hide' );
		$this->add_login_text( 'Email', 'register-email-label', true, 'hide' );
		$this->add_login_text( 'Register', 'register-button-label', false, 'keep' );
		$this->add_login_text( 'Registration confirmation will be emailed to you.', 'register-confirmation-email', true );
		$this->add_login_text( 'Log in', 'login-link-label', true );
	}

	/**
	 * Register the lost-password-page texts.
	 *
	 * @return void
	 */
	public function check_lostpasswords_texts() {
		$this->add_login_text( 'Username or Email Address', 'lostpassword-username-label', true, 'hide' );
		$this->add_login_text( 'Get New Password', 'lostpassword-button-label', false, 'keep' );
		$this->add_login_text( 'Register', 'register-link-label', true );
		$this->add_login_text( 'Log in', 'login-link-label', true );
	}

	/**
	 * Replace a core login string with the customised text.
	 *
	 * One callback serves every string registered with add_login_text(). Only
	 * core's `default` text domain is touched: other plugins on the login page
	 * (two-factor, CAPTCHA, WooCommerce, ...) translate their own "Password" or
	 * "Email" strings and must keep them.
	 *
	 * @param string $translated_text The translated text.
	 * @param string $text            The source text.
	 * @param string $domain          The text domain.
	 * @return string
	 */
	public function filter_login_text( $translated_text, $text = '', $domain = 'default' ) {
		if ( 'default' !== $domain || ! is_string( $text ) || ! isset( $this->login_texts[ $text ] ) ) {
			return $translated_text;
		}

		$entry = $this->login_texts[ $text ];
		$value = $this->options[ $entry['option'] ] ?? '';
		$label = is_scalar( $value ) ? (string) $value : '';

		// Still the English default: keep core's translation.
		if ( $label === $text ) {
			return $translated_text;
		}

		if ( '' === $label ) {
			if ( 'keep' === $entry['empty'] ) {
				return $translated_text;
			}

			if ( 'hide' === $entry['empty'] ) {
				// Visually removed, but the field keeps its accessible name.
				return '<span class="screen-reader-text">' . esc_html( $translated_text ) . '</span>';
			}

			return '';
		}

		return $entry['escape'] ? esc_html( $label ) : $label;
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
	public function change_back_to_text( $translated_text, $text, $context, $domain = 'default' ) {
		/*
		 * WordPress 5.7 renamed this string from "Back to %s" to "Go to %s"
		 * (and added the `login_site_html_link` filter). Match both so the
		 * setting keeps working on current and older installs.
		 */
		$patterns = array( '&larr; Back to %s', '&larr; Go to %s' );

		// Check if this is our text.
		if ( 'default' !== $domain || ! in_array( $text, $patterns, true ) ) {
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
}

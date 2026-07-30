<?php
/**
 * End-to-end tests for login page CSS output and the gettext filters.
 *
 * These drive the real plugin classes against the WordPress shims in
 * tests/wp-stubs.php, so they exercise hook registration and the actual
 * printed stylesheet rather than the sanitizers in isolation.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * @covers Colorlib_Login_Customizer_CSS_Customization
 */
class CssOutputTest extends TestCase {

	/**
	 * Load the plugin classes once.
	 */
	public static function setUpBeforeClass(): void {
		require_once dirname( __DIR__ ) . '/includes/class-colorlib-login-customizer.php';
		require_once dirname( __DIR__ ) . '/includes/lib/class-colorlib-login-customizer-css-customization.php';
	}

	/**
	 * Reset shim state before each test.
	 */
	protected function setUp(): void {
		clc_test_reset();
	}

	/**
	 * Build a CSS_Customization instance with the given saved options.
	 *
	 * @param array<string, mixed> $options Saved plugin options.
	 * @return Colorlib_Login_Customizer_CSS_Customization
	 */
	private function make( array $options = array() ): Colorlib_Login_Customizer_CSS_Customization {
		update_option( 'clc-options', $options );

		// Prime the singleton so key_name/defaults are available.
		Colorlib_Login_Customizer::instance( dirname( __DIR__ ) . '/colorlib-login-customizer.php', '2.3.0' );

		return new Colorlib_Login_Customizer_CSS_Customization();
	}

	/**
	 * Build an instance that has been through login_init, as it always has
	 * by the time any of the gettext filters can fire.
	 *
	 * @param array<string, mixed> $options Saved plugin options.
	 * @return Colorlib_Login_Customizer_CSS_Customization
	 */
	private function makeBooted( array $options = array() ): Colorlib_Login_Customizer_CSS_Customization {
		$css = $this->make( $options );
		$css->init_login();

		return $css;
	}

	/**
	 * Capture the inline CSS printed on login_head.
	 *
	 * @param array<string, mixed> $options Saved plugin options.
	 * @return string
	 */
	private function render( array $options = array() ): string {
		$css = $this->make( $options );
		$css->init_login();

		ob_start();
		$css->generate_css();

		return (string) ob_get_clean();
	}

	/*
	 * ---------------------------------------------------------------------
	 * Hook scoping.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Constructing the object must not register any login-page filter.
	 *
	 * The object is built on `init` for every request. When the privacy
	 * filter was registered here it removed the privacy-policy link from the
	 * front end too.
	 */
	public function test_constructor_registers_no_login_filters(): void {
		$this->make( array( 'hide-privacy-link' => true ) );

		$this->assertFalse(
			has_filter( 'the_privacy_policy_link' ),
			'the_privacy_policy_link must not be filtered outside the login page'
		);
		$this->assertFalse( has_filter( 'login_body_class' ) );
		$this->assertFalse( has_action( 'login_head' ) );

		// Only the two entry points may be registered up front.
		$this->assertTrue( has_action( 'login_init' ) );
	}

	/**
	 * On the login page the privacy filter is registered when enabled.
	 */
	public function test_privacy_filter_applies_on_login_only_when_enabled(): void {
		$css = $this->make( array( 'hide-privacy-link' => true ) );
		$css->init_login();

		$this->assertTrue( has_filter( 'the_privacy_policy_link' ) );
		$this->assertSame( '', apply_filters( 'the_privacy_policy_link', '<a>Privacy</a>', '' ) );
	}

	/**
	 * With the toggle off the link is left alone.
	 */
	public function test_privacy_filter_absent_when_disabled(): void {
		$css = $this->make( array( 'hide-privacy-link' => false ) );
		$css->init_login();

		$this->assertFalse( has_filter( 'the_privacy_policy_link' ) );
	}

	/**
	 * The base stylesheet is enqueued as a cacheable file.
	 */
	public function test_base_stylesheet_is_enqueued_as_a_file(): void {
		$GLOBALS['clc_test_styles'] = array();

		$css = $this->make();
		$css->init_login();
		do_action( 'login_enqueue_scripts' );

		$this->assertArrayHasKey( 'colorlib-login-customizer-login', $GLOBALS['clc_test_styles'] );
		$this->assertStringEndsWith(
			'assets/css/clc-login.css',
			$GLOBALS['clc_test_styles']['colorlib-login-customizer-login']
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Printed CSS.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * The static base CSS is no longer inlined on every page view.
	 */
	public function test_base_css_is_not_inlined(): void {
		$out = $this->render();

		$this->assertStringNotContainsString( '.ml-container{', $out );
		$this->assertStringNotContainsString( 'ast-style-guide-wrapper', $out );
		$this->assertStringContainsString( 'id="clc-style"', $out );
	}

	/**
	 * Quoted values such as font stacks must survive verbatim; esc_attr()
	 * used to turn the apostrophes into &#039; inside the stylesheet.
	 */
	public function test_quoted_values_are_not_html_encoded(): void {
		$out = $this->render(
			array(
				'form-border' => '1px solid #ddd',
				'custom-css'  => "body{font-family:'Helvetica Neue',sans-serif}",
			)
		);

		$this->assertStringNotContainsString( '&#039;', $out );
		$this->assertStringNotContainsString( '&amp;', $out );
		$this->assertStringContainsString( '1px solid #ddd', $out );
		$this->assertStringContainsString( "'Helvetica Neue'", $out );
	}

	/**
	 * A hostile custom-CSS value cannot escape the style element.
	 */
	public function test_custom_css_cannot_break_out(): void {
		$out = $this->render(
			array(
				'custom-css' => '</style><script>alert(1)</script>@@importimport url(//evil.example/x.css);',
			)
		);

		$this->assertStringNotContainsString( '<script', $out );
		$this->assertStringNotContainsString( '</style><script', $out );
		$this->assertStringNotContainsStringIgnoringCase( '@import', $out );
	}

	/**
	 * Background image URLs are quoted so they cannot close url() early.
	 */
	public function test_background_image_url_is_quoted(): void {
		$out = $this->render( array( 'custom-background' => 'https://example.test/bg.jpg' ) );

		$this->assertStringContainsString( 'url("https://example.test/bg.jpg")', $out );
	}

	/**
	 * A value that survived from a legacy install still cannot inject a rule.
	 */
	public function test_legacy_unsanitized_value_cannot_inject_a_rule(): void {
		$out = $this->render( array( 'form-padding' => '10px}body{display:none' ) );

		$this->assertStringNotContainsString( 'body{display:none', $out );
	}

	/**
	 * Column widths are emitted from integer maths only.
	 */
	public function test_column_css_uses_integers(): void {
		$out = $this->render(
			array(
				'columns'       => '2',
				'columns-width' => array(
					'left'  => 8,
					'right' => 4,
				),
			)
		);

		$this->assertStringContainsString( 'id="clc-columns-style"', $out );
		$this->assertMatchesRegularExpression( '/width:66\.66\d*%/', $out );
	}

	/*
	 * ---------------------------------------------------------------------
	 * gettext filters: escaping must match the context WordPress prints in.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Labels that core prints with _e() are escaped by the plugin.
	 */
	public function test_labels_printed_raw_by_core_are_escaped(): void {
		$css = $this->makeBooted( array( 'username-label' => 'User & Email' ) );

		$this->assertSame(
			'User &amp; Email',
			$css->change_username_label( 'Username or Email Address', 'Username or Email Address', 'default' )
		);
	}

	/**
	 * Values core prints with esc_attr_e()/esc_html_e() must come back raw,
	 * otherwise "Sign In & Go" renders as "Sign In &amp; Go".
	 *
	 * @dataProvider preEscapedByCoreProvider
	 *
	 * @param string $method  Filter method.
	 * @param string $option  Option key.
	 * @param string $source  Source string core translates.
	 */
	public function test_values_pre_escaped_by_core_are_not_double_encoded( string $method, string $option, string $source ): void {
		$css = $this->makeBooted( array( $option => "Tom & Jerry's" ) );

		$this->assertSame(
			"Tom & Jerry's",
			$css->{$method}( $source, $source, 'default' ),
			$method . '() must not escape a value core already escapes'
		);
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public function preEscapedByCoreProvider(): array {
		return array(
			'Log In button'          => array( 'change_login_label', 'login-label', 'Log In' ),
			'Remember Me label'      => array( 'change_rememberme_label', 'rememberme-label', 'Remember Me' ),
			'Register button'        => array( 'change_register_register_label', 'register-button-label', 'Register' ),
			'Get New Password'       => array( 'change_lostpasswords_button_label', 'lostpassword-button-label', 'Get New Password' ),
		);
	}

	/**
	 * WordPress 5.7 renamed the site link string; both spellings must match.
	 */
	public function test_back_to_text_matches_modern_and_legacy_strings(): void {
		$css = $this->makeBooted( array( 'back-to-text' => 'Return to' ) );

		$this->assertSame(
			'&larr; Return to %s',
			$css->change_back_to_text( '&larr; Go to %s', '&larr; Go to %s', 'site', 'default' ),
			'the modern "Go to %s" string must be matched'
		);

		$this->assertSame(
			'&larr; Return to %s',
			$css->change_back_to_text( '&larr; Back to %s', '&larr; Back to %s', 'site', 'default' ),
			'the legacy "Back to %s" string must still be matched'
		);
	}

	/**
	 * Regression: the result is handed to sprintf() with exactly one argument,
	 * so it must carry exactly one %s. A second placeholder raises
	 * ArgumentCountError on PHP 8 and takes down the whole login page.
	 *
	 * @dataProvider backToTextProvider
	 *
	 * @param string $stored Stored option value.
	 */
	public function test_back_to_text_always_yields_one_placeholder( string $stored ): void {
		$css = $this->makeBooted( array( 'back-to-text' => $stored ) );

		foreach ( array( '&larr; Go to %s', '&larr; Back to %s' ) as $source ) {
			$result = $css->change_back_to_text( $source, $source, 'site', 'default' );

			$this->assertSame(
				1,
				substr_count( str_replace( '%%', '', $result ), '%s' ),
				sprintf( 'stored "%s" produced %s placeholders', $stored, substr_count( $result, '%s' ) )
			);

			// The real call site: this must not throw.
			$this->assertNotSame( '', sprintf( $result, 'Example Site' ) );
		}
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function backToTextProvider(): array {
		return array(
			'bare phrase'          => array( 'Back to site' ),
			'legacy full pattern'  => array( '&larr; Back to %s' ),
			'modern full pattern'  => array( '&larr; Go to %s' ),
			'arrow only'           => array( '&larr; Home' ),
			'unicode arrow'        => array( "\u{2190} Home" ),
			'stray placeholder'    => array( 'Back %s to %s site' ),
			'percent sign'         => array( '100% back' ),
			'whitespace'           => array( '   ' ),
			'empty'                => array( '' ),
		);
	}

	/**
	 * An install that never saved the setting keeps core's own wording.
	 */
	public function test_back_to_text_untouched_when_not_customised(): void {
		$css = $this->makeBooted();

		$this->assertSame(
			'&larr; Go to %s',
			$css->change_back_to_text( '&larr; Go to %s', '&larr; Go to %s', 'site', 'default' )
		);
	}

	/**
	 * Unrelated translations are passed straight through.
	 */
	public function test_unrelated_strings_are_untouched(): void {
		$css = $this->makeBooted( array( 'username-label' => 'Custom' ) );

		$this->assertSame(
			'Some other string',
			$css->change_username_label( 'Some other string', 'Some other string', 'default' )
		);
	}

	/**
	 * The login page title cannot break out of the <title> element, which
	 * core echoes unescaped.
	 */
	public function test_login_title_cannot_break_out(): void {
		$css = $this->makeBooted( array( 'login-page-title' => '</title><script>alert(1)</script>Hi' ) );

		$title = $css->login_page_title( 'Log In' );

		$this->assertStringNotContainsString( '</title>', $title );
		$this->assertStringNotContainsString( '<script', $title );
	}

	/**
	 * Every setting registered in the Customizer needs an entry in
	 * get_defaults(), otherwise wp_parse_args() cannot supply a fallback and
	 * sanitize_imported_options() silently drops the key on import.
	 *
	 * @dataProvider registeredSettingProvider
	 *
	 * @param string $key Setting key that must have a default.
	 */
	public function test_registered_settings_have_defaults( string $key ): void {
		$defaults = Colorlib_Login_Customizer::instance()->get_defaults();

		$this->assertArrayHasKey(
			$key,
			$defaults,
			$key . ' is registered in the Customizer but missing from get_defaults()'
		);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function registeredSettingProvider(): array {
		return array(
			// Regressions: these three were missing and were dropped on import.
			'logo-title'            => array( 'logo-title' ),
			'login-page-title'      => array( 'login-page-title' ),
			'form-horizontal-align' => array( 'form-horizontal-align' ),
			// Spot-check of keys read directly by the CSS class.
			'back-to-text'          => array( 'back-to-text' ),
			'custom-css'            => array( 'custom-css' ),
			'hide-privacy-link'     => array( 'hide-privacy-link' ),
			'columns-width'         => array( 'columns-width' ),
		);
	}

	/**
	 * Core's own entities in the title are preserved, not double-encoded.
	 */
	public function test_login_title_preserves_core_entities(): void {
		$css = $this->makeBooted();

		$this->assertSame(
			'Log In &lsaquo; Example Site &#8212; WordPress',
			$css->login_page_title( 'Log In &lsaquo; Example Site &#8212; WordPress' )
		);
	}
}

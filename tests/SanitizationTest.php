<?php
/**
 * Tests for the sanitization layer.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * @covers ::clc_sanitize_color
 * @covers ::clc_sanitize_dimension
 * @covers ::clc_sanitize_css
 * @covers ::clc_sanitize_css_value
 * @covers ::clc_escape_css_value
 */
class SanitizationTest extends TestCase {

	/*
	 * ---------------------------------------------------------------------
	 * Regression: nested-token bypass.
	 *
	 * The original sanitizer removed dangerous tokens in a single pass, so a
	 * nested payload such as "java" + "javascript:" + "script:" reassembled
	 * into a working token once the inner match was deleted. Every payload
	 * below was verified to survive the old implementation.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function nestedBypassProvider(): array {
		return array(
			'nested @import'      => array( '@@importimport url("//evil.example/x.css");', '@import' ),
			'nested javascript:'  => array( 'background:url(javajavascript:script:alert(1));', 'javascript:' ),
			'nested behavior:'    => array( 'behavibehavior:or:url(#default#time2);', 'behavior:' ),
			'nested expression('  => array( 'width:expresexpression(sion(alert(1));', 'expression(' ),
			'nested vbscript:'    => array( 'background:url(vbvbscript:script:x);', 'vbscript:' ),
			'nested data: url'    => array( 'background:url(dadata:ta:text/html,payload);', 'data:' ),
			'nested -moz-binding' => array( '-moz--moz-binding:binding:url(x.xml);', '-moz-binding:' ),
			'triple nested'       => array( '@@@importimportimport url(x);', '@import' ),
		);
	}

	/**
	 * @dataProvider nestedBypassProvider
	 *
	 * @param string $payload  Malicious CSS.
	 * @param string $forbidden Token that must not survive.
	 */
	public function test_nested_tokens_cannot_be_reassembled( string $payload, string $forbidden ): void {
		$this->assertStringNotContainsStringIgnoringCase(
			$forbidden,
			clc_sanitize_css( $payload ),
			'clc_sanitize_css() left a reassembled token in the output'
		);

		$this->assertStringNotContainsStringIgnoringCase(
			$forbidden,
			clc_escape_css_value( $payload ),
			'clc_escape_css_value() left a reassembled token in the output'
		);
	}

	/**
	 * A closing style tag must never survive, or custom CSS becomes XSS.
	 */
	public function test_style_element_cannot_be_closed(): void {
		$css = '</style><script>alert(1)</script>body{color:red}';
		$out = clc_sanitize_css( $css );

		$this->assertStringNotContainsStringIgnoringCase( '</style', $out );
		$this->assertStringNotContainsStringIgnoringCase( '<script', $out );
	}

	/**
	 * Legitimate CSS must still pass through untouched.
	 */
	public function test_valid_custom_css_is_preserved(): void {
		$css = 'body.login { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }';

		$this->assertSame( $css, clc_sanitize_css( $css ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Colors.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @return array<string, array{0: string}>
	 */
	public function validColorProvider(): array {
		return array(
			'hex 3'            => array( '#fff' ),
			'hex 4 (alpha)'    => array( '#fff8' ),
			'hex 6'            => array( '#0085ba' ),
			'hex 8 (alpha)'    => array( '#0085ba80' ),
			'rgb legacy'       => array( 'rgb(255, 107, 107)' ),
			'rgba legacy'      => array( 'rgba(255, 255, 255, 0.95)' ),
			'rgb modern space' => array( 'rgb(0 0 0 / 50%)' ),
			'hsl'              => array( 'hsl(210, 50%, 40%)' ),
			'hsl modern'       => array( 'hsl(210deg 50% 40% / 80%)' ),
			'var()'            => array( 'var(--brand-color)' ),
			'var() fallback'   => array( 'var(--brand-color, #fff)' ),
			'color-mix()'      => array( 'color-mix(in srgb, #fff 40%, #000)' ),
		);
	}

	/**
	 * @dataProvider validColorProvider
	 *
	 * @param string $color Color to accept.
	 */
	public function test_valid_colors_are_accepted( string $color ): void {
		$this->assertSame( $color, clc_sanitize_color( $color ) );
	}

	/**
	 * Named colors and CSS-wide keywords normalise to lower case.
	 */
	public function test_keywords_and_named_colors(): void {
		$this->assertSame( 'transparent', clc_sanitize_color( 'transparent' ) );
		$this->assertSame( 'currentcolor', clc_sanitize_color( 'currentColor' ) );
		$this->assertSame( 'rebeccapurple', clc_sanitize_color( 'rebeccapurple' ) );
		$this->assertSame( 'inherit', clc_sanitize_color( 'inherit' ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function invalidColorProvider(): array {
		return array(
			'declaration breakout' => array( 'red; color: blue' ),
			'rule breakout'        => array( 'red}body{display:none' ),
			'style breakout'       => array( 'red</style><script>alert(1)</script>' ),
			'url()'                => array( 'url(https://evil.example/x.png)' ),
			'expression()'         => array( 'expression(alert(1))' ),
			'image-set()'          => array( 'image-set("x.png" 1x)' ),
			'backslash escape'     => array( '\\3c /style\\3e ' ),
			'malformed hex'        => array( '#12345' ),
			'garbage'              => array( '!!!' ),
		);
	}

	/**
	 * @dataProvider invalidColorProvider
	 *
	 * @param string $color Color to reject.
	 */
	public function test_invalid_colors_are_rejected( string $color ): void {
		$this->assertSame( '', clc_sanitize_color( $color ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Dimensions.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @return array<string, array{0: string}>
	 */
	public function validDimensionProvider(): array {
		return array(
			'bare number'    => array( '320' ),
			'px'             => array( '48px' ),
			'negative'       => array( '-15px' ),
			'decimal'        => array( '1.5rem' ),
			'leading dot'    => array( '.5em' ),
			'percent'        => array( '100%' ),
			'viewport'       => array( '100vh' ),
			'dynamic vh'     => array( '100dvh' ),
			'small vh'       => array( '50svh' ),
			'ch'             => array( '20ch' ),
			'calc()'         => array( 'calc(100% - 20px)' ),
			'clamp()'        => array( 'clamp(1rem, 2vw, 3rem)' ),
			'min()'          => array( 'min(100%, 400px)' ),
			'var()'          => array( 'var(--form-width)' ),
		);
	}

	/**
	 * @dataProvider validDimensionProvider
	 *
	 * @param string $value Dimension to accept.
	 */
	public function test_valid_dimensions_are_accepted( string $value ): void {
		$this->assertSame( $value, clc_sanitize_dimension( $value ) );
	}

	/**
	 * Sizing keywords are accepted and lower-cased.
	 */
	public function test_dimension_keywords(): void {
		$this->assertSame( 'auto', clc_sanitize_dimension( 'AUTO' ) );
		$this->assertSame( 'fit-content', clc_sanitize_dimension( 'fit-content' ) );
		$this->assertSame( 'unset', clc_sanitize_dimension( 'unset' ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function invalidDimensionProvider(): array {
		return array(
			'declaration breakout' => array( '10px; color: red' ),
			'rule breakout'        => array( '10px}body{display:none' ),
			'unbalanced parens'    => array( 'calc((100% - 20px)' ),
			'closing paren first'  => array( 'calc)100%(' ),
			'url()'                => array( 'url(x.png)' ),
			'unknown unit'         => array( '10furlongs' ),
			'garbage'              => array( 'drop table' ),
		);
	}

	/**
	 * @dataProvider invalidDimensionProvider
	 *
	 * @param string $value Dimension to reject.
	 */
	public function test_invalid_dimensions_are_rejected( string $value ): void {
		$this->assertSame( '', clc_sanitize_dimension( $value ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Generic CSS values (borders, shadows, padding...).
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @return array<string, array{0: string}>
	 */
	public function validCssValueProvider(): array {
		return array(
			'shadow'        => array( '0 1px 3px rgba(0,0,0,.13)' ),
			'multi shadow'  => array( '0 -1px 1px #006799, 1px 0 1px #006799' ),
			'border'        => array( '1px solid #ddd' ),
			'border words'  => array( '2px dotted black' ),
			'padding pair'  => array( '26px 24px' ),
			'padding quad'  => array( '26px 24px 46px 30px' ),
			'none'          => array( '0 none' ),
		);
	}

	/**
	 * @dataProvider validCssValueProvider
	 *
	 * @param string $value Value to accept.
	 */
	public function test_valid_css_values_are_accepted( string $value ): void {
		$this->assertSame( $value, clc_sanitize_css_value( $value ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public function invalidCssValueProvider(): array {
		return array(
			'declaration breakout' => array( '1px solid red; background: url(x)' ),
			'rule breakout'        => array( '1px solid red}body{display:none' ),
			'unbalanced parens'    => array( 'rgba(0,0,0,.5' ),
			'url()'                => array( 'url(https://evil.example/x.png)' ),
			'expression()'         => array( 'expression(alert(1))' ),
		);
	}

	/**
	 * @dataProvider invalidCssValueProvider
	 *
	 * @param string $value Value to reject.
	 */
	public function test_invalid_css_values_are_rejected( string $value ): void {
		$this->assertSame( '', clc_sanitize_css_value( $value ) );
	}

	/**
	 * The print-time escaper strips structural characters but keeps quotes,
	 * so font stacks survive — this is what esc_attr() used to break.
	 */
	public function test_escape_css_value_preserves_quotes(): void {
		$this->assertSame(
			"'Helvetica Neue', sans-serif",
			clc_escape_css_value( "'Helvetica Neue', sans-serif" )
		);

		$this->assertSame( 'abcdef', clc_escape_css_value( 'a<b>c{d}e;f' ) );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Images, URLs, columns.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Only permitted image extensions and protocols pass.
	 */
	public function test_image_sanitization(): void {
		$this->assertSame( 'https://example.com/a.png', clc_sanitize_image( 'https://example.com/a.png' ) );
		$this->assertSame( 'https://example.com/a.avif', clc_sanitize_image( 'https://example.com/a.avif' ) );
		$this->assertSame( 'https://example.com/a.webp', clc_sanitize_image( 'https://example.com/a.webp' ) );

		$this->assertSame( '', clc_sanitize_image( 'https://example.com/a.php' ) );
		$this->assertSame( '', clc_sanitize_image( 'https://example.com/noext' ) );
		$this->assertSame( '', clc_sanitize_image( 'javascript:alert(1)' ) );
		$this->assertSame( '', clc_sanitize_image( 'data:text/html,<svg onload=alert(1)>' ) );
	}

	/**
	 * URL sanitization rejects script protocols.
	 */
	public function test_url_sanitization(): void {
		$this->assertSame( 'https://example.com/', clc_sanitize_url( 'https://example.com/' ) );
		$this->assertSame( 'mailto:a@example.com', clc_sanitize_url( 'mailto:a@example.com' ) );
		$this->assertSame( '', clc_sanitize_url( 'javascript:alert(1)' ) );
		$this->assertSame( '', clc_sanitize_url( '' ) );
	}

	/**
	 * Column widths are clamped into the 1..11 grid range.
	 */
	public function test_columns_width_is_clamped(): void {
		$this->assertSame(
			array(
				'left'  => 8,
				'right' => 4,
			),
			clc_sanitize_columns_width(
				array(
					'left'  => 8,
					'right' => 4,
				)
			)
		);

		$this->assertSame(
			array(
				'left'  => 11,
				'right' => 1,
			),
			clc_sanitize_columns_width(
				array(
					'left'  => 999,
					'right' => 0,
				)
			)
		);

		$this->assertSame(
			array(
				'left'  => 6,
				'right' => 6,
			),
			clc_sanitize_columns_width( 'not-an-array' )
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Type safety.
	 *
	 * These run under strict_types; before the `mixed` signatures a null or
	 * array setting value raised a TypeError instead of sanitizing.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * @return array<string, array{0: callable-string}>
	 */
	public function sanitizerProvider(): array {
		return array(
			'color'     => array( 'clc_sanitize_color' ),
			'dimension' => array( 'clc_sanitize_dimension' ),
			'css_value' => array( 'clc_sanitize_css_value' ),
			'css'       => array( 'clc_sanitize_css' ),
			'url'       => array( 'clc_sanitize_url' ),
			'image'     => array( 'clc_sanitize_image' ),
			'text'      => array( 'clc_sanitize_text' ),
			'textarea'  => array( 'clc_sanitize_textarea' ),
		);
	}

	/**
	 * @dataProvider sanitizerProvider
	 *
	 * @param string $fn Sanitizer under test.
	 */
	public function test_sanitizers_tolerate_non_string_input( string $fn ): void {
		foreach ( array( null, array(), true, false, 0, 1.5, new stdClass() ) as $input ) {
			$result = $fn( $input );
			$this->assertIsString( $result, $fn . '() must always return a string' );
		}
	}

	/**
	 * Booleans map cleanly for toggle settings.
	 */
	public function test_checkbox_sanitization(): void {
		$this->assertTrue( clc_sanitize_checkbox( '1' ) );
		$this->assertTrue( clc_sanitize_checkbox( true ) );
		$this->assertFalse( clc_sanitize_checkbox( '' ) );
		$this->assertFalse( clc_sanitize_checkbox( 0 ) );
		$this->assertFalse( clc_sanitize_checkbox( null ) );
	}

	/**
	 * Select values fall back to the supplied default when not allowed.
	 */
	public function test_select_sanitization(): void {
		$choices = array(
			'top'        => 'Top',
			'settings'   => 'Settings',
			'appearance' => 'Appearance',
		);

		$this->assertSame( 'settings', clc_sanitize_select( 'settings', $choices, 'top' ) );
		$this->assertSame( 'top', clc_sanitize_select( 'evil', $choices, 'top' ) );
		$this->assertSame( 'top', clc_sanitize_select( null, $choices, 'top' ) );
	}

	/**
	 * Parenthesis balancing helper.
	 */
	public function test_parens_balanced_helper(): void {
		$this->assertTrue( clc_css_parens_balanced( 'calc(100% - (2 * 10px))' ) );
		$this->assertFalse( clc_css_parens_balanced( 'calc(100%' ) );
		$this->assertFalse( clc_css_parens_balanced( 'calc)100%(' ) );
	}
}

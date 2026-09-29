<?php
/**
 * Tests for settings import and Customizer choice validation.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Customize_Setting' ) ) {
	/**
	 * Just enough of WP_Customize_Setting for clc_sanitize_choice().
	 */
	class WP_Customize_Setting {
		/**
		 * Manager.
		 *
		 * @var object
		 */
		public $manager;

		/**
		 * Setting ID.
		 *
		 * @var string
		 */
		public $id;

		/**
		 * Default value.
		 *
		 * @var mixed
		 */
		public $default;

		/**
		 * Build a setting whose control offers the given choices.
		 *
		 * @param string $id      Setting ID.
		 * @param mixed  $default Default value.
		 * @param array  $choices Control choices.
		 */
		public function __construct( string $id, $default, array $choices ) {
			$this->id      = $id;
			$this->default = $default;
			$this->manager = new class( $choices ) {
				/**
				 * Control choices.
				 *
				 * @var array
				 */
				private $choices;

				/**
				 * @param array $choices Control choices.
				 */
				public function __construct( array $choices ) {
					$this->choices = $choices;
				}

				/**
				 * @param string $id Control ID.
				 * @return object
				 */
				public function get_control( $id ) {
					return (object) array( 'choices' => $this->choices );
				}
			};
		}
	}
}

/**
 * @covers Colorlib_Login_Customizer_Settings::sanitize_imported_options
 * @covers ::clc_sanitize_choice
 */
class SettingsTest extends TestCase {

	/**
	 * Load the plugin classes once.
	 */
	public static function setUpBeforeClass(): void {
		require_once dirname( __DIR__ ) . '/includes/class-colorlib-login-customizer.php';
		require_once dirname( __DIR__ ) . '/includes/lib/class-colorlib-login-customizer-settings.php';
	}

	/**
	 * The settings object, wired to the main plugin singleton.
	 *
	 * @return Colorlib_Login_Customizer_Settings
	 */
	private function settings(): Colorlib_Login_Customizer_Settings {
		$plugin = Colorlib_Login_Customizer::instance( dirname( __DIR__ ) . '/colorlib-login-customizer.php', '2.3.0' );

		return Colorlib_Login_Customizer_Settings::instance( $plugin );
	}

	/**
	 * Labels are plain text: a semicolon is fine. They used to go through the
	 * CSS value sanitizer, which emptied any label containing one.
	 */
	public function test_import_keeps_text_labels_intact(): void {
		$clean = $this->settings()->sanitize_imported_options(
			array(
				'username-label' => 'Email; or username',
				'logo-title'     => '<b>Acme</b> Portal',
			)
		);

		$this->assertSame( 'Email; or username', $clean['username-label'] );
		$this->assertSame( 'Acme Portal', $clean['logo-title'] );
	}

	/**
	 * Only columns-width may be an array; other keys with array values are
	 * dropped instead of being turned into a column-width array.
	 */
	public function test_import_drops_arrays_for_scalar_settings(): void {
		$clean = $this->settings()->sanitize_imported_options(
			array(
				'custom-css'    => array( 'body{}' ),
				'columns-width' => array(
					'left'  => 8,
					'right' => 4,
				),
			)
		);

		$this->assertArrayNotHasKey( 'custom-css', $clean );
		$this->assertSame(
			array(
				'left'  => 8,
				'right' => 4,
			),
			$clean['columns-width']
		);
	}

	/**
	 * Unknown keys and invalid choices never reach the saved options.
	 */
	public function test_import_validates_keys_and_choices(): void {
		$clean = $this->settings()->sanitize_imported_options(
			array(
				'not-a-setting' => 'x',
				'columns'       => 2,
				'logo-settings' => 'evil" onload="x',
				'templates'     => '../../etc',
			)
		);

		$this->assertArrayNotHasKey( 'not-a-setting', $clean );
		$this->assertSame( '2', $clean['columns'] );
		$this->assertSame( 'show-image-only', $clean['logo-settings'] );
		$this->assertSame( 'default', $clean['templates'] );
	}

	/**
	 * Each kind of value gets the sanitizer the Customizer would use.
	 */
	public function test_import_sanitizes_by_setting_kind(): void {
		$clean = $this->settings()->sanitize_imported_options(
			array(
				'custom-logo'           => 'https://example.test/logo.php',
				'custom-background'     => 'https://example.test/bg.jpg',
				'button-background'     => 'red;}body{display:none',
				'form-width'            => '400px',
				'hide-rememberme'       => '1',
				'footer-link-1-url'     => 'javascript:alert(1)',
				'form-field-width'      => '100%',
				'background-brightness' => '120',
			)
		);

		$this->assertSame( '', $clean['custom-logo'] );
		$this->assertSame( 'https://example.test/bg.jpg', $clean['custom-background'] );
		$this->assertSame( '', $clean['button-background'] );
		$this->assertSame( 400, $clean['form-width'] );
		$this->assertTrue( $clean['hide-rememberme'] );
		$this->assertSame( '', $clean['footer-link-1-url'] );
		$this->assertSame( '100%', $clean['form-field-width'] );
		$this->assertSame( 120, $clean['background-brightness'] );
	}

	/**
	 * Every default key has a sanitizer that keeps its default intact, so
	 * exporting a fresh install and importing it again changes nothing.
	 */
	public function test_import_round_trips_the_defaults(): void {
		$defaults = Colorlib_Login_Customizer::instance()->get_defaults();
		$clean    = $this->settings()->sanitize_imported_options( $defaults );

		foreach ( $defaults as $key => $value ) {
			$this->assertArrayHasKey( $key, $clean, $key );
			$this->assertEquals( $value, $clean[ $key ], $key . ' changed on import' );
		}
	}

	/**
	 * Select and button-group values must be one of the control's choices.
	 */
	public function test_choice_sanitizer_uses_control_choices(): void {
		$select = new WP_Customize_Setting(
			'clc-options[logo-settings]',
			'show-image-only',
			array(
				'hide-logo' => 'Hide logo',
				'use-both'  => 'Show both',
			)
		);

		$this->assertSame( 'use-both', clc_sanitize_choice( 'use-both', $select ) );
		$this->assertSame( 'show-image-only', clc_sanitize_choice( 'bogus', $select ) );

		$group = new WP_Customize_Setting(
			'clc-options[form-column-align]',
			3,
			array(
				'left'  => array( 'value' => 1 ),
				'right' => array( 'value' => 3 ),
			)
		);

		$this->assertSame( '1', clc_sanitize_choice( '1', $group ) );
		$this->assertSame( '3', clc_sanitize_choice( 'left', $group ), 'the key is not a value' );
	}
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

/**
 * Class Colorlib_Login_Customizer_Customizer
 */
class Colorlib_Login_Customizer_Customizer {
	/**
	 * @var
	 */
	public $settings;
	/**
	 * The main plugin object.
	 *
	 * @var    object
	 * @access   public
	 * @since    1.0.0
	 */
	public $parent = null;

	/**
	 * Colorlib_Login_Customizer_Customizer constructor.
	 *
	 * @param $parent
	 * @param $manager
	 */
	public function __construct( $parent, $manager ) {
		//Plugin object
		$this->parent = $parent;
		// Initialise settings
		$this->init_settings();
		// Register plugin sections and settings
		$this->register_settings( $manager );
		// Load customizer assets
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customizer_enqueue_scripts' ), 25 );
		add_action( 'customize_preview_init', array( $this, 'customize_preview_styles' ), 25 );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	private function generate_name( $id ) {
		return $this->parent->key_name . '[' . $id . ']';
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		$settings['templates'] = array(
			'title'       => esc_html__( 'Templates', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'templates',
					'label'       => esc_html__( 'Temapltes', 'colorlib-login-customizer' ),
					'description' => '',
					'type'        => 'clc-templates',
					'default'     => 'default',
					'choices'     => array(
						'default' => esc_url( $this->parent->assets_url ) . '/img/default.jpg',
						'01'      => esc_url( $this->parent->assets_url ) . '/img/template-01.jpg',
					),
				),
			),
		);

		$settings['logo'] = array(
			'title'       => esc_html__( 'Logo options', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'custom-logo-url',
					'label'       => esc_html__( 'Custom logo URL', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This is where the logo will link to.', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => esc_url( get_home_url() ),
				),
				array(
					'id'          => 'custom-logo',
					'label'       => esc_html__( 'Custom logo', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'colorlib-login-customizer' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'logo-width',
					'label'       => esc_html__( 'Logo Width', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Make sure you set the logo width to match your image.', 'colorlib-login-customizer' ),
					'default'     => 84,
					'choices'     => array(
						'min'  => 50,
						'max'  => 1000,
						'step' => 5,
					),
					'type'        => 'clc-range-slider',
				),
				array(
					'id'          => 'logo-height',
					'label'       => esc_html__( 'Logo Height', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Make sure you set the logo height to match your image.', 'colorlib-login-customizer' ),
					'default'     => 84,
					'choices'     => array(
						'min'  => 50,
						'max'  => 1000,
						'step' => 5,
					),
					'type'        => 'clc-range-slider',
				),
			),
		);

		$settings['background'] = array(
			'title'       => esc_html__( 'Background options', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'custom-background',
					'label'       => esc_html__( 'Custom background', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'colorlib-login-customizer' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'custom-background-color',
					'label'       => esc_html__( 'Custom background color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the background color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '',
				),
			),
		);

		$settings['form'] = array(
			'title'       => esc_html__( 'Form options', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'form-width',
					'label'       => esc_html__( 'Form Width', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Please input the desired width for the login form in pixels. Example: 20', 'colorlib-login-customizer' ),
					'default'     => 320,
					'type'        => 'clc-range-slider',
					'choices'     => array(
						'min'  => 150,
						'max'  => 1000,
						'step' => 5,
					),
				),
				array(
					'id'          => 'form-height',
					'label'       => esc_html__( 'Form Height', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Please input the desired height for the login form in pixels. Example: 20', 'colorlib-login-customizer' ),
					'default'     => 194,
					'choices'     => array(
						'min'  => 150,
						'max'  => 1000,
						'step' => 5,
					),
					'type'        => 'clc-range-slider',
				),
				array(
					'id'          => 'form-background-image',
					'label'       => esc_html__( 'Form background image', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the background image property of login form.', 'colorlib-login-customizer' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'form-background-color',
					'label'       => esc_html__( 'Form background color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the background color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '',
				),
				array(
					'id'          => 'form-padding',
					'label'       => esc_html__( 'Form padding', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the padding property. Example: 26px 24px 46px 30px', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => '26px 24px 46px',
				),
				array(
					'id'          => 'form-border',
					'label'       => esc_html__( 'Form border', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the border property. Example: 2px dotted black', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => '',
				),
				array(
					'id'          => 'form-field-width',
					'label'       => esc_html__( 'Form field width', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Please input the desired width for the form field in pixels. Example: 20', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => '100%',
				),
				array(
					'id'          => 'form-field-margin',
					'label'       => esc_html__( 'Form field margin', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the margin property. Example: 26px 24px 46px 30px', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => '2px 6px 16px 0px',
				),
				array(
					'id'          => 'form-field-background',
					'label'       => esc_html__( 'Form field background', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the background color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '',
				),
				array(
					'id'          => 'form-field-color',
					'label'       => esc_html__( 'Form field color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the text color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#333333',
				),
				array(
					'id'          => 'form-label-color',
					'label'       => esc_html__( 'Form label color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the label text color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#2EA2CC',
				),
			),
		);

		$settings['general'] = array(
			'title'       => esc_html__( 'Miscellaneous', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'button-background',
					'label'       => esc_html__( 'Button background', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s background property', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button-background-hover',
					'label'       => esc_html__( 'Button background hover state', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s background property on hover', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button-border-color',
					'label'       => esc_html__( 'Button border color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s border color property', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button-border-color-hover',
					'label'       => esc_html__( 'Button border hover state', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s border property on hover', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button-shadow',
					'label'       => esc_html__( 'Button shadow', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s shadow property. Example: 0 1px 0 #006799', 'colorlib-login-customizer' ),
					'type'        => 'text',
				),
				array(
					'id'          => 'button-color',
					'label'       => esc_html__( 'Button color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s text color property', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'link-color',
					'label'       => esc_html__( 'Link color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the text color of links that are underneath the login form', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'link-color-hover',
					'label'       => esc_html__( 'Link color hover', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the text color of links, that are underneath the login form, on hover', 'colorlib-login-customizer' ),
					'type'        => 'color',
				),
			),
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register settings in the customizer
	 */
	public function register_settings( $manager ) {
		$manager->add_panel(
			'clc_main_panel',
			array(
				'capability'     => 'edit_theme_options',
				'theme_supports' => '',
				'title'          => esc_html__( 'Colorlib Login Customizer', 'colorlib-login-customizer' ),
			)
		);

		foreach ( $this->settings as $section => $properties ) {
			$manager->add_section(
				'clc_' . $section,
				array(
					'title'       => $properties['title'],
					'description' => $properties['description'],
					'panel'       => 'clc_main_panel',
				)
			);

			foreach ( $properties['fields'] as $setting ) {
				$key_name      = $this->generate_name( $setting['id'] );
				$settings_args = array(
					'type'      => 'option',
					'transport' => 'refresh',
				);

				if ( 'templates' == $setting['id'] ) {
					$settings_args['transport'] = 'postMessage';
				}

				$manager->add_setting( $key_name, $settings_args );

				switch ( $setting['type'] ) {
					case 'image':
						$manager->add_control(
							new WP_Customize_Image_Control(
								$manager,
								$key_name,
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'section'     => 'clc_' . $section,
								)
							)
						);
						break;
					case 'color':
						$manager->add_control(
							new WP_Customize_Color_Control(
								$manager,
								$key_name,
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'section'     => 'clc_' . $section,
								)
							)
						);
						break;
					case 'clc-range-slider':
						$manager->add_control(
							new Colorlib_Login_Customizer_Range_Slider_Control(
								$manager,
								$key_name,
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'default'     => $setting['default'],
									'choices'     => $setting['choices'],
									'section'     => 'clc_' . $section,
								)
							)
						);
						break;
					case 'clc-templates':
						$manager->add_control(
							new Colorlib_Login_Customizer_Template_Control(
								$manager,
								$key_name,
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'default'     => $setting['default'],
									'choices'     => $setting['choices'],
									'section'     => 'clc_' . $section,
								)
							)
						);
						break;
					default:
						$manager->add_control(
							$key_name,
							array(
								'label'       => $setting['label'],
								'description' => $setting['description'],
								'section'     => 'clc_' . $section,
							)
						);
						break;
				}
			}
		}

	}

	/**
	 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
	 */
	public function customize_preview_styles() {
		wp_enqueue_style( 'colorlib-login-customizer-previewer', esc_url( $this->parent->assets_url ) . 'css/clc-customizer-previewer.css' );
		wp_enqueue_script( 'colorlib-login-customizer-preview', esc_url( $this->parent->assets_url ) . 'js/clc-preview.js', array( 'customize-preview' ), false, true );
	}

	/*
	 * Our Customizer script
	 *
	 * Dependencies: Customizer Controls script (core)
	 */
	public function customizer_enqueue_scripts() {
		wp_enqueue_style( 'colorlib-login-customizer-styles', esc_url( $this->parent->assets_url ) . 'css/clc-customizer.css' );
		wp_enqueue_script(
			'colorlib-login-customizer-script', esc_url( $this->parent->assets_url ) . 'js/clc-customizer.js', array(
				'jquery',
				'customize-controls',
			), false, true
		);

		wp_localize_script(
			'colorlib-login-customizer-script', 'CLCUrls', array(
				'siteurl' => get_option( 'siteurl' ),
			)
		);
	}
}

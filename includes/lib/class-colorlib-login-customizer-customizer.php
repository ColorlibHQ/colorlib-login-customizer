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
						'default'      => array(
							'url' => esc_url( $this->parent->assets_url ) . 'img/default.jpg',
							'options' => array(
								/**
								 * Layout
								 */
								'columns'                  => '1',
								'form-column-align'        => '3',
								'form-vertical-align'      => '2',
								/**
								 * Logo section
								 */
								'hide-logo'                 => 0,
								'custom-logo-url'           => '',
								'custom-logo'               => '',
								'logo-width'                => '84',
								'logo-height'               => '84',
								/**
								 * Background section
								 */
								'custom-background'         => '',
								'custom-background-color'   => '#f1f1f1',
								/**
								 * Form section
								 */
								'form-width'                => '320',
								'form-height'               => '297',
								'form-background-image'     => '',
								'form-background-color'     => 'rgba(255, 255, 255, 1)',
								'form-padding'              => '26px 24px 46px',
								'form-border'               => '',
								'form-field-width'          => '',
								'form-field-margin'         => '',
								'form-field-background'     => '#fbfbfb',
								'form-field-color'          => '#333333',
								'form-label-color'          => '#72777c',
								'hide-extra-links'          => 0,
								/**
								 * Others section ( misc )
								 */
								'button-background'         => '#0085ba',
								'button-background-hover'   => '#008ec2',
								'button-border-color'       => '#0073aa',
								'button-border-color-hover' => '#006799',
								'button-shadow'             => '',
								'button-color'              => '#ffffff',
								'link-color'                => '#555d66',
								'link-color-hover'          => '#00a0d2',
							),
						),
						'template-01'  => array(
							'url'     => esc_url( $this->parent->assets_url ) . 'img/template-01.jpg',
							'options' => array(
								'columns' => '2',
								'form-column-align' => '1',
								'custom-background' => esc_url( $this->parent->assets_url ) . 'img/background.jpg',
							),
						),
						'template-02'  => array(
							'url' => esc_url( $this->parent->assets_url ) . 'img/template-02.jpg',
							'options' => array(
								'hide-logo' => '1',
								'hide-extra-links' => '1',
								'custom-background' => esc_url( $this->parent->assets_url ) . 'img/background-1.jpg',
								'form-background-color' => 'rgba(255, 255, 255, 0)',
								'form-shadow' => 'none',
							),
						),
					),
				),
			),
		);

		$settings['logo'] = array(
			'title'       => esc_html__( 'Logo options', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'hide-logo',
					'label'       => esc_html__( 'Hide Logo', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Show/Hide the logo from login page', 'colorlib-login-customizer' ),
					'type'        => 'clc-toggle',
					'default'     => 0,
				),
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

		$settings['layout'] = array(
			'title'       => esc_html__( 'Layout options', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'columns',
					'label'       => esc_html__( 'Columns', 'colorlib-login-customizer' ),
					'description' => '',
					'default'     => 1,
					'choices'     => array(
						1 => array(
							'value' => 1,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/one-column.png',
						),
						2 => array(
							'value' => 2,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/two-column.png',
						),
					),
					'type'        => 'clc-button-group',
				),
				array(
					'id'          => 'form-column-align',
					'label'       => esc_html__( 'Form Column Alignment', 'colorlib-login-customizer' ),
					'description' => '',
					'default'     => 3,
					'choices'     => array(
						'left' => array(
							'value' => 1,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-align-left.jpg',
						),
						'top' => array(
							'value' => 2,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-align-top.jpg',
						),
						'right' => array(
							'value' => 3,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-align-right.jpg',
						),
						'bottom' => array(
							'value' => 4,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-align-bottom.jpg',
						),
					),
					'type'        => 'clc-button-group',
					'active_callback' => array( $this, 'check_two_column_layout' ),
				),
				array(
					'id'          => 'form-vertical-align',
					'label'       => esc_html__( 'Form Vertical Alignment', 'colorlib-login-customizer' ),
					'description' => '',
					'default'     => 2,
					'choices'     => array(
						'top' => array(
							'value' => 1,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-vertical-align-top.png',
						),
						'middle' => array(
							'value' => 2,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-vertical-align-middle.png',
						),
						'bottom' => array(
							'value' => 3,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-vertical-align-bottom.png',
						),
					),
					'type'        => 'clc-button-group',
				),
				array(
					'id'          => 'form-horizontal-align',
					'label'       => esc_html__( 'Form Horizontal Alignment', 'colorlib-login-customizer' ),
					'description' => '',
					'default'     => 2,
					'choices'     => array(
						'left' => array(
							'value' => 1,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-horizontal-align-left.png',
						),
						'middle' => array(
							'value' => 2,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-vertical-align-middle.png',
						),
						'right' => array(
							'value' => 3,
							'png'   => COLORLIB_LOGIN_CUSTOMIZER_URL . '/assets/img/form-horizontal-align-right.png',
						),
					),
					'type'        => 'clc-button-group',
				),
			),
		);

		$settings['background'] = array(
			'title'       => esc_html__( 'Background options', 'colorlib-login-customizer' ),
			'description' => '',
			'fields'      => array(
				array(
					'id'          => 'custom-background-color',
					'label'       => esc_html__( 'Custom background color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the background color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#f1f1f1',
				),
				array(
					'id'          => 'custom-background',
					'label'       => esc_html__( 'Custom background', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'colorlib-login-customizer' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'custom-background-color-form',
					'label'       => esc_html__( 'Form Column background color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the background color property.', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#f1f1f1',
					'active_callback' => array( $this, 'check_two_column_layout' ),
				),
				array(
					'id'          => 'custom-background-form',
					'label'       => esc_html__( 'Form Column background', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'colorlib-login-customizer' ),
					'type'        => 'image',
					'default'     => '',
					'active_callback' => array( $this, 'check_two_column_layout' ),
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
					'default'     => '#ffffff',
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
					'default'     => '0 none',
				),
				array(
					'id'          => 'form-shadow',
					'label'       => esc_html__( 'Form shadow', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the form\'s shadow property. Example: 0 1px 0 #006799', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => '0 1px 3px rgba(0,0,0,.13)',
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
					'default'     => '#fbfbfb',
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
					'default'     => '#72777c',
				),
				array(
					'id'          => 'hide-extra-links',
					'label'       => esc_html__( 'Hide Extra Links', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'Show/Hide the links under the login form', 'colorlib-login-customizer' ),
					'type'        => 'clc-toggle',
					'default'     => 0,
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
					'default'     => '#0085ba',
				),
				array(
					'id'          => 'button-background-hover',
					'label'       => esc_html__( 'Button background hover state', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s background property on hover', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#008ec2',
				),
				array(
					'id'          => 'button-border-color',
					'label'       => esc_html__( 'Button border color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s border color property', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#0073aa',
				),
				array(
					'id'          => 'button-border-color-hover',
					'label'       => esc_html__( 'Button border hover state', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s border property on hover', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#006799',
				),
				array(
					'id'          => 'button-shadow',
					'label'       => esc_html__( 'Button shadow', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s shadow property. Example: 0 1px 0 #006799', 'colorlib-login-customizer' ),
					'type'        => 'text',
					'default'     => '0 1px 0 #006799',
				),
				array(
					'id'          => 'button-color',
					'label'       => esc_html__( 'Button color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the submit button\'s text color property', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#ffffff',
				),
				array(
					'id'          => 'link-color',
					'label'       => esc_html__( 'Link color', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the text color of links that are underneath the login form', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#555d66',
				),
				array(
					'id'          => 'link-color-hover',
					'label'       => esc_html__( 'Link color hover', 'colorlib-login-customizer' ),
					'description' => esc_html__( 'This will change the text color of links, that are underneath the login form, on hover', 'colorlib-login-customizer' ),
					'type'        => 'color',
					'default'     => '#00a0d2',
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
					'transport' => 'postMessage',
				);

				if ( isset( $setting['default'] ) ) {
					$settings_args['default'] = $setting['default'];
				}

				$manager->add_setting( $key_name, $settings_args );

				$control_args = array(
					'label'       => $setting['label'],
					'description' => $setting['description'],
					'section'     => 'clc_' . $section,
				);

				if ( isset( $setting['active_callback'] ) ) {
					$control_args['active_callback'] = $setting['active_callback'];
				}

				if ( isset( $setting['choices'] ) ) {
					$control_args['choices'] = $setting['choices'];
				}

				switch ( $setting['type'] ) {
					case 'image':
						$manager->add_control(
							new WP_Customize_Image_Control( $manager, $key_name, $control_args )
						);
						break;
					case 'color':
						$manager->add_control(
							new Colorlib_Login_Customizer_Control_Color_Picker( $manager, $key_name, $control_args )
						);
						break;
					case 'clc-range-slider':
						$manager->add_control(
							new Colorlib_Login_Customizer_Range_Slider_Control( $manager, $key_name, $control_args )
						);
						break;
					case 'clc-templates':
						$manager->add_control(
							new Colorlib_Login_Customizer_Template_Control( $manager, $key_name, $control_args )
						);
						break;
					case 'clc-button-group':
						$manager->add_control(
							new Colorlib_Login_Customizer_Button_Group_Control( $manager, $key_name, $control_args )
						);
						break;
					case 'clc-toggle' :
						$manager->add_control(
							new Colorlib_Login_Customizer_Control_Toggle( $manager, $key_name, $control_args )
						);
						break;
					default:
						$manager->add_control( $key_name, $control_args );
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
		wp_enqueue_style( 'colorlib-login-customizer-minicolors-styles', esc_url( $this->parent->assets_url ) . 'css/jquery.minicolors.css' );
		wp_enqueue_style( 'colorlib-login-customizer-styles', esc_url( $this->parent->assets_url ) . 'css/clc-customizer.css' );
		wp_enqueue_script(
			'colorlib-login-customizer-minicolors-script', esc_url( $this->parent->assets_url ) . 'js/jquery.minicolors.js', array(
				'jquery',
			), false, true
		);
		wp_enqueue_script(
			'colorlib-login-customizer-script', esc_url( $this->parent->assets_url ) . 'js/clc-customizer.js', array(
				'jquery',
				'customize-controls',
				'colorlib-login-customizer-minicolors-script',
			), false, true
		);

		wp_localize_script(
			'colorlib-login-customizer-script', 'CLCUrls', array(
				'siteurl' => get_option( 'siteurl' ),
			)
		);
	}

	// Active callbacks
	public function check_two_column_layout( $control ) {
		$options = get_option( 'clc-options', array() );

		if ( '2' == $control->manager->get_setting( 'clc-options[columns]' )->value() ) {
			return true;
		}

		return false;
	}
}

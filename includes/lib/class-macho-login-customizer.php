<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
};

/**
 * Class Macho_Login_Customizer
 */
class Macho_Login_Customizer {
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
	 * Macho_Login_Customizer constructor.
	 *
	 * @param $parent
	 * @param $manager
	 */
	public function __construct( $parent, $manager ) {
		//Plugin object
		$this->parent = $parent;
		// Prefix
		$this->base = 'ml_';
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

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		$settings['logo'] = array(
			'title'       => esc_html__( 'Logo options', 'macho-login' ),
			'description' => esc_html__( 'Logo options description.', 'macho-login' ),
			'fields'      => array(
				array(
					'id'          => 'custom_logo_url',
					'label'       => esc_html__( 'Custom logo url', 'macho-login' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'macho-login' ),
					'type'        => 'text',
					'default'     => esc_url( get_home_url() ),
				),
				array(
					'id'          => 'custom_logo',
					'label'       => esc_html__( 'Custom logo', 'macho-login' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'macho-login' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'logo_width',
					'label'       => esc_html__( 'Logo Width', 'macho-login' ),
					'description' => esc_html__( 'Make sure you set the logo width to match your image.', 'macho-login' ),
					'default'     => 84,
					'choices'     => array(
						'min'  => 50,
						'max'  => 1000,
						'step' => 5,
					),
					'type'        => 'slider',
				),
				array(
					'id'          => 'logo_height',
					'label'       => esc_html__( 'Logo Height', 'macho-login' ),
					'description' => esc_html__( 'Make sure you set the logo width to match your image.', 'macho-login' ),
					'default'     => 84,
					'choices'     => array(
						'min'  => 50,
						'max'  => 1000,
						'step' => 5,
					),
					'type'        => 'slider',
				),
			)
		);

		$settings['background'] = array(
			'title'       => esc_html__( 'Background options', 'macho-login' ),
			'description' => esc_html__( 'Background options description.', 'macho-login' ),
			'fields'      => array(
				array(
					'id'          => 'custom_background',
					'label'       => esc_html__( 'Custom background', 'macho-login' ),
					'description' => esc_html__( 'This will upload an image to your media library and store the attachment ID in the option field. Once you have uploaded an imge the thumbnail will display above these buttons.', 'macho-login' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'custom_background_color',
					'label'       => esc_html__( 'Custom background color', 'macho-login' ),
					'description' => esc_html__( 'This will change the background color property.', 'macho-login' ),
					'type'        => 'color',
					'default'     => '',
				),
			)
		);

		$settings['form'] = array(
			'title'       => esc_html__( 'Form options', 'macho-login' ),
			'description' => esc_html__( 'Form options description.', 'macho-login' ),
			'fields'      => array(
				array(
					'id'          => 'form_width',
					'label'       => esc_html__( 'Form Width', 'macho-login' ),
					'description' => esc_html__( 'Form width', 'macho-login' ),
					'default'     => 320,
					'type'        => 'slider',
					'choices'     => array(
						'min'  => 150,
						'max'  => 1000,
						'step' => 5,
					),
				),
				array(
					'id'          => 'form_height',
					'label'       => esc_html__( 'Form Height', 'macho-login' ),
					'description' => esc_html__( 'Form Height', 'macho-login' ),
					'default'     => 194,
					'choices'     => array(
						'min'  => 150,
						'max'  => 1000,
						'step' => 5,
					),
					'type'        => 'slider',
				),
				array(
					'id'          => 'form_background_image',
					'label'       => esc_html__( 'Form background image', 'macho-login' ),
					'description' => esc_html__( 'This will change the background image property.', 'macho-login' ),
					'type'        => 'image',
					'default'     => '',
				),
				array(
					'id'          => 'form_background_color',
					'label'       => esc_html__( 'Form background color', 'macho-login' ),
					'description' => esc_html__( 'This will change the background color property.', 'macho-login' ),
					'type'        => 'color',
					'default'     => '',
				),
				array(
					'id'          => 'form_padding',
					'label'       => esc_html__( 'Form padding', 'macho-login' ),
					'description' => esc_html__( 'This will change the padding property.', 'macho-login' ),
					'type'        => 'text',
					'default'     => '26px 24px 46px',
				),
				array(
					'id'          => 'form_border',
					'label'       => esc_html__( 'Form border', 'macho-login' ),
					'description' => esc_html__( 'Border (Example: 2px dotted black)', 'macho-login' ),
					'type'        => 'text',
					'default'     => '',
				),
				array(
					'id'          => 'form_field_width',
					'label'       => esc_html__( 'Form field width', 'macho-login' ),
					'description' => esc_html__( 'Width property', 'macho-login' ),
					'type'        => 'text',
					'default'     => '100%',
				),
				array(
					'id'          => 'form_field_margin',
					'label'       => esc_html__( 'Form field margin', 'macho-login' ),
					'description' => esc_html__( 'margin property', 'macho-login' ),
					'type'        => 'text',
					'default'     => '2px 6px 16px 0px',
				),
				array(
					'id'          => 'form_field_background',
					'label'       => esc_html__( 'Form field background', 'macho-login' ),
					'description' => esc_html__( 'margin property', 'macho-login' ),
					'type'        => 'color',
					'default'     => '',
				),
				array(
					'id'          => 'form_field_color',
					'label'       => esc_html__( 'Form field color', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
					'default'     => '#333333',
				),
				array(
					'id'          => 'form_label_color',
					'label'       => esc_html__( 'Form label color', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
					'default'     => '#2EA2CC',
				),
			)
		);

		$settings['general'] = array(
			'title'       => esc_html__( 'Miscellaneous', 'macho-login' ),
			'description' => esc_html__( 'Miscellaneous description.', 'macho-login' ),
			'fields'      => array(
				array(
					'id'          => 'button_background',
					'label'       => esc_html__( 'Button background', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button_background_hover',
					'label'       => esc_html__( 'Button background hover state', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button_border_color',
					'label'       => esc_html__( 'Button border color', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button_border_color_hover',
					'label'       => esc_html__( 'Button border hover state', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'button_shadow',
					'label'       => esc_html__( 'Button shadow', 'macho-login' ),
					'description' => esc_html__( 'shadow property', 'macho-login' ),
					'type'        => 'text',
				),
				array(
					'id'          => 'button_color',
					'label'       => esc_html__( 'Button color', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'link_color',
					'label'       => esc_html__( 'Link color', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
				array(
					'id'          => 'link_color_hover',
					'label'       => esc_html__( 'Link color hover', 'macho-login' ),
					'description' => esc_html__( 'Color property', 'macho-login' ),
					'type'        => 'color',
				),
			)
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register settings in the customizer
	 */
	public function register_settings( $manager ) {
		$manager->add_panel(
			'ml_main_panel',
			array(
				'priority'       => 10,
				'capability'     => 'edit_theme_options',
				'theme_supports' => '',
				'title'          => esc_html__( 'Macho Login Options', 'macho-login' )
			)
		);

		/**
		 * Add a section
		 */
		$manager->add_section(
			new Epsilon_Section_Predefined_Schemes(
				$manager,
				'ml_predefined_color_schemes',
				array(
					'priority'      => 0,
					'type'          => 'epsilon-section-predefined-schemes',
					'panel'         => 'ml_main_panel',
					'title'         => esc_html__( 'Predefined setups', 'macho-login' ),
					'description'   => esc_html__( 'Select one our predefined setups', 'macho-login' ),
					/**
					 *
					 * @todo Adaugat defaulturi pentru toate optiunile
					 *       Se pot adauga direct in JS, sau aici.
					 *       Ramane de vazut
					 *
					 */
					'color_schemes' => array(
						'black' => array(
							'id'      => 'black',
							/**
							 * @todo ca sa nu mai complicam treaba aiurea, sa folosim niste PNG thumbs pentru setups
							 */
							'thumb'   => $this->parent->assets_url . '/img/black-scheme.png',
							'options' => array(
								'custom_background_color' => '#000',
							),
						),
						'red'   => array(
							'id'      => 'black',
							'thumb'   => $this->parent->assets_url . '/img/black-scheme.png',
							'options' => array(
								'custom_background_color' => '#e62117',
								'form_background_color'   => '#000',
							),
						),
					)
				)
			)
		);

		foreach ( $this->settings as $section => $properties ) {
			$manager->add_section(
				'ml_' . $section,
				array(
					'title'       => $properties['title'],
					'description' => $properties['description'],
					'panel'       => 'ml_main_panel'
				)
			);

			foreach ( $properties['fields'] as $setting ) {
				$manager->add_setting(
					'ml_' . $setting['id'],
					array(
						'type' => 'option'
					)
				);

				switch ( $setting['type'] ) {
					case 'image':
						$manager->add_control(
							new WP_Customize_Image_Control(
								$manager,
								'ml_' . $setting['id'],
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'section'     => 'ml_' . $section,
								)
							)
						);
						break;
					case 'color':
						$manager->add_control(
							new WP_Customize_Color_Control(
								$manager,
								'ml_' . $setting['id'],
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'section'     => 'ml_' . $section,
								)
							)
						);
						break;
					case 'slider':
						$manager->add_control(
							new Epsilon_Control_Slider_Custom(
								$manager,
								'ml_' . $setting['id'],
								array(
									'label'       => $setting['label'],
									'description' => $setting['description'],
									'default'     => $setting['default'],
									'choices'     => $setting['choices'],
									'section'     => 'ml_' . $section,
								)
							)
						);
						break;
					default:
						$manager->add_control(
							'ml_' . $setting['id'],
							array(
								'label'       => $setting['label'],
								'description' => $setting['description'],
								'section'     => 'ml_' . $section,
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
		wp_enqueue_style( 'c-epsilon-styles', esc_url( $this->parent->assets_url ) . 'css/custom-epsilon.css' );
	}

	/*
	 * Our Customizer script
	 *
	 * Dependencies: Customizer Controls script (core)
	 */
	public function customizer_enqueue_scripts() {
		wp_enqueue_style( 'c-epsilon-styles', esc_url( $this->parent->assets_url ) . 'css/custom-epsilon.css' );
		wp_enqueue_script( 'c-epsilon-object', esc_url( $this->parent->assets_url ) . 'js/ml-login.js', array(
			'jquery',
			'customize-controls'
		), false, true );

		wp_localize_script( 'c-epsilon-object', 'WPUrls', array(
			'siteurl' => get_option( 'siteurl' ),
			'theme'   => get_template_directory_uri(),
			'ajaxurl' => admin_url( 'admin-ajax.php' )
		) );
	}
}
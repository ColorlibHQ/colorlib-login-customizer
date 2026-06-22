<?php
/**
 * Admin review-request notice handler.
 *
 * @package Colorlib_Login_Customizer
 */

/**
 * Displays a time-delayed admin notice asking the user to leave a review.
 */
class CLC_Review {

	/**
	 * The single instance of this class.
	 *
	 * @var CLC_Review
	 */
	private static $instance;

	/**
	 * Days after install at which the review notice is shown.
	 *
	 * @var array
	 */
	private $when = array( 5, 15, 30 );

	/**
	 * Number of days since the plugin was installed.
	 *
	 * @var int
	 */
	private $value;

	/**
	 * Notice strings keyed by purpose.
	 *
	 * @var array
	 */
	private $messages;

	/**
	 * Review URL template for the plugin.
	 *
	 * @var string
	 */
	private $link = 'https://wordpress.org/support/plugin/%s/reviews/#new-post';

	/**
	 * Plugin slug used to build the review URL.
	 *
	 * @var string
	 */
	private $slug = '';

	/**
	 * Option name used to persist the review state.
	 *
	 * @var string
	 */
	private $option_name = '';

	/**
	 * Constructor.
	 *
	 * @param array $args Configuration arguments (expects 'slug' and optional 'messages').
	 */
	public function __construct( $args ) {

		if ( isset( $args['slug'] ) ) {
			$this->slug = $args['slug'];
		}

		$this->value = $this->value();

		$this->messages = array(
			/* translators: %s: number of days since the plugin was installed. */
			'notice'  => __( "Hey, I noticed you have installed our Colorlib Login Customizer plugin for %s day(s) - that's awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.", 'colorlib-login-customizer' ),
			'rate'    => __( 'Ok, you deserve it', 'colorlib-login-customizer' ),
			'rated'   => __( 'I already did', 'colorlib-login-customizer' ),
			'no_rate' => __( 'No, not good enough', 'colorlib-login-customizer' ),
		);

		if ( isset( $args['messages'] ) ) {
			$this->messages = wp_parse_args( $args['messages'], $this->messages );
		}

		$this->init();
	}

	/**
	 * Retrieve the single instance of this class.
	 *
	 * @param array $args Configuration arguments.
	 * @return CLC_Review
	 */
	public static function get_instance( $args ) {
		if ( null === static::$instance ) {
			static::$instance = new static( $args );
		}

		return static::$instance;
	}

	/**
	 * Register the admin hooks for the review notice.
	 *
	 * @return void
	 */
	private function init() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'wp_ajax_clc_epsilon_review', array( $this, 'ajax' ) );

		if ( $this->check() ) {
			add_action( 'admin_notices', array( $this, 'five_star_wp_rate_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );
		}
	}

	/**
	 * Determine whether the review notice should be displayed.
	 *
	 * @return bool True when the notice should be shown.
	 */
	private function check() {

		$options = get_option( 'clc-options' );
		$option  = isset( $options['givemereview'] ) ? $options['givemereview'] : '';

		if ( 'already-rated' == $option ) {
			return false;
		}

		if ( $this->value == $option && '' != $option ) {
			return false;
		}

		if ( is_array( $this->when ) ) {
			foreach ( $this->when as $et ) {
				if ( $et == $this->value ) {
					return true;
				}
			}
		}
	}

	/**
	 * Calculate the number of days since the plugin was installed.
	 *
	 * @return int|null Days since install, or null on first run.
	 */
	private function value() {

		$value = get_transient( 'clc_review' );

		if ( $value ) {
			$current_time = time(); // Current time in seconds.
			$trans_date   = strtotime( $value );
			$date_diff    = $current_time - $trans_date;
			return round( $date_diff / ( 60 * 60 * 24 ) );
		}

		$date = gmdate( 'Y-m-d' );
		set_transient( 'clc_review', $date, 24 * 30 * HOUR_IN_SECONDS );
	}

	/**
	 * Render the review-request admin notice.
	 *
	 * @return void
	 */
	public function five_star_wp_rate_notice() {

		$url = sprintf( $this->link, $this->slug );

		?>
		<div id="<?php echo esc_attr( $this->slug ); ?>-epsilon-review-notice" class="notice notice-success is-dismissible">
			<p><?php printf( wp_kses_post( $this->messages['notice'] ), esc_html( $this->value ) ); ?></p>
			<p class="actions">
				<a id="epsilon-rate" href="<?php echo esc_url( $url ); ?>"
					class="button button-primary epsilon-review-button"><?php echo esc_html( $this->messages['rate'] ); ?></a>
				<a id="epsilon-rated" href="#"
					class="button button-secondary epsilon-review-button"><?php echo esc_html( $this->messages['rated'] ); ?></a>
				<a id="epsilon-no-rate" href="#"
					class="button button-secondary epsilon-review-button"><?php echo esc_html( $this->messages['no_rate'] ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Handle the AJAX request that records the review choice.
	 *
	 * @return void
	 */
	public function ajax() {

		check_ajax_referer( 'epsilon-review', 'security' );

		$options = get_option( 'clc-options', array() );

		if ( isset( $_POST['epsilon-review'] ) ) {
			$options['givemereview'] = 'already-rated';
		} else {
			$options['givemereview'] = $this->value;
		}

		update_option( 'clc-options', $options );

		wp_die( 'ok' );
	}

	/**
	 * Enqueue the scripts required by the review notice.
	 *
	 * @return void
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Print the inline JavaScript that handles the review notice actions.
	 *
	 * @return void
	 */
	public function ajax_script() {

		$ajax_nonce = wp_create_nonce( 'epsilon-review' );

		?>

		<script type="text/javascript">
			jQuery(document).ready(function ($) {

				$('.epsilon-review-button').click(function (evt) {
					var href = $(this).attr('href'),
						id = $(this).attr('id');

					evt.preventDefault();

					var data = {
						action: 'clc_epsilon_review',
						security: '<?php echo esc_js( $ajax_nonce ); ?>',
					};

					if ('epsilon-rated' === id || 'epsilon-rate' === id) {
						data['epsilon-review'] = 1;
					}


					$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function (response) {
						$('#<?php echo esc_attr( $this->slug ); ?>-epsilon-review-notice').slideUp('fast', function () {
							$(this).remove();
						});

						if ('epsilon-rate' === id) {
							window.location.href = href;
						}

					});

				});

				$('#colorlib-login-customizer-epsilon-review-notice .notice-dismiss').click(function(){

					var data = {
						action: 'clc_epsilon_review',
						security: '<?php echo esc_js( $ajax_nonce ); ?>',
					};

					$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', data, function (response) {
						$('#<?php echo esc_attr( $this->slug ); ?>-epsilon-review-notice').slideUp('fast', function () {
							$(this).remove();
						});

					});
				});

			});
		</script>

		<?php
	}
}
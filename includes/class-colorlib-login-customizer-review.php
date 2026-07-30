<?php
/**
 * Admin review-request notice handler.
 *
 * @package Colorlib_Login_Customizer
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays a time-delayed admin notice asking the user to leave a review.
 */
class CLC_Review {

	/**
	 * The single instance of this class.
	 *
	 * @var CLC_Review|null
	 */
	private static ?CLC_Review $instance = null;

	/**
	 * Capability required to see and act on the review notice.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * User meta key holding this user's review-notice state.
	 *
	 * @var string
	 */
	private const USER_META_KEY = 'clc_review_state';

	/**
	 * Days after install at which the review notice is shown.
	 *
	 * @var array<int, int>
	 */
	private array $when = array( 5, 15, 30 );

	/**
	 * Number of days since the plugin was installed. Null until the install
	 * date transient exists (i.e. on the very first admin request).
	 *
	 * @var int|null
	 */
	private ?int $value = null;

	/**
	 * Notice strings keyed by purpose.
	 *
	 * @var array<string, string>
	 */
	private array $messages;

	/**
	 * Review URL template for the plugin.
	 *
	 * @var string
	 */
	private string $link = 'https://wordpress.org/support/plugin/%s/reviews/#new-post';

	/**
	 * Plugin slug used to build the review URL.
	 *
	 * @var string
	 */
	private string $slug = '';

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $args Configuration arguments (expects 'slug' and optional 'messages').
	 */
	public function __construct( array $args ) {

		if ( isset( $args['slug'] ) ) {
			$this->slug = (string) $args['slug'];
		}

		$this->value = $this->value();

		$this->messages = array(
			/* translators: %s: number of days since the plugin was installed. */
			'notice'  => __( "Hey, I noticed you have installed our Colorlib Login Customizer plugin for %s day(s) - that's awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.", 'colorlib-login-customizer' ),
			'rate'    => __( 'Ok, you deserve it', 'colorlib-login-customizer' ),
			'rated'   => __( 'I already did', 'colorlib-login-customizer' ),
			'no_rate' => __( 'No, not good enough', 'colorlib-login-customizer' ),
		);

		if ( isset( $args['messages'] ) && is_array( $args['messages'] ) ) {
			$this->messages = wp_parse_args( $args['messages'], $this->messages );
		}

		$this->init();
	}

	/**
	 * Retrieve the single instance of this class.
	 *
	 * @param array<string, mixed> $args Configuration arguments.
	 * @return CLC_Review
	 */
	public static function get_instance( array $args ): CLC_Review {
		if ( null === self::$instance ) {
			self::$instance = new self( $args );
		}

		return self::$instance;
	}

	/**
	 * Register the admin hooks for the review notice.
	 *
	 * @return void
	 */
	private function init(): void {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		add_action( 'wp_ajax_clc_epsilon_review', array( $this, 'ajax' ) );

		if ( $this->check() ) {
			add_action( 'admin_notices', array( $this, 'five_star_wp_rate_notice' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );
		}
	}

	/**
	 * Read this user's stored review state.
	 *
	 * Falls back to the legacy site-wide `clc-options[givemereview]` value so
	 * that a dismissal made before the per-user migration is still honoured.
	 *
	 * @return string Stored state, or an empty string when never dismissed.
	 */
	private function get_state(): string {
		$state = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );

		if ( '' !== $state && false !== $state && null !== $state ) {
			return (string) $state;
		}

		$options = get_option( 'clc-options', array() );

		if ( is_array( $options ) && isset( $options['givemereview'] ) ) {
			return (string) $options['givemereview'];
		}

		return '';
	}

	/**
	 * Determine whether the review notice should be displayed.
	 *
	 * @return bool True when the notice should be shown.
	 */
	private function check(): bool {

		if ( null === $this->value ) {
			return false;
		}

		$state = $this->get_state();

		if ( 'already-rated' === $state ) {
			return false;
		}

		if ( '' !== $state && (string) $this->value === $state ) {
			return false;
		}

		return in_array( $this->value, $this->when, true );
	}

	/**
	 * Calculate the number of days since the plugin was installed.
	 *
	 * @return int|null Days since install, or null on the first run (when the
	 *                  install date is only just being recorded).
	 */
	private function value(): ?int {

		$value = get_transient( 'clc_review' );

		if ( is_string( $value ) && '' !== $value ) {
			$trans_date = strtotime( $value );

			if ( false === $trans_date ) {
				return null;
			}

			return (int) round( ( time() - $trans_date ) / DAY_IN_SECONDS );
		}

		set_transient( 'clc_review', gmdate( 'Y-m-d' ), 30 * DAY_IN_SECONDS );

		return null;
	}

	/**
	 * Render the review-request admin notice.
	 *
	 * @return void
	 */
	public function five_star_wp_rate_notice(): void {

		$url = sprintf( $this->link, $this->slug );

		?>
		<div id="<?php echo esc_attr( $this->slug ); ?>-epsilon-review-notice" class="notice notice-success is-dismissible">
			<p><?php printf( wp_kses_post( $this->messages['notice'] ), esc_html( (string) $this->value ) ); ?></p>
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
	public function ajax(): void {

		check_ajax_referer( 'epsilon-review', 'security' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( -1, 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by check_ajax_referer() above.
		$rated = isset( $_POST['epsilon-review'] );

		update_user_meta(
			get_current_user_id(),
			self::USER_META_KEY,
			$rated ? 'already-rated' : (string) $this->value
		);

		wp_die( 'ok' );
	}

	/**
	 * Print the inline JavaScript that handles the review notice actions.
	 *
	 * @return void
	 */
	public function ajax_script(): void {

		$ajax_nonce = wp_create_nonce( 'epsilon-review' );
		$notice_id  = $this->slug . '-epsilon-review-notice';

		?>
		<script>
			( function () {
				'use strict';

				var notice = document.getElementById( <?php echo wp_json_encode( $notice_id ); ?> );

				if ( ! notice ) {
					return;
				}

				var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
				var nonce   = <?php echo wp_json_encode( $ajax_nonce ); ?>;

				function dismiss( rated, then ) {
					var body = new URLSearchParams();
					body.append( 'action', 'clc_epsilon_review' );
					body.append( 'security', nonce );

					if ( rated ) {
						body.append( 'epsilon-review', '1' );
					}

					window.fetch( ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: body.toString()
					} ).then( function () {
						if ( notice && notice.parentNode ) {
							notice.parentNode.removeChild( notice );
						}
						if ( then ) {
							then();
						}
					} );
				}

				Array.prototype.forEach.call(
					notice.querySelectorAll( '.epsilon-review-button' ),
					function ( button ) {
						button.addEventListener( 'click', function ( event ) {
							event.preventDefault();

							var id   = button.getAttribute( 'id' );
							var href = button.getAttribute( 'href' );

							dismiss( 'epsilon-rated' === id || 'epsilon-rate' === id, function () {
								if ( 'epsilon-rate' === id ) {
									window.location.href = href;
								}
							} );
						} );
					}
				);

				notice.addEventListener( 'click', function ( event ) {
					if ( event.target.classList.contains( 'notice-dismiss' ) ) {
						dismiss( false );
					}
				} );
			}() );
		</script>
		<?php
	}
}

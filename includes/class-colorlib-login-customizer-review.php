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
 *
 * The notice appears at most once per stage (5, 15 and 30 days after
 * install). Closing it snoozes it until the next stage; "I already did" and
 * "No, not good enough" end it for good.
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
	 * Either a final state ('already-rated', 'declined') or the stage, in
	 * days, at which the notice was last closed.
	 *
	 * @var string
	 */
	private const USER_META_KEY = 'clc_review_state';

	/**
	 * Option holding the install date (Y-m-d).
	 *
	 * A real, autoloaded option: it used to be a 30-day transient, which
	 * expired, restarted the countdown and re-asked every month forever — and
	 * cost two uncached queries on every admin page.
	 *
	 * @var string
	 */
	private const INSTALL_OPTION = 'clc_review_installed';

	/**
	 * Days after install at which the review notice is shown.
	 *
	 * @var array<int, int>
	 */
	private array $when = array( 5, 15, 30 );

	/**
	 * The latest stage (days since install) reached, or null before the first.
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
	 * Get the singleton instance.
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
	 * Register hooks for users who can act on the notice.
	 *
	 * @return void
	 */
	private function init(): void {
		if ( ! is_admin() || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		add_action( 'wp_ajax_clc_epsilon_review', array( $this, 'ajax' ) );

		// The AJAX handler needs no notice state; skip the lookups there.
		if ( wp_doing_ajax() ) {
			return;
		}

		$this->value = $this->value();

		if ( $this->check() ) {
			add_action( 'admin_notices', array( $this, 'five_star_wp_rate_notice' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'ajax_script' ) );
		}
	}

	/**
	 * Read this user's stored notice state.
	 *
	 * @return string
	 */
	private function get_state(): string {
		$state = get_user_meta( get_current_user_id(), self::USER_META_KEY, true );

		if ( is_scalar( $state ) && '' !== (string) $state ) {
			return (string) $state;
		}

		// Legacy: before 2.2 the choice was stored site-wide in the options.
		$options = get_option( 'clc-options', array() );

		if ( is_array( $options ) && isset( $options['givemereview'] ) && is_scalar( $options['givemereview'] ) ) {
			return (string) $options['givemereview'];
		}

		return '';
	}

	/**
	 * Whether the notice should be displayed.
	 *
	 * @return bool
	 */
	private function check(): bool {

		if ( null === $this->value ) {
			return false;
		}

		$state = $this->get_state();

		if ( 'already-rated' === $state || 'declined' === $state ) {
			return false;
		}

		// Closed at an earlier stage: ask again only once a later one is reached.
		return '' === $state || $this->value > (int) $state;
	}

	/**
	 * The latest stage reached since install, or null before the first one.
	 *
	 * @return int|null
	 */
	private function value(): ?int {
		$installed = get_option( self::INSTALL_OPTION, '' );

		if ( ! is_string( $installed ) || '' === $installed ) {
			// Carry over the date from the old transient where it still exists.
			$legacy    = get_transient( 'clc_review' );
			$installed = is_string( $legacy ) && false !== strtotime( $legacy ) ? $legacy : gmdate( 'Y-m-d' );

			update_option( self::INSTALL_OPTION, $installed, true );
		}

		$timestamp = strtotime( $installed );

		if ( false === $timestamp ) {
			return null;
		}

		$days  = (int) floor( ( time() - $timestamp ) / DAY_IN_SECONDS );
		$stage = null;

		foreach ( $this->when as $when ) {
			if ( $days >= $when ) {
				$stage = $when;
			}
		}

		return $stage;
	}

	/**
	 * Whether the current admin screen is one where the notice belongs.
	 *
	 * The dashboard, the plugins list and this plugin's own page — not every
	 * screen in wp-admin.
	 *
	 * @return bool
	 */
	private function is_notice_screen(): bool {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen ) {
			return false;
		}

		return in_array( $screen->base, array( 'dashboard', 'plugins' ), true )
			|| false !== strpos( (string) $screen->id, 'colorlib-login-customizer_settings' );
	}

	/**
	 * Output the review notice.
	 *
	 * @return void
	 */
	public function five_star_wp_rate_notice(): void {

		if ( ! $this->is_notice_screen() ) {
			return;
		}

		$url = sprintf( $this->link, $this->slug );

		?>
		<div id="<?php echo esc_attr( $this->slug ); ?>-epsilon-review-notice" class="notice notice-success is-dismissible">
			<p><?php printf( wp_kses_post( $this->messages['notice'] ), esc_html( (string) $this->value ) ); ?></p>
			<p class="actions">
				<a id="epsilon-rate" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"
					class="button button-primary epsilon-review-button"><?php echo esc_html( $this->messages['rate'] ); ?></a>
				<a id="epsilon-rated" href="#" role="button"
					class="button button-secondary epsilon-review-button"><?php echo esc_html( $this->messages['rated'] ); ?></a>
				<a id="epsilon-no-rate" href="#" role="button"
					class="button button-secondary epsilon-review-button"><?php echo esc_html( $this->messages['no_rate'] ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Store the user's response to the notice.
	 *
	 * @return void
	 */
	public function ajax(): void {

		check_ajax_referer( 'epsilon-review', 'security' );

		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( -1, 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified by check_ajax_referer() above.
		if ( isset( $_POST['epsilon-review'] ) ) {
			$state = 'already-rated';
		} elseif ( isset( $_POST['decline'] ) ) {
			$state = 'declined';
		} else {
			// Closed: snooze until the next stage.
			$stage = $this->value();
			$state = null === $stage ? '' : (string) $stage;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		update_user_meta( get_current_user_id(), self::USER_META_KEY, $state );

		wp_die( 'ok' );
	}

	/**
	 * Print the notice's click handlers.
	 *
	 * @return void
	 */
	public function ajax_script(): void {

		if ( ! $this->is_notice_screen() ) {
			return;
		}

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

				function dismiss( answer ) {
					var body = new URLSearchParams();
					body.append( 'action', 'clc_epsilon_review' );
					body.append( 'security', nonce );

					if ( answer ) {
						body.append( answer, '1' );
					}

					if ( notice.parentNode ) {
						notice.parentNode.removeChild( notice );
					}

					window.fetch( ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: body.toString()
					} );
				}

				Array.prototype.forEach.call(
					notice.querySelectorAll( '.epsilon-review-button' ),
					function ( button ) {
						button.addEventListener( 'click', function ( event ) {
							var id = button.getAttribute( 'id' );

							// The review link opens in a new tab; only the others stay put.
							if ( 'epsilon-rate' !== id ) {
								event.preventDefault();
							}

							dismiss( 'epsilon-no-rate' === id ? 'decline' : 'epsilon-review' );
						} );
					}
				);

				notice.addEventListener( 'click', function ( event ) {
					if ( event.target.classList.contains( 'notice-dismiss' ) ) {
						dismiss( '' );
					}
				} );
			}() );
		</script>
		<?php
	}
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EGWS_Settings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_egws_send_test', [ $this, 'handle_test_email' ] );
		add_action( 'admin_post_egws_clear_logs', [ $this, 'handle_clear_logs' ] );
		add_action( 'admin_notices', [ $this, 'page_notices' ] );
	}

	// -------------------------------------------------------------------------
	// Notices
	// -------------------------------------------------------------------------

	public function page_notices(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'settings_page_eternal-gws-smtp' !== $screen->id ) {
			return;
		}

		$result = $_GET['egws_test'] ?? '';
		if ( 'success' === $result ) {
			echo '<div class="notice notice-success egws-notice is-dismissible"><p>'
				. esc_html__( 'Test email sent successfully. Check your inbox.', 'eternal-gws-smtp' )
				. '</p></div>';
		} elseif ( 'fail' === $result ) {
			echo '<div class="notice notice-error egws-notice is-dismissible"><p>'
				. esc_html__( 'Test email failed. See the log below for the error details.', 'eternal-gws-smtp' )
				. '</p></div>';
		}

		if ( ! empty( $_GET['egws_logs_cleared'] ) ) {
			echo '<div class="notice notice-success egws-notice is-dismissible"><p>'
				. esc_html__( 'Mail logs cleared.', 'eternal-gws-smtp' )
				. '</p></div>';
		}
	}

	// -------------------------------------------------------------------------
	// Menu & settings registration
	// -------------------------------------------------------------------------

	public function add_menu(): void {
		add_options_page(
			__( 'Google Workspace SMTP', 'eternal-gws-smtp' ),
			__( 'GWS SMTP', 'eternal-gws-smtp' ),
			'manage_options',
			'eternal-gws-smtp',
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		register_setting( 'egws_settings_group', EGWS_OPTION_KEY, [ $this, 'sanitize' ] );
	}

	public function sanitize( array $input ): array {
		$existing = get_option( EGWS_OPTION_KEY, [] );

		$output = [
			'from_email' => sanitize_email( $input['from_email'] ?? '' ),
			'from_name'  => sanitize_text_field( $input['from_name'] ?? '' ),
			'username'   => sanitize_email( $input['username'] ?? '' ),
			'port'       => in_array( (int) ( $input['port'] ?? 587 ), [ 587, 465 ], true ) ? (int) $input['port'] : 587,
			'encryption' => in_array( $input['encryption'] ?? 'tls', [ 'tls', 'ssl' ], true ) ? $input['encryption'] : 'tls',
		];

		$raw_password = $input['app_password'] ?? '';
		if ( ! empty( $raw_password ) ) {
			$output['app_password'] = EGWS_Crypto::encrypt( $raw_password );
		} else {
			$output['app_password'] = $existing['app_password'] ?? '';
		}

		return $output;
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_eternal-gws-smtp' !== $hook ) {
			return;
		}
		wp_enqueue_style(
			'egws-admin',
			EGWS_PLUGIN_URL . 'assets/admin.css',
			[],
			EGWS_VERSION
		);
	}

	// -------------------------------------------------------------------------
	// Settings page
	// -------------------------------------------------------------------------

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$opts       = get_option( EGWS_OPTION_KEY, [] );
		$from_email = $opts['from_email'] ?? '';
		$from_name  = $opts['from_name'] ?? '';
		$username   = $opts['username'] ?? '';
		$port       = $opts['port'] ?? 587;
		$encryption = $opts['encryption'] ?? 'tls';
		$has_pass   = ! empty( $opts['app_password'] );
		$logs       = EGWS_Logger::get_all();
		?>
		<div class="wrap egws-wrap">
			<h1><?php esc_html_e( 'Google Workspace SMTP', 'eternal-gws-smtp' ); ?></h1>

			<?php settings_errors( 'egws_settings_group' ); ?>

			<!-- SMTP Configuration -->
			<div class="egws-card">
				<h2><?php esc_html_e( 'SMTP Configuration', 'eternal-gws-smtp' ); ?></h2>
				<form method="post" action="options.php">
					<?php settings_fields( 'egws_settings_group' ); ?>

					<table class="form-table" role="presentation">

						<tr>
							<th scope="row">
								<label for="egws_from_name"><?php esc_html_e( 'From Name', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<input type="text" id="egws_from_name"
									name="<?php echo esc_attr( EGWS_OPTION_KEY ); ?>[from_name]"
									value="<?php echo esc_attr( $from_name ); ?>"
									class="regular-text" placeholder="Your Business Name" />
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="egws_from_email"><?php esc_html_e( 'From Email', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<input type="email" id="egws_from_email"
									name="<?php echo esc_attr( EGWS_OPTION_KEY ); ?>[from_email]"
									value="<?php echo esc_attr( $from_email ); ?>"
									class="regular-text" placeholder="info@yourdomain.com" />
								<p class="description"><?php esc_html_e( 'Must be a valid Google Workspace email on your domain.', 'eternal-gws-smtp' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="egws_username"><?php esc_html_e( 'SMTP Username', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<input type="email" id="egws_username"
									name="<?php echo esc_attr( EGWS_OPTION_KEY ); ?>[username]"
									value="<?php echo esc_attr( $username ); ?>"
									class="regular-text" placeholder="info@yourdomain.com" />
								<p class="description"><?php esc_html_e( 'Usually the same as From Email.', 'eternal-gws-smtp' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="egws_app_password"><?php esc_html_e( 'App Password', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<input type="password" id="egws_app_password"
									name="<?php echo esc_attr( EGWS_OPTION_KEY ); ?>[app_password]"
									value="" class="regular-text" autocomplete="new-password"
									placeholder="<?php echo $has_pass ? esc_attr__( 'Leave blank to keep existing', 'eternal-gws-smtp' ) : 'xxxx xxxx xxxx xxxx'; ?>" />
								<?php if ( $has_pass ) : ?>
									<p class="description egws-status-ok"><?php esc_html_e( 'A password is saved. Enter a new one to replace it.', 'eternal-gws-smtp' ); ?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( '16-character App Password from Google Account → Security → App passwords.', 'eternal-gws-smtp' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="egws_port"><?php esc_html_e( 'SMTP Port', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<select id="egws_port" name="<?php echo esc_attr( EGWS_OPTION_KEY ); ?>[port]">
									<option value="587" <?php selected( $port, 587 ); ?>>587 — STARTTLS (recommended)</option>
									<option value="465" <?php selected( $port, 465 ); ?>>465 — SSL/TLS</option>
								</select>
							</td>
						</tr>

						<tr>
							<th scope="row">
								<label for="egws_encryption"><?php esc_html_e( 'Encryption', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<select id="egws_encryption" name="<?php echo esc_attr( EGWS_OPTION_KEY ); ?>[encryption]">
									<option value="tls" <?php selected( $encryption, 'tls' ); ?>>TLS / STARTTLS</option>
									<option value="ssl" <?php selected( $encryption, 'ssl' ); ?>>SSL</option>
								</select>
							</td>
						</tr>

					</table>

					<?php submit_button( __( 'Save Settings', 'eternal-gws-smtp' ) ); ?>
				</form>

				<!-- Setup Instructions (below save button, inside the config card) -->
				<div class="egws-instructions">
					<h3><?php esc_html_e( 'How to set up a Google Workspace App Password', 'eternal-gws-smtp' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Sign in to the Google Workspace account you want to send from (e.g. info@yourdomain.com).', 'eternal-gws-smtp' ); ?></li>
						<li>
							<?php esc_html_e( 'Go to', 'eternal-gws-smtp' ); ?>
							<strong><?php esc_html_e( 'Google Account → Security → 2-Step Verification', 'eternal-gws-smtp' ); ?></strong>
							<?php esc_html_e( 'and make sure it is turned on.', 'eternal-gws-smtp' ); ?>
						</li>
						<li>
							<?php esc_html_e( 'In the same Security page, find', 'eternal-gws-smtp' ); ?>
							<strong><?php esc_html_e( 'App passwords', 'eternal-gws-smtp' ); ?></strong>
							<?php esc_html_e( '(it only appears after 2-Step Verification is enabled).', 'eternal-gws-smtp' ); ?>
						</li>
						<li><?php esc_html_e( 'Click "Select app" → choose Mail. Click "Select device" → choose Other, type "WordPress". Click Generate.', 'eternal-gws-smtp' ); ?></li>
						<li><?php esc_html_e( 'Copy the 16-character code shown. Paste it into the App Password field above and save.', 'eternal-gws-smtp' ); ?></li>
						<li><?php esc_html_e( 'Use the Send Test Email section below to confirm everything is working.', 'eternal-gws-smtp' ); ?></li>
					</ol>
					<div class="egws-info-row">
						<span><strong><?php esc_html_e( 'SMTP Host:', 'eternal-gws-smtp' ); ?></strong> smtp.gmail.com</span>
						<span><strong><?php esc_html_e( 'SPF / DKIM / DMARC:', 'eternal-gws-smtp' ); ?></strong> <?php esc_html_e( 'Already configured by Google Workspace — no extra DNS changes needed.', 'eternal-gws-smtp' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Test Email -->
			<?php if ( $has_pass && ! empty( $from_email ) ) : ?>
			<div class="egws-card egws-test-card">
				<h2><?php esc_html_e( 'Send Test Email', 'eternal-gws-smtp' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Sends a real email through your configured SMTP credentials. The result is saved to the log below.', 'eternal-gws-smtp' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="egws_send_test" />
					<?php wp_nonce_field( 'egws_test_email', 'egws_test_nonce' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row">
								<label for="egws_test_to"><?php esc_html_e( 'Send To', 'eternal-gws-smtp' ); ?></label>
							</th>
							<td>
								<input type="email" id="egws_test_to" name="egws_test_to"
									class="regular-text" placeholder="you@example.com" required />
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Send Test Email', 'eternal-gws-smtp' ), 'secondary' ); ?>
				</form>
			</div>
			<?php endif; ?>

			<!-- Mail Log -->
			<div class="egws-card egws-log-card">
				<div class="egws-log-header">
					<h2><?php esc_html_e( 'Mail Log', 'eternal-gws-smtp' ); ?></h2>
					<?php if ( ! empty( $logs ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="egws-clear-form">
						<input type="hidden" name="action" value="egws_clear_logs" />
						<?php wp_nonce_field( 'egws_clear_logs', 'egws_clear_nonce' ); ?>
						<?php submit_button( __( 'Clear Logs', 'eternal-gws-smtp' ), 'delete small', 'submit', false ); ?>
					</form>
					<?php endif; ?>
				</div>
				<p class="description"><?php printf( esc_html__( 'Last %d emails sent through this plugin. Both system emails and test emails are recorded here.', 'eternal-gws-smtp' ), EGWS_Logger::MAX_LOGS ); ?></p>

				<?php if ( empty( $logs ) ) : ?>
					<p class="egws-empty"><?php esc_html_e( 'No emails logged yet.', 'eternal-gws-smtp' ); ?></p>
				<?php else : ?>
					<table class="widefat striped egws-log-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Time', 'eternal-gws-smtp' ); ?></th>
								<th><?php esc_html_e( 'Type', 'eternal-gws-smtp' ); ?></th>
								<th><?php esc_html_e( 'Recipient', 'eternal-gws-smtp' ); ?></th>
								<th><?php esc_html_e( 'Status', 'eternal-gws-smtp' ); ?></th>
								<th><?php esc_html_e( 'Error', 'eternal-gws-smtp' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td class="egws-col-time"><?php echo esc_html( $log['time'] ?? '—' ); ?></td>
								<td>
									<span class="egws-badge egws-badge-<?php echo esc_attr( $log['type'] ?? 'system' ); ?>">
										<?php echo esc_html( ucfirst( $log['type'] ?? 'system' ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( $log['to'] ?? '—' ); ?></td>
								<td>
									<?php if ( 'success' === ( $log['status'] ?? '' ) ) : ?>
										<span class="egws-status egws-status-ok">&#10003; <?php esc_html_e( 'Sent', 'eternal-gws-smtp' ); ?></span>
									<?php else : ?>
										<span class="egws-status egws-status-fail">&#10007; <?php esc_html_e( 'Failed', 'eternal-gws-smtp' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="egws-col-error">
									<?php if ( ! empty( $log['error'] ) ) : ?>
										<code><?php echo esc_html( $log['error'] ); ?></code>
									<?php else : ?>
										<span class="egws-muted">—</span>
									<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Form handlers
	// -------------------------------------------------------------------------

	public function handle_test_email(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'eternal-gws-smtp' ) );
		}

		check_admin_referer( 'egws_test_email', 'egws_test_nonce' );

		$to = sanitize_email( $_POST['egws_test_to'] ?? '' );
		if ( ! is_email( $to ) ) {
			wp_die( esc_html__( 'Invalid email address.', 'eternal-gws-smtp' ) );
		}

		$opts      = get_option( EGWS_OPTION_KEY, [] );
		$from_name = $opts['from_name'] ?? get_bloginfo( 'name' );

		EGWS_Logger::$context = 'test';

		$sent = wp_mail(
			$to,
			sprintf( __( 'Test email from %s', 'eternal-gws-smtp' ), get_bloginfo( 'name' ) ),
			sprintf(
				__( "This is a test email sent via Google Workspace SMTP.\n\nIf you received this, your SMTP configuration is working correctly.\n\nSent from: %s", 'eternal-gws-smtp' ),
				$from_name
			)
		);

		// Reset context in case the mailer hook didn't fire (e.g. no credentials configured).
		EGWS_Logger::$context = 'system';

		wp_safe_redirect( add_query_arg(
			[ 'page' => 'eternal-gws-smtp', 'egws_test' => $sent ? 'success' : 'fail' ],
			admin_url( 'options-general.php' )
		) );
		exit;
	}

	public function handle_clear_logs(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'eternal-gws-smtp' ) );
		}

		check_admin_referer( 'egws_clear_logs', 'egws_clear_nonce' );

		EGWS_Logger::clear();

		wp_safe_redirect( add_query_arg(
			[ 'page' => 'eternal-gws-smtp', 'egws_logs_cleared' => '1' ],
			admin_url( 'options-general.php' )
		) );
		exit;
	}
}

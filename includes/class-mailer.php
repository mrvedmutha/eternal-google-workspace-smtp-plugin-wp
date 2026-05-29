<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EGWS_Mailer {

	public function __construct() {
		add_action( 'phpmailer_init', [ $this, 'configure' ] );
		add_filter( 'wp_mail_from', [ $this, 'set_from_email' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'set_from_name' ] );
		add_action( 'wp_mail_failed', [ $this, 'log_failure' ] );
	}

	public function configure( PHPMailer\PHPMailer\PHPMailer $mailer ): void {
		$opts = get_option( EGWS_OPTION_KEY, [] );

		if ( empty( $opts['username'] ) || empty( $opts['app_password'] ) ) {
			return;
		}

		$password = EGWS_Crypto::decrypt( $opts['app_password'] );
		if ( empty( $password ) ) {
			return;
		}

		$port       = (int) ( $opts['port'] ?? 587 );
		$encryption = $opts['encryption'] ?? 'tls';

		$mailer->isSMTP();
		$mailer->Host       = 'smtp.gmail.com';
		$mailer->SMTPAuth   = true;
		$mailer->Username   = $opts['username'];
		$mailer->Password   = $password;
		$mailer->SMTPSecure = ( 'ssl' === $encryption )
			? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
			: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
		$mailer->Port = $port;

		// Fires after a successful PHPMailer send (mutually exclusive with wp_mail_failed).
		$mailer->action_function = static function ( bool $ok, array $to ): void {
			if ( ! $ok ) {
				return; // wp_mail_failed covers this path.
			}
			$recipient = ! empty( $to ) ? $to[0][0] : '';
			EGWS_Logger::add( [
				'type'    => EGWS_Logger::$context,
				'to'      => $recipient,
				'status'  => 'success',
				'error'   => '',
			] );
			EGWS_Logger::$context = 'system';
		};
	}

	/**
	 * Fires when wp_mail() catches a PHPMailer exception.
	 * The WP_Error message contains the raw PHPMailer error string.
	 */
	public function log_failure( WP_Error $error ): void {
		$data    = $error->get_error_data();
		$to_list = $data['to'] ?? [];
		$to      = is_array( $to_list ) ? implode( ', ', $to_list ) : (string) $to_list;
		$message = implode( ' | ', $error->get_error_messages() );

		EGWS_Logger::add( [
			'type'   => EGWS_Logger::$context,
			'to'     => $to,
			'status' => 'failed',
			'error'  => $message,
		] );
		EGWS_Logger::$context = 'system';
	}

	public function set_from_email( string $email ): string {
		$opts       = get_option( EGWS_OPTION_KEY, [] );
		$from_email = $opts['from_email'] ?? '';
		return is_email( $from_email ) ? $from_email : $email;
	}

	public function set_from_name( string $name ): string {
		$opts      = get_option( EGWS_OPTION_KEY, [] );
		$from_name = $opts['from_name'] ?? '';
		return ! empty( $from_name ) ? $from_name : $name;
	}
}

<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EGWS_Mailer {

	public function __construct() {
		add_action( 'phpmailer_init', [ $this, 'configure' ] );
		add_filter( 'wp_mail_from', [ $this, 'set_from_email' ] );
		add_filter( 'wp_mail_from_name', [ $this, 'set_from_name' ] );
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
		$mailer->SMTPSecure = ( $encryption === 'ssl' )
			? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
			: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
		$mailer->Port       = $port;
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

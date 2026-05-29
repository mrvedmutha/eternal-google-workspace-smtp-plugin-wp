<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EGWS_Logger {

	const OPTION_KEY = 'egws_mail_logs';
	const MAX_LOGS   = 50;

	/** Set to 'test' before a test send, reset to 'system' after. */
	public static string $context = 'system';

	public static function add( array $entry ): void {
		$logs = get_option( self::OPTION_KEY, [] );
		array_unshift( $logs, array_merge( [ 'time' => current_time( 'mysql' ) ], $entry ) );
		update_option( self::OPTION_KEY, array_slice( $logs, 0, self::MAX_LOGS ), false );
	}

	public static function get_all(): array {
		return get_option( self::OPTION_KEY, [] );
	}

	public static function clear(): void {
		delete_option( self::OPTION_KEY );
	}
}

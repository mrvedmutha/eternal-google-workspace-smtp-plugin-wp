<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EGWS_Crypto {

	private static function get_key(): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'egws-fallback-key-change-me';
		return substr( hash( 'sha256', $salt ), 0, 32 );
	}

	public static function encrypt( string $plaintext ): string {
		if ( empty( $plaintext ) ) {
			return '';
		}
		$iv     = openssl_random_pseudo_bytes( 16 );
		$cipher = openssl_encrypt( $plaintext, 'AES-256-CBC', self::get_key(), 0, $iv );
		return base64_encode( $iv . $cipher );
	}

	public static function decrypt( string $encoded ): string {
		if ( empty( $encoded ) ) {
			return '';
		}
		$raw    = base64_decode( $encoded );
		$iv     = substr( $raw, 0, 16 );
		$cipher = substr( $raw, 16 );
		$plain  = openssl_decrypt( $cipher, 'AES-256-CBC', self::get_key(), 0, $iv );
		return $plain === false ? '' : $plain;
	}
}

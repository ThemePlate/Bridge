<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

class Helpers {

	public const DEFAULT_NAMEPATH = 'bridge';

	public const HTTP_METHODS = array(
		'GET',
		'POST',
		'PUT',
		'PATCH',
		'DELETE',
	);


	public static function prepare_pathname( string $value ): string {

		$value = str_replace( '\\', '/', $value );

		return trim( $value, '/ ' );

	}


	public static function prepare_header( string $value ): string {

		$value = self::prepare_pathname( $value );
		$value = preg_replace( '/[^\w\d]+/', ' ', $value );
		$value = ucwords( strtolower( trim( $value ) ) );

		return str_replace( ' ', '-', $value );

	}


	public static function header_key( string $value ): string {

		$value = self::prepare_header( $value );

		if ( '' === $value ) {
			return '';
		}

		$header = strtoupper( $value );
		$header = str_replace( '-', '_', $header );

		return 'HTTP_' . $header;

	}


	public static function header_valid( string $value ): bool {

		$header = self::header_key( $value );

		if ( ! $header ) {
			return true;
		}

		return isset( $_SERVER[ $header ] ) && $_SERVER[ $header ];

	}


	public static function caller_path(): string {

		$traced = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		return dirname( $traced[1]['file'] ) . DIRECTORY_SEPARATOR;

	}


	public static function valid_nonce( string $value ): bool {

		return isset( $_SERVER['HTTP_TPB_NONCE'] ) && wp_verify_nonce( $_SERVER['HTTP_TPB_NONCE'], $value );

	}


	public static function dynamic_match( string $pattern, string $route ): ?array {

		$pattern_parts = explode( '/', $pattern );
		$route_parts   = explode( '/', $route );

		if ( count( $pattern_parts ) !== count( $route_parts ) ) {
			return null;
		}

		$params = array();
		$count  = count( $pattern_parts );

		for ( $i = 0; $i < $count; $i++ ) {
			$pattern_part = $pattern_parts[ $i ];
			$route_part   = $route_parts[ $i ];

			if ( preg_match( '/\[([^\]]+)\]/', $pattern_part, $matches ) ) {
				$params[ $matches[1] ] = $route_part;
			} elseif ( $pattern_part !== $route_part ) {
				return null;
			}
		}

		return $params;

	}

}

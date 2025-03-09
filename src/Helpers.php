<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

class Helpers {

	public const DEFAULT_NAMEPATH = 'bridge';

	public const HTTP_METHODS = [
		'GET',
		'POST',
		'PUT',
		'PATCH',
		'DELETE',
	];


	public static function prepare_pathname( string $value ): string {

		$value = str_replace( '\\', '/', $value );

		return trim( $value, '/ ' );

	}


	public static function prepare_header( string $value ): string {

		$value = self::prepare_pathname( $value );
		$value = preg_replace( '/[^\w\d]+/', ' ', $value );
		$value = ucwords( strtolower( trim( (string) $value ) ) );

		return str_replace( ' ', '-', $value );

	}


	public static function prepare_extension( string $value ): string {

		$value = trim( $value, '. ' );

		if ( '' !== $value ) {
			$value .= '.';
		}

		return '.' . $value . 'php';

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

		if ( '' === $header ) {
			return true;
		}

		return isset( $_SERVER[ $header ] ) && $_SERVER[ $header ];

	}


	public static function caller_path(): string {

		$traced = debug_backtrace(); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		// @phpstan-ignore-next-line
		return dirname( $traced[1]['file'] ) . DIRECTORY_SEPARATOR;

	}


	public static function valid_nonce( string $value ): bool {

		return isset( $_SERVER['HTTP_TPB_NONCE'] ) && wp_verify_nonce( $_SERVER['HTTP_TPB_NONCE'], $value );

	}


	public static function valid_route( string $value, string $with_prefix = '' ): bool {

		$path = wp_parse_url( $value, PHP_URL_PATH );

		if ( false === $path || null === $path ) {
			return false;
		}

		$clean = self::prepare_pathname( $path );
		$parts = explode( '/', $clean );

		if ( '' !== $with_prefix && $parts[0] !== $with_prefix ) {
			return false;
		}

		$valid_parts = array_filter(
			$parts,
			function ( $part ): bool {
				if ( '' === $part || '[]' === $part ) {
					return false;
				}

				$open_count  = substr_count( $part, '[' );
				$close_count = substr_count( $part, ']' );

				if ( $open_count !== $close_count ) {
					return false;
				}

				return ! ( $open_count && ! preg_match( '/^[^\[\]]*\[[^\[\]]+\][^\[\]]*$/', $part ) );
			}
		);

		return count( $parts ) === count( $valid_parts );

	}


	/**
	 * @return array<string, string> | null
	 */
	public static function dynamic_match( string $pattern, string $route ): ?array {

		$pattern_parts = explode( '/', $pattern );
		$route_parts   = explode( '/', $route );

		if ( count( $pattern_parts ) !== count( $route_parts ) ) {
			return null;
		}

		$params = [];
		$count  = count( $pattern_parts );

		for ( $i = 0; $i < $count; $i++ ) {
			$pattern_part = $pattern_parts[ $i ];
			$route_part   = $route_parts[ $i ];

			if ( preg_match( '/\[([^\]]+)\]/', $pattern_part, $matches ) ) {
				$param_name = $matches[1];

				$pattern_regex = preg_quote( $pattern_part, '/' );
				$pattern_regex = str_replace( '\[' . $param_name . '\]', '(.*)', $pattern_regex );

				if ( preg_match( '/^' . $pattern_regex . '$/', $route_part, $value_matches ) ) {
					$params[ $param_name ] = $value_matches[1];
				} else {
					$params[ $param_name ] = $route_part;
				}
			} elseif ( $pattern_part !== $route_part ) {
				return null;
			}
		}

		return $params;

	}

}

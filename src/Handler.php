<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

class Handler {

	public readonly string $identifier;
	protected array $handles = array();


	public function __construct( string $identifier ) {

		$this->identifier = Helpers::prepare_header( $identifier );

	}


	public function handle( string $method, callable $action ): void {

		$this->handles[ $method ] = $action;

	}


	public function header_key(): string {

		if ( ! $this->identifier ) {
			return '';
		}

		return Helpers::header_key( $this->identifier );

	}


	public function is_valid(): bool {

		$header = $this->header_key();

		if ( ! $header ) {
			return true;
		}

		return isset( $_SERVER[ $header ] ) && $_SERVER[ $header ];

	}


	public function execute( string $method, array $params ): bool {

		if ( ! $this->is_valid() ) {
			return false;
		}

		if ( empty( $this->handles[ $method ] ) ) {
			if ( empty( $this->handles['*'] ) ) {
				return false;
			}

			$method = '*';
		}

		return call_user_func_array( $this->handles[ $method ], array( $params ) );

	}

}

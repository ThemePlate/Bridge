<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

class Handler {

	public readonly string $identifier;

	/**
	 * @var array<string, callable>
	 */
	protected array $handles = [];


	public function __construct( string $identifier ) {

		$this->identifier = Helpers::prepare_header( $identifier );

	}


	public function handle( string $method, callable $action ): void {

		$this->handles[ $method ] = $action;

	}

	/**
	 * @param array<string, string> $params
	 */
	public function execute( string $method, array $params ): bool {

		if ( ! Helpers::header_valid( $this->identifier ) ) {
			return false;
		}

		if ( empty( $this->handles[ $method ] ) ) {
			if ( empty( $this->handles['*'] ) ) {
				return false;
			}

			$method = '*';
		}

		$callback = $this->handles[ $method ];

		return $callback( $params );

	}

}

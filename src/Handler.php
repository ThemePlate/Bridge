<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

class Handler {

	public readonly ?Validator $validator;

	/**
	 * @var array<string, callable>
	 */
	protected array $handles = [];


	public function __construct( ?Validator $validator = null ) {

		$this->validator = $validator;

	}


	public function handle( string $method, callable $action ): void {

		$this->handles[ $method ] = $action;

	}

	/**
	 * @param array<string, string> $params
	 */
	public function execute( string $method, array $params ): bool {

		$validator = $this->validator;

		if ( $validator instanceof Validator && ! $validator( $params['REQUEST_ROUTE'], $method ) ) {
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

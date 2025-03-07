<?php

declare(strict_types=1);

namespace Tests;

use stdClass;
use ThemePlate\Bridge\Handler;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase {
	public function test_execute_registered_method(): void {
		$method  = 'name';
		$params  = array( 1, 'two' );
		$handler = new Handler( 'test' );

		$handler->handle(
			$method,
			function ( $p ) use ( $params ): true {
				$this->assertSame( $params, $p );

				return true;
			}
		);

		$_SERVER[ $handler->header_key() ] = true;

		$this->assertTrue( $handler->execute( $method, $params ) );
	}

	public function test_execute_returns_false_if_method_not_registered(): void {
		$this->assertFalse( ( new Handler( 'identifier' ) )->execute( 'method', array() ) );
	}

	public function test_execute_return_on_empty_identifier(): void {
		$handler = new Handler( '' );

		$handler->handle(
			'OPTION',
			function (): true {
				return true;
			}
		);

		$this->assertTrue( $handler->execute( 'OPTION', array() ) );
		$this->assertArrayNotHasKey( $handler->header_key(), $_SERVER );
		$this->assertNotEmpty( $_SERVER );
	}

	public function test_handle_multiple_methods(): void {
		$handles = array(
			'method1' => array( true, array( false, null ) ),
			'method2' => array( false, array( new stdClass() ) ),
		);
		$handler = new Handler( 'Custom-Request' );

		foreach ( $handles as $method => $data ) {
			$handler->handle(
				$method,
				function ( $p ) use ( $data ): bool {
					$this->assertSame( $data[1], $p );

					return $data[0];
				}
			);

			$_SERVER[ $handler->header_key() ] = true;

			$result = $handler->execute( $method, $data[1] );

			if ( $data[0] ) {
				$this->assertTrue( $result );
			} else {
				$this->assertFalse( $result );
			}
		}
	}

	public function test_handle_wildcard(): void {
		$handler  = new Handler( 'test' );
		$callback = function (): true {
			return true;
		};

		$handler->handle( '*', $callback );
		$handler->handle( 'GET', $callback );

		$_SERVER[ $handler->header_key() ] = true;

		$this->assertTrue( $handler->execute( 'GET', array() ) );
		$this->assertTrue( $handler->execute( 'OPTIONS', array() ) );
		$this->assertTrue( $handler->execute( 'RANDOM', array() ) );
	}
}

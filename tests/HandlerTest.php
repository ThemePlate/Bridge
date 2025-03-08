<?php

declare(strict_types=1);

namespace Tests;

use stdClass;
use ThemePlate\Bridge\Handler;
use ThemePlate\Bridge\Helpers;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase {
	public function test_execute_registered_method(): void {
		$method  = 'name';
		$params  = [
			'first'  => '1',
			'second' => 'two',
		];
		$handler = new Handler( 'test' );

		$handler->handle(
			$method,
			function ( $p ) use ( $params ): true {
				$this->assertSame( $params, $p );

				return true;
			}
		);

		$_SERVER[ Helpers::header_key( $handler->identifier ) ] = true;

		$this->assertTrue( $handler->execute( $method, $params ) );
	}

	public function test_execute_returns_false_if_method_not_registered(): void {
		$this->assertFalse( ( new Handler( 'identifier' ) )->execute( 'method', [] ) );
	}

	public function test_execute_return_on_empty_identifier(): void {
		$handler = new Handler( '' );

		$handler->handle( 'OPTION', fn(): true => true );
		$this->assertTrue( $handler->execute( 'OPTION', [] ) );
		$this->assertArrayNotHasKey( Helpers::header_key( $handler->identifier ), $_SERVER );
		$this->assertNotEmpty( $_SERVER );
	}

	public function test_handle_multiple_methods(): void {
		$handles = [
			'method1' => [
				true,
				[
					'first'  => 'false',
					'second' => '',
				],
			],
			'method2' => [ false, [ 'first' => stdClass::class ] ],
		];
		$handler = new Handler( 'Custom-Request' );

		foreach ( $handles as $method => $data ) {
			$handler->handle(
				$method,
				function ( $p ) use ( $data ): bool {
					$this->assertSame( $data[1], $p );

					return $data[0];
				}
			);

			$_SERVER[ Helpers::header_key( $handler->identifier ) ] = true;

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
		$callback = fn(): true => true;

		$handler->handle( '*', $callback );
		$handler->handle( 'GET', $callback );

		$_SERVER[ Helpers::header_key( $handler->identifier ) ] = true;

		$this->assertTrue( $handler->execute( 'GET', [] ) );
		$this->assertTrue( $handler->execute( 'OPTIONS', [] ) );
		$this->assertTrue( $handler->execute( 'RANDOM', [] ) );
	}
}

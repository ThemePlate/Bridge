<?php

declare(strict_types=1);

namespace Tests;

use stdClass;
use ThemePlate\Bridge\Handler;
use ThemePlate\Bridge\Helpers;
use ThemePlate\Bridge\Validator;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase {
	public function test_execute_registered_method(): void {
		$method  = 'name';
		$params  = [
			'first'  => '1',
			'second' => 'two',
		];
		$handler = new Handler(
			'test',
			new class() implements Validator {
				public function __invoke( string ...$segments ): bool {
					return Helpers::header_valid( 'test' );
				}
			}
		);

		$handler->handle(
			$method,
			function ( string $second, string $first ) use ( $params ): true {
				$this->assertSame(
					$params,
					[
						'first'  => $first,
						'second' => $second,
					]
				);

				return true;
			}
		);

		$_SERVER[ Helpers::header_key( 'test' ) ] = true;

		$this->assertTrue( $handler->execute( $method, $params ) );
	}

	public function test_execute_returns_false_if_method_not_registered(): void {
		$this->assertFalse( ( new Handler( 'test' ) )->execute( 'method', [] ) );
	}

	public function test_execute_return_on_empty_identifier(): void {
		$handler = new Handler( 'test' );

		$handler->handle( 'OPTION', fn(): true => true );
		$this->assertTrue( $handler->execute( 'OPTION', [] ) );
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
			'method2' => [
				false,
				[
					'first' => stdClass::class,
				],
			],
		];
		$handler = new Handler(
			'test',
			new class() implements Validator {
				public function __invoke( string ...$segments ): bool {
					return Helpers::header_valid( 'Custom-Request' );
				}
			}
		);

		foreach ( $handles as $method => $data ) {
			$handler->handle(
				$method,
				function ( string ...$p ) use ( $data ): bool {
					$this->assertSame( $data[1], $p );

					return $data[0];
				}
			);

			$_SERVER[ Helpers::header_key( 'Custom-Request' ) ] = true;

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

		$this->assertTrue( $handler->execute( 'GET', [] ) );
		$this->assertTrue( $handler->execute( 'OPTIONS', [] ) );
		$this->assertTrue( $handler->execute( 'RANDOM', [] ) );
	}
}

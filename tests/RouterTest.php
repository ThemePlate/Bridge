<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use ThemePlate\Bridge\Handler;
use ThemePlate\Bridge\Helpers;
use ThemePlate\Bridge\Loader;
use ThemePlate\Bridge\Router;
use ThemePlate\Bridge\Validator;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;
use function Brain\Monkey\Functions\expect;

final class RouterTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		setUp();
	}

	protected function tearDown(): void {
		tearDown();
		parent::tearDown();
	}

	public static function for_prefix(): array {
		return [
			'empty' => [ '', Helpers::DEFAULT_NAMEPATH ],
			'root'  => [ '/', Helpers::DEFAULT_NAMEPATH ],
		];
	}

	#[DataProvider( 'for_prefix' )]
	public function test_prefix( string $prefix, string $expected ): void {
		$this->assertSame( $expected, ( new Router( $prefix ) )->prefix );
	}

	protected function stub_wp_parse_url( int $count = 1 ): void {
		expect( 'wp_parse_url' )->times( $count )->andReturnUsing(
			fn( ...$args ): mixed => call_user_func_array( 'parse_url', $args )
		);
	}

	public static function for_init(): array {
		defined( 'EP_ROOT' ) || define( 'EP_ROOT', 64 );

		return [
			'known'   => [ true ],
			'unknown' => [ false ],
		];
	}

	#[DataProvider( 'for_init' )]
	public function test_init( bool $wanted ): void {
		$prefix = 'test';
		$router = new Router( $prefix );

		$_SERVER['REQUEST_URI'] = $wanted ? $prefix : 'unknown';

		expect( 'add_rewrite_endpoint' )->once()->with( $prefix, EP_ROOT );

		$this->stub_wp_parse_url();
		$router->init();
		$this->assertSame( $wanted ? 10 : false, has_action( 'wp', $router->route( ... ) ) );
	}

	public static function for_is_valid(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'base' => [ 'test', true ],
			'sub' => [ 'test/this', true ],
			'slashed' => [ '/test/this/', true ],
			'extras' => [ '//test//this', false ],
			'deep' => [ '/test/this/please// ', true ],
			'unknown' => [ 'tester', false ],
			'empty' => [ '', false ],
			'root' => [ '/', false ],
			'dynamic' => [ 'test/[this]', true ],
			'dynamic empty' => [ 'test/[]', false ],
			'dynamic deep' => [ 'test/[this]/[that]', true ],
			'dynamic deep empty' => [ 'test/[this]/[]', false ],
			'no closing bracket' => [ 'test/[this', false ],
			'no opening bracket' => [ 'test/this]', false ],
			'multiple opening' => [ 'test/[[this]', false ],
			'multiple closing' => [ 'test/[that]]', false ],
			'improper opening' => [ 'test/[this[that]', false ],
			'improper closing' => [ 'test/[this]that]', false ],
			'wrong brackets' => [ 'test/]this/[that', false ],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_is_valid' )]
	public function test_is_valid( string $path, bool $is_valid ): void {
		$this->stub_wp_parse_url();

		$result = ( new Router( 'test' ) )->is_valid( $path );

		if ( $is_valid ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	public static function for_add_route(): array {
		$values = self::for_is_valid();

		$values['known'] = $values['unknown'];

		$values['known'][1] = true;

		unset( $values['unknown'] );

		return $values;
	}

	#[DataProvider( 'for_add_route' )]
	public function test_add_route( string $path, bool $is_valid ): void {
		$this->stub_wp_parse_url();

		$result = ( new Router( 'test' ) )->add( $path, new Handler( 'test' ) );

		if ( $is_valid ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	#[DataProvider( 'for_init' )]
	public function test_dispatch( bool $is_known ): void {
		$this->stub_wp_parse_url();

		$p_id_r  = 'test';
		$router  = new Router( $p_id_r );
		$handler = new Handler( $p_id_r );

		$handler->handle( 'POST', fn(): true => true );

		$router->add( $p_id_r, $handler );

		if ( $is_known ) {
			$this->assertTrue( $router->dispatch( $p_id_r, 'POST' ) );
		} else {
			$this->assertFalse( $router->dispatch( $p_id_r, 'GET' ) );
		}
	}

	public function test_dispatch_invalid(): void {
		$router = new Router( 'test' );

		$this->assertFalse( $router->dispatch( ' ', '' ) );
		$this->assertFalse( $router->dispatch( '', ' ' ) );
		$this->assertFalse( $router->dispatch( ' ', ' ' ) );
	}

	public function test_validator(): void {
		$this->stub_wp_parse_url( 2 );

		$validator = new class() implements Validator {
			public function __invoke( string $route, string $method ): bool {
				return match ( $route ) {
					'test' => 'GET' === $method,
					default => false,
				};
			}
		};

		$router = new Router( 'test', $validator );

		$router->any( 'test', fn(): true => true );
		$router->any( 'unknown', fn(): true => true );

		$this->assertTrue( $router->dispatch( 'test', 'GET' ) );
		$this->assertFalse( $router->dispatch( 'test', 'POST' ) );
		$this->assertFalse( $router->dispatch( 'test', 'DELETE' ) );
		$this->assertFalse( $router->dispatch( 'unknown', 'GET' ) );
	}

	#[DataProvider( 'for_init' )]
	public function test_map( bool $is_known ): void {
		$this->stub_wp_parse_url();

		$route  = 'tester';
		$router = new Router( 'test' );
		$cbf    = fn(): true => true;

		if ( $is_known ) {
			$router->map( $route, $cbf, 'OPTION' );
		} else {
			$router->map( $route, $cbf );
		}

		if ( $is_known ) {
			$this->assertTrue( $router->dispatch( $route, 'OPTION' ) );
		} else {
			foreach ( Helpers::HTTP_METHODS as $method ) {
				$this->assertTrue( $router->dispatch( $route, $method ) );
			}
		}
	}

	public function test_map_invalid(): void {
		$this->stub_wp_parse_url( 2 );

		$router = new Router( 'test' );

		$this->assertFalse( $router->map( 'test', fn (): true => true, '' ) );
		$this->assertFalse( $router->map( 'test', fn (): true => true, ' ' ) );
	}

	#[DataProvider( 'for_init' )]
	public function test_basic_routes( bool $is_known ): void {
		$route  = 'tester';
		$router = new Router( 'test' );

		if ( $is_known ) {
			$this->stub_wp_parse_url( count( Helpers::HTTP_METHODS ) );

			foreach ( Helpers::HTTP_METHODS as $method ) {
				$router->$method( $route, fn(): true => true );
			}
		}

		$return = $router->dispatch( $route, $is_known ? $method : 'OPTION' );

		if ( $is_known ) {
			$this->assertTrue( $return );
		} else {
			$this->assertFalse( $return );
		}
	}

	public function test_load(): void {
		$this->stub_wp_parse_url( 7 );
		expect( 'path_is_absolute' )->once()->andReturn( true );

		$router = new Router( 'test' );

		$this->assertTrue(
			$router->load(
				new Loader( __DIR__ . '/templates' ),
				new Handler( 'TPBT' )
			)
		);

		$data = [
			'call.txt'    => [ 'call', false ],
			'error.php'   => [ 'error', false ],
			'hello.php'   => [ 'hello', true ],
			'goodbye.php' => [ 'goodbye', true ],
			'deep/fn.php' => [ 'deep/fn', true ],
		];

		$_SERVER['HTTP_TPBT'] = true;

		foreach ( $data as $expected ) {
			[$route, $is_valid] = $expected;

			if ( $is_valid ) {
				$this->assertTrue( $router->dispatch( $route, 'GET' ) );
			} else {
				$this->assertFalse( $router->dispatch( $route, 'GET' ) );
			}
		}
	}

	public static function for_load_invalid(): array {
		return [
			'nonexistent' => [ '../nonexistent' ],
			'call.txt'    => [ 'templates/call.txt' ],
		];
	}

	#[DataProvider( 'for_load_invalid' )]
	public function test_load_invalid( string $location ): void {
		expect( 'path_is_absolute' )->once()->andReturn( true );

		$router = new Router( 'test' );

		$this->assertFalse(
			$router->load(
				new Loader( $location ),
				new Handler( 'TPBT' )
			)
		);
	}

	public function test_load_suffixed(): void {
		$this->stub_wp_parse_url( 3 );
		expect( 'path_is_absolute' )->once()->andReturn( true );

		$router = new Router( 'test' );

		$this->assertTrue(
			$router->load(
				new Loader( __DIR__ . '/templates', 'action' ),
				new Handler( 'TPBT' )
			)
		);

		$data = [
			'call.txt'             => [ 'call', false ],
			'error.php'            => [ 'error', false ],
			'hello.php'            => [ 'hello', false ],
			'goodbye.php'          => [ 'goodbye', false ],
			'deep/fn.php'          => [ 'deep/fn', false ],
			'deep/only.action.php' => [ 'deep/only', true ],
			'do.action.php'        => [ 'do', true ],
			'test.action.php'      => [ 'test', true ],
		];

		$_SERVER['HTTP_TPBT'] = true;

		foreach ( $data as $expected ) {
			[$route, $is_valid] = $expected;

			if ( $is_valid ) {
				$this->assertTrue( $router->dispatch( $route, 'GET' ) );
			} else {
				$this->assertFalse( $router->dispatch( $route, 'GET' ) );
			}
		}
	}

	#[DataProviderExternal( HelpersTest::class, 'for_dynamic_match' )]
	public function test_dynamic_routes( string $pattern, string $route, ?array $expected ): void {
		$this->stub_wp_parse_url();

		$router   = new Router( 'test' );
		$captured = null;
		$handler  = $this->createMock( Handler::class );

		$handler->method( 'execute' )
			->willReturnCallback(
				function ( $method, $params ) use ( &$captured ): true {
					$captured = $params;
					return true;
				}
			);

		$router->add( $pattern, $handler );

		$result = $router->dispatch( $route, 'GET' );

		if ( null === $expected ) {
			$this->assertFalse( $result );
		} else {
			$this->assertTrue( $result );

			foreach ( $expected as $key => $value ) {
				$this->assertSame( $value, $captured[ $key ] );
			}
		}
	}
}

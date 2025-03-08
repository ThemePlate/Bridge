<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use ThemePlate\Bridge\Handler;
use ThemePlate\Bridge\Helpers;
use ThemePlate\Bridge\Loader;
use ThemePlate\Bridge\Router;
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
		return array(
			'empty' => array( '', Helpers::DEFAULT_NAMEPATH ),
			'root'  => array( '/', Helpers::DEFAULT_NAMEPATH ),
		);
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

		return array(
			'known'   => array( true ),
			'unknown' => array( false ),
		);
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
		return array(
			'base' => array( 'test', true ),
			'sub' => array( 'test/this', true ),
			'slashed' => array( '/test/this/', true ),
			'extras' => array( '//test//this', false ),
			'deep' => array( '/test/this/please// ', true ),
			'unknown' => array( 'tester', false ),
			'empty' => array( '', false ),
			'root' => array( '/', false ),
			'dynamic' => array( 'test/[this]', true ),
			'dynamic empty' => array( 'test/[]', false ),
			'dynamic deep' => array( 'test/[this]/[that]', true ),
			'dynamic deep empty' => array( 'test/[this]/[]', false ),
			'no closing bracket' => array( 'test/[this', false ),
			'no opening bracket' => array( 'test/this]', false ),
			'multiple opening' => array( 'test/[[this]', false ),
			'multiple closing' => array( 'test/[that]]', false ),
			'improper opening' => array( 'test/[this[that]', false ),
			'improper closing' => array( 'test/[this]that]', false ),
			'wrong brackets' => array( 'test/]this/[that', false ),
		);
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
			$_SERVER[ Helpers::header_key( $p_id_r ) ] = true;

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

		$data = array(
			'call.txt'    => array( 'call', false ),
			'error.php'   => array( 'error', false ),
			'hello.php'   => array( 'hello', true ),
			'goodbye.php' => array( 'goodbye', true ),
			'deep/fn.php' => array( 'deep/fn', true ),
		);

		$_SERVER['HTTP_TPBT'] = true;

		foreach ( $data as $expected ) {
			list( $route, $is_valid ) = $expected;

			if ( $is_valid ) {
				$this->assertTrue( $router->dispatch( $route, 'GET' ) );
			} else {
				$this->assertFalse( $router->dispatch( $route, 'GET' ) );
			}
		}
	}

	public static function for_load_invalid(): array {
		return array(
			'nonexistent' => array( '../nonexistent' ),
			'call.txt'    => array( 'templates/call.txt' ),
		);
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

		$data = array(
			'call.txt'             => array( 'call', false ),
			'error.php'            => array( 'error', false ),
			'hello.php'            => array( 'hello', false ),
			'goodbye.php'          => array( 'goodbye', false ),
			'deep/fn.php'          => array( 'deep/fn', false ),
			'deep/only.action.php' => array( 'deep/only', true ),
			'do.action.php'        => array( 'do', true ),
			'test.action.php'      => array( 'test', true ),
		);

		$_SERVER['HTTP_TPBT'] = true;

		foreach ( $data as $expected ) {
			list( $route, $is_valid ) = $expected;

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

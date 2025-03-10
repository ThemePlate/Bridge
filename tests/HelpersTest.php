<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ThemePlate\Bridge\Helpers;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\expect;

final class HelpersTest extends TestCase {
	public static function stub_wp_parse_url( int $count = 1 ): void {
		expect( 'wp_parse_url' )->times( $count )->andReturnUsing(
			fn( ...$args ): mixed => call_user_func_array( 'parse_url', $args )
		);
	}

	public static function for_prepare_pathname(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'correct' => [ 'test', 'test' ],
			'prefixed' => [ '/test', 'test' ],
			'suffixed' => [ 'test/', 'test' ],
			'windows' => [ 'C:\folder\test', 'C:/folder/test' ],
			'extras' => [ '/test// ', 'test' ],
			'deep'  => [ ' //deep/test', 'deep/test' ],
			'empty' => [ '', '' ],
			'root'  => [ '/', '' ],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_prepare_pathname' )]
	public function test_prepare_pathname( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::prepare_pathname( $value ) );
	}

	public static function for_prepare_header(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'correct' => [ 'test', 'Test' ],
			'prefixed' => [ '/test', 'Test' ],
			'suffixed' => [ 'test/', 'Test' ],
			'windows' => [ 'C:\folder\test', 'C-Folder-Test' ],
			'extras' => [ '/test// ', 'Test' ],
			'deep'  => [ ' //deep/test', 'Deep-Test' ],
			'empty' => [ '', '' ],
			'root'  => [ '/', '' ],
			'spaced' => [ ' test this', 'Test-This' ],
			'others' => [ '~test!@this#$%out^&*+', 'Test-This-Out' ],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_prepare_header' )]
	public function test_prepare_header( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::prepare_header( $value ) );
	}

	public static function for_prepare_extension(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'empty' => [ '', '.php' ],
			'correct' => [ 'action', '.action.php' ],
			'dashed' => [ 'action-test', '.action-test.php' ],
			'spaced'  => [ ' action test ', '.action test.php' ],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_prepare_extension' )]
	public function test_prepare_extension( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::prepare_extension( $value ) );
	}

	public static function for_header_key(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'empty' => [ '', '' ],
			'root'  => [ '/', '' ],
			'correct' => [ 'test', 'HTTP_TEST' ],
			'spaced' => [ ' test this', 'HTTP_TEST_THIS' ],
			'prefixed' => [ '/test', 'HTTP_TEST' ],
			'suffixed' => [ 'test/', 'HTTP_TEST' ],
			'windows' => [ 'C:\folder\test', 'HTTP_C_FOLDER_TEST' ],
			'extras' => [ '/test// ', 'HTTP_TEST' ],
			'deep'  => [ ' //deep/test', 'HTTP_DEEP_TEST' ],
			'others' => [ '~test!@this#$%out^&*+', 'HTTP_TEST_THIS_OUT' ],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_header_key' )]
	public function test_header_key( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::header_key( $value ) );
	}

	public static function for_header_valid(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'empty' => [ '', false, true ],
			'missing' => [ 'TESTS', false, false ],
			'found' => [ 'TESTS', true, true ],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_header_valid' )]
	public function test_header_valid( string $value, bool $is_set, bool $expected ): void {
		if ( $is_set ) {
			$_SERVER[ Helpers::header_key( $value ) ] = true;
		} else {
			unset( $_SERVER[ Helpers::header_key( $value ) ] );
		}

		$this->assertSame( $expected, Helpers::header_valid( $value ) );
	}

	public static function for_valid_route(): array {
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

	#[DataProvider( 'for_valid_route' )]
	public function test_valid_route( string $path, bool $is_valid ): void {
		$this->stub_wp_parse_url();

		$result = Helpers::valid_route( $path, 'test' );

		if ( $is_valid ) {
			$this->assertTrue( $result );
		} else {
			$this->assertFalse( $result );
		}
	}

	public static function for_dynamic_match(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return [
			'simple' => [
				'pattern' => 'user/[name]',
				'route' => 'user/john',
				'expected' => [ 'name' => 'john' ],
			],
			'multiple' => [
				'pattern' => 'page/[id]/comment/[user]',
				'route' => 'page/123/comment/john',
				'expected' => [
					'id' => '123',
					'user' => 'john',
				],
			],
			'prefixed' => [
				'pattern' => 'site-[number]',
				'route' => 'site-123',
				'expected' => [ 'number' => '123' ],
			],
			'prefix_unmatched' => [
				'pattern' => 'site-[number]',
				'route' => 'site123',
				'expected' => null,
			],
			'suffix' => [
				'pattern' => '[id]-live',
				'route' => '2-live',
				'expected' => [ 'id' => '2' ],
			],
			'suffix_unmatched' => [
				'pattern' => '[id]-live',
				'route' => '2live',
				'expected' => null,
			],
			'combined' => [
				'pattern' => 'site-[number]/reviews/[id]-live',
				'route' => 'site-123/reviews/2-live',
				'expected' => [
					'number' => '123',
					'id' => '2',
				],
			],
			'no_match' => [
				'pattern' => 'user/[name]',
				'route' => 'user/john/extra',
				'expected' => null,
			],
			'mismatch' => [
				'pattern' => 'page/[id]/comment/[user]',
				'route' => 'page/123/comment',
				'expected' => null,
			],
		];
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_dynamic_match' )]
	public function test_dynamic_match( string $pattern, string $route, ?array $expected ): void {
		$this->assertSame( $expected, Helpers::dynamic_match( $pattern, $route ) );
	}
}

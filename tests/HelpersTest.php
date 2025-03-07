<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ThemePlate\Bridge\Helpers;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase {
	public static function for_prepare_pathname(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return array(
			'correct' => array( 'test', 'test' ),
			'prefixed' => array( '/test', 'test' ),
			'suffixed' => array( 'test/', 'test' ),
			'windows' => array( 'C:\folder\test', 'C:/folder/test' ),
			'extras' => array( '/test// ', 'test' ),
			'deep'  => array( ' //deep/test', 'deep/test' ),
			'empty' => array( '', '' ),
			'root'  => array( '/', '' ),
		);
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_prepare_pathname' )]
	public function test_prepare_pathname( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::prepare_pathname( $value ) );
	}

	public static function for_prepare_header(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return array(
			'correct' => array( 'test', 'Test' ),
			'prefixed' => array( '/test', 'Test' ),
			'suffixed' => array( 'test/', 'Test' ),
			'windows' => array( 'C:\folder\test', 'C-Folder-Test' ),
			'extras' => array( '/test// ', 'Test' ),
			'deep'  => array( ' //deep/test', 'Deep-Test' ),
			'empty' => array( '', '' ),
			'root'  => array( '/', '' ),
			'spaced' => array( ' test this', 'Test-This' ),
			'others' => array( '~test!@this#$%out^&*+', 'Test-This-Out' ),
		);
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_prepare_header' )]
	public function test_prepare_header( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::prepare_header( $value ) );
	}

	public static function for_header_key(): array {
		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		return array(
			'empty' => array( '', '' ),
			'root'  => array( '/', '' ),
			'correct' => array( 'test', 'HTTP_TEST' ),
			'spaced' => array( ' test this', 'HTTP_TEST_THIS' ),
			'prefixed' => array( '/test', 'HTTP_TEST' ),
			'suffixed' => array( 'test/', 'HTTP_TEST' ),
			'windows' => array( 'C:\folder\test', 'HTTP_C_FOLDER_TEST' ),
			'extras' => array( '/test// ', 'HTTP_TEST' ),
			'deep'  => array( ' //deep/test', 'HTTP_DEEP_TEST' ),
			'others' => array( '~test!@this#$%out^&*+', 'HTTP_TEST_THIS_OUT' ),
		);
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	#[DataProvider( 'for_header_key' )]
	public function test_header_key( string $value, string $expected ): void {
		$this->assertSame( $expected, Helpers::header_key( $value ) );
	}
}

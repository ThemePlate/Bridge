<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use ThemePlate\Bridge\Helpers;
use ThemePlate\Bridge\Loader;
use PHPUnit\Framework\TestCase;
use function Brain\Monkey\Functions\expect;

final class LoaderTest extends TestCase {
	public static function for_location(): array {
		return array(
			'null'  => array( null, Helpers::DEFAULT_NAMEPATH ),
			'empty' => array( '', Helpers::DEFAULT_NAMEPATH ),
			'root'  => array( '/', Helpers::DEFAULT_NAMEPATH ),
		);
	}

	#[DataProvider( 'for_location' )]
	public function test_location( ?string $location, string $expected ): void {
		expect( 'path_is_absolute' )->once()->andReturn( false );

		$loader = null === $location ? new Loader() : new Loader( $location );

		$this->assertSame( __DIR__ . DIRECTORY_SEPARATOR . $expected, $loader->location );
	}

	public static function for_is_valid_template(): array {
		return array(
			array( 'HelpersTest', true ),
			array( 'RouterTest', true ),
			array( 'nonexistent', false ),
		);
	}

	#[DataProvider( 'for_is_valid_template' )]
	public function test_is_valid_template_absolute( string $name, bool $is_valid ): void {
		expect( 'path_is_absolute' )->once()->andReturn( true );

		if ( $is_valid ) {
			$this->assertTrue( ( new Loader( __DIR__ ) )->is_valid( $name ) );
		} else {
			$this->assertFalse( ( new Loader( __DIR__ ) )->is_valid( $name ) );
		}
	}

	#[DataProvider( 'for_is_valid_template' )]
	public function test_is_valid_template_relative( string $name, bool $is_valid ): void {
		expect( 'path_is_absolute' )->once()->andReturn( false );

		if ( $is_valid ) {
			$this->assertTrue( ( new Loader( '../tests' ) )->is_valid( $name ) );
		} else {
			$this->assertFalse( ( new Loader( '../tests' ) )->is_valid( $name ) );
		}
	}
}

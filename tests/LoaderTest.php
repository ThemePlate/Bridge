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
		return [
			'empty' => [ '', Helpers::DEFAULT_NAMEPATH ],
			'root'  => [ '/', Helpers::DEFAULT_NAMEPATH ],
		];
	}

	#[DataProvider( 'for_location' )]
	public function test_location( ?string $location, string $expected ): void {
		expect( 'path_is_absolute' )->once()->andReturn( false );

		$this->assertSame( __DIR__ . DIRECTORY_SEPARATOR . $expected, ( new Loader( $location ) )->location );
	}

	public static function for_is_valid_template(): array {
		return [
			[ 'HelpersTest', true ],
			[ 'RouterTest', true ],
			[ 'nonexistent', false ],
			[ '', false ],
			[ ' ', false ],
		];
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

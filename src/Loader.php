<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

class Loader {

	public readonly string $location;
	public readonly string $extension;


	public function __construct( string $location, string $suffix = '' ) {

		if ( ! path_is_absolute( $location ) ) {
			$location = Helpers::prepare_pathname( $location );

			if ( '' === $location ) {
				$location = Helpers::DEFAULT_NAMEPATH;
			}

			$location = Helpers::caller_path() . $location;
		}

		$this->location  = $location;
		$this->extension = Helpers::prepare_extension( $suffix );

	}


	protected function file_path( string $name ): string {

		$path = realpath( $this->location . DIRECTORY_SEPARATOR . $name . $this->extension );

		if ( ! $path ) {
			return '';
		}

		return $path;

	}


	public function load( array $data ): bool {

		if ( empty( $data['REQUEST_ROUTE'] ) ) {
			return false;
		}

		$template = $data['REQUEST_ROUTE'];

		if ( ! $this->is_valid( $template ) ) {
			return false;
		}

		return ( function (): bool {
			$params = func_get_arg( 0 );

			return (bool) include $this->file_path( func_get_arg( 1 ) );
		} )( $data, $template );

	}


	public function is_valid( string $template ): bool {

		$template = Helpers::prepare_pathname( $template );

		if ( '' === $template ) {
			return false;
		}

		return file_exists( $this->file_path( $template ) );

	}

}

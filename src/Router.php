<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use WP;

class Router {

	public readonly string $prefix;

	public readonly ?Validator $validator;

	/**
	 * @var array<string, Handler>
	 */
	protected array $routes = [];


	public function __construct( string $prefix, ?Validator $validator = null ) {

		$prefix = Helpers::prepare_pathname( $prefix );

		if ( '' === $prefix ) {
			$prefix = Helpers::DEFAULT_NAMEPATH;
		}

		$this->prefix    = $prefix;
		$this->validator = $validator;

	}


	public function init(): void {

		add_rewrite_endpoint( $this->prefix, EP_ROOT );

		if ( Helpers::valid_route( $_SERVER['REQUEST_URI'], $this->prefix ) ) {
			add_action( 'wp', $this->route( ... ) );
		}

	}


	public function route( WP $wp ): void {

		if ( isset( $wp->query_vars[ $this->prefix ] ) ) {
			$route  = Helpers::prepare_pathname( $wp->query_vars[ $this->prefix ] );
			$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

			if ( '' !== $route && $this->dispatch( $route, $method ) ) {
				die();
			}
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

	}


	public function add( string $route, Handler $handler ): bool {

		$route = Helpers::prepare_pathname( $route );

		if ( ! Helpers::valid_route( $route ) ) {
			return false;
		}

		$this->routes[ $route ] = $handler;

		return true;

	}


	public function dispatch( string $route, string $method ): bool {

		$route  = Helpers::prepare_pathname( $route );
		$method = strtoupper( trim( $method ) );

		if ( '' === $route || '' === $method ) {
			return false;
		}

		$base_params = [
			'REQUEST_METHOD' => $method,
			'REQUEST_ROUTE'  => $route,
			...$_REQUEST, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		];

		$handler = $this->routes[ $route ] ?? null;

		if ( null !== $handler ) {
			return $handler->execute( $method, $base_params );
		}

		foreach ( $this->routes as $pattern => $handler ) {
			$dynamic_params = Helpers::dynamic_match( $pattern, $route );

			if ( null !== $dynamic_params ) {
				$params = array_merge( $base_params, $dynamic_params );

				return $handler->execute( $method, $params );
			}
		}

		return false;

	}


	public function map( string $route, callable $callback, ?string $method = null ): bool {

		$route = Helpers::prepare_pathname( $route );

		if ( ! Helpers::valid_route( $route ) ) {
			return false;
		}

		if ( null !== $method ) {
			$method = strtoupper( trim( $method ) );

			if ( '' === $method ) {
				return false;
			}
		}

		if ( empty( $this->routes[ $route ] ) ) {
			$this->routes[ $route ] = new Handler( $this->validator );
		}

		$handler = $this->routes[ $route ];
		$methods = $method ? [ $method ] : Helpers::HTTP_METHODS;

		foreach ( $methods as $method ) {
			$handler->handle( $method, $callback );
		}

		return true;

	}


	public function get( string $route, callable $callback ): bool {

		return $this->map( $route, $callback, 'GET' );

	}


	public function post( string $route, callable $callback ): bool {

		return $this->map( $route, $callback, 'POST' );

	}


	public function put( string $route, callable $callback ): bool {

		return $this->map( $route, $callback, 'PUT' );

	}


	public function patch( string $route, callable $callback ): bool {

		return $this->map( $route, $callback, 'PATCH' );

	}


	public function delete( string $route, callable $callback ): bool {

		return $this->map( $route, $callback, 'DELETE' );

	}


	public function any( string $route, callable $callback ): bool {

		return $this->map( $route, $callback, '*' );

	}


	public function load( Loader $loader, ?Validator $validator = null ): bool {

		if ( ! is_dir( $loader->location ) || ! is_readable( $loader->location ) ) {
			return false;
		}

		$iterator = new RecursiveDirectoryIterator( $loader->location );

		foreach ( new RecursiveIteratorIterator( $iterator ) as $item ) {
			if ( ! $item->isFile() || ! str_ends_with( (string) $item->getFilename(), $loader->extension ) ) {
				continue;
			}

			$path = str_replace(
				[
					$loader->location . DIRECTORY_SEPARATOR,
					$loader->extension,
				],
				'',
				$item->getPathname()
			);

			$handler = new Handler( $validator ?? $this->validator );

			$this->add( $path, $handler );
			$handler->handle( '*', $loader->load( ... ) );
		}

		return true;

	}

}

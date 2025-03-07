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
	protected array $routes = array();


	public function __construct( string $prefix ) {

		$prefix = Helpers::prepare_pathname( $prefix );

		if ( '' === $prefix ) {
			$prefix = Helpers::DEFAULT_NAMEPATH;
		}

		$this->prefix = $prefix;

	}


	public function init(): void {

		add_rewrite_endpoint( $this->prefix, EP_ROOT );

		if ( $this->is_valid( $_SERVER['REQUEST_URI'] ) ) {
			add_action( 'wp', array( $this, 'route' ) );
		}

	}


	public function is_valid( string $endpoint, bool $with_prefix = true ): bool {

		$path  = wp_parse_url( $endpoint, PHP_URL_PATH );
		$clean = Helpers::prepare_pathname( $path );
		$parts = explode( '/', $clean );

		if ( $with_prefix && $parts[0] !== $this->prefix ) {
			return false;
		}

		return count( $parts ) === count( array_filter( $parts ) );

	}


	public function route( WP $wp ): void {

		if (
			isset( $wp->query_vars[ $this->prefix ] ) &&
			Helpers::valid_nonce( $this->prefix )
		) {
			$route = Helpers::prepare_pathname( $wp->query_vars[ $this->prefix ] );

			if ( $this->dispatch( $route, $_SERVER['REQUEST_METHOD'] ) ) {
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

		if ( ! $this->is_valid( $route, false ) ) {
			return false;
		}

		$this->routes[ $route ] = $handler;

		return true;

	}


	public function dispatch( string $route, string $method ): bool {

		if ( empty( $this->routes[ $route ] ) ) {
			return false;
		}

		$params = array(
			'REQUEST_METHOD' => $method,
			'REQUEST_ROUTE'  => $route,
			...$_REQUEST, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		);

		return call_user_func_array(
			array( $this->routes[ $route ], 'execute' ),
			array( $method, $params )
		);

	}


	public function map( string $route, callable $callback, ?string $method = null ): bool {

		$route = Helpers::prepare_pathname( $route );

		if ( ! $this->is_valid( $route, false ) ) {
			return false;
		}

		if ( empty( $this->routes[ $route ] ) ) {
			$this->routes[ $route ] = new Handler( $this->prefix );
		}

		$handler = $this->routes[ $route ];

		$methods = $method ? array( $method ) : Helpers::HTTP_METHODS;

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


	public function load( Loader $loader, Handler $handler ): void {

		$iterator = new RecursiveDirectoryIterator( $loader->location );

		foreach ( new RecursiveIteratorIterator( $iterator ) as $item ) {
			if ( ! $item->isFile() || $item->getExtension() !== 'php' ) {
				continue;
			}

			$path = str_replace(
				array(
					$loader->location . DIRECTORY_SEPARATOR,
					'.php',
				),
				'',
				$item->getPathname()
			);

			$handler->handle( '*', array( $loader, 'load' ) );

			$this->add( $path, $handler );
		}

	}

}

# ThemePlate Bridge

## Usage

```php
$router = new ThemePlate\Bridge\Router( 'test' );

// `<WP_HOME>/test/route`
$router->map(
	'route',
	function (): bool {
		// ...
	}
);

// `<WP_HOME>/test/[path]`
$router->any(
	'[path]',
	function ( string $path ): bool {
		// $path = [path]
	}
);

// `<WP_HOME>/test/[filename]`
$router->load( new Loader( __DIR__ . '/templates' ) );
// only handles .php files
$router->load( new Loader( __DIR__ . '/templates', 'action' ) );
// only handles .action.php files

add_action( 'init', array( $router, 'init' ) );
```

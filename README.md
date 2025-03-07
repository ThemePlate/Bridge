# ThemePlate Bridge

## Usage

```php
$router = new ThemePlate\Bridge\Router( 'test' );

// `<WP_HOME>/test/route`
$router->map(
	'route',
	function () {
		// ...
	},
	'GET'
);

// `<WP_HOME>/test/[filename]`
$router->load(
	new Loader( __DIR__ . '/templates' ),
	new Handler( 'identifier' )
);
// only handles .php files

add_action( 'init', array( $router, 'init' ) );
```

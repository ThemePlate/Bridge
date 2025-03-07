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

add_action( 'init', array( $router, 'init' ) );
```

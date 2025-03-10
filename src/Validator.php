<?php

/**
 * @package ThemePlate
 */

declare(strict_types=1);

namespace ThemePlate\Bridge;

interface Validator {

	public function __invoke( string ...$segments ): bool;

}

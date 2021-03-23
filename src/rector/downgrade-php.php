<?php

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function ( ContainerConfigurator $container_configurator ): void {
	$parameters = $container_configurator->parameters();

	// Set the paths to refactor.
	$parameters->set( Option::PATHS, '' );

	// Set PHP version to downgrade to.
	$parameters->set( Option::PHP_VERSION_FEATURES, PhpVersion::PHP_53 );

	// Disable caching.
	$parameters->set( Option::ENABLE_CACHE, false );
};

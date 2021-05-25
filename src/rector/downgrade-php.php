<?php

use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function ( ContainerConfigurator $container_configurator ): void {
	$parameters = $container_configurator->parameters();

	// Set downgrading rules
	$parameters->set( Option::SETS, array( DowngradeSetList::PHP_80, DowngradeSetList::PHP_74, DowngradeSetList::PHP_73,
		DowngradeSetList::PHP_72, DowngradeSetList::PHP_71, DowngradeSetList::PHP_70, DowngradeSetList::PHP_53 ) );

	// Set the paths to refactor.
	$parameters->set( Option::PATHS, json_decode( getenv( 'dws_autloadedFiles' ), true ) );

	// Set composer autoloader.
	$parameters->set( Option::BOOTSTRAP_FILES, array( getenv( 'dws_vendorDir' ) . '/autoload.php' ) );

	// Set PHP version to downgrade to.
	$parameters->set( Option::PHP_VERSION_FEATURES, PhpVersion::PHP_53 );

	// Disable caching.
	$parameters->set( Option::ENABLE_CACHE, false );
};

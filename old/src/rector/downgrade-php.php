<?php

use Dotenv\Dotenv;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\DowngradeSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$dotenv = Dotenv::createMutable( '/tmp/' );
$dotenv->load();

return static function ( ContainerConfigurator $container_configurator ): void {
	$parameters = $container_configurator->parameters();

	// Set downgrading rules
	foreach ( array( DowngradeSetList::PHP_80, DowngradeSetList::PHP_74, DowngradeSetList::PHP_73,
		DowngradeSetList::PHP_72, DowngradeSetList::PHP_71, DowngradeSetList::PHP_70, DowngradeSetList::PHP_53 ) as $downgrade_set ) {
		$container_configurator->import( $downgrade_set );
	}

	// Set the paths to refactor.
	$parameters->set( Option::PATHS, json_decode( $_ENV['dws_autoloadedFiles'], true, 512, JSON_THROW_ON_ERROR ) );

	// Set composer autoloader.
	$parameters->set( Option::BOOTSTRAP_FILES, array( $_ENV['dws_vendorDir'] . '/autoload.php' ) );

	// Set PHP version to downgrade to.
	$parameters->set( Option::PHP_VERSION_FEATURES, PhpVersion::PHP_53 );

	// Disable caching.
	$parameters->set( Option::ENABLE_CACHE, false );
};

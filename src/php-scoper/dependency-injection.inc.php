<?php declare( strict_types = 1 );

use Dotenv\Dotenv;
use Isolated\Symfony\Component\Finder\Finder;

$dotenv = Dotenv::createMutable( '/tmp/' );
$dotenv->load();

return array(
	/**
	 * By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	 * directory. You can however define which files should be scoped by defining a collection of Finders in the
	 * following configuration key.
	 *
	 * For more see: https://github.com/humbug/php-scoper#finders-and-paths
	 */
	'finders'         => array(
		Finder::create()->files()->in( 'vendor/laravel/serializable-closure' )->name( array( '*.php', 'LICENSE.md', 'composer.json' ) ),
		Finder::create()->files()->in( 'vendor/php-di/phpdoc-reader' )->name( array( '*.php', 'LICENSE', 'composer.json' ) ),
		Finder::create()->files()->in( 'vendor/php-di/invoker' )->name( array( '*.php', 'LICENSE', 'composer.json' ) ),
		Finder::create()->files()->in( 'vendor/php-di/php-di' )->name( array( '*.php', 'LICENSE', 'composer.json' ) ),
	),

	/**
	 * When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	 * original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	 * support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	 * heart contents.
	 *
	 * For more see: https://github.com/humbug/php-scoper#patchers
	 */
	'patchers'        => array(),

	'files-whitelist' => array(
		$_ENV['dws_vendorDir'] . '/php-di/php-di/src/Compiler/Template.php',
	),
);

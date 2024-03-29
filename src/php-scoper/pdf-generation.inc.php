<?php declare( strict_types = 1 );

use Isolated\Symfony\Component\Finder\Finder;

return array(
	/**
	 * By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	 * directory. You can however define which files should be scoped by defining a collection of Finders in the
	 * following configuration key.
	 *
	 * For more see: https://github.com/humbug/php-scoper#finders-and-paths
	 */
	'finders'  => array(
		Finder::create()->files()->in( 'vendor/dompdf/dompdf' )->name( array( '*.php', '*.afm', '*.ttf', '*.ufm', '*.ser', '*.html', '*.css', '*.png', '*.svg', 'LICENSE.LGPL', 'composer.json' ) )->notName( 'Autoloader.php' ),
		Finder::create()->files()->in( 'vendor/phenx' )->exclude( 'tests' )->name( array( '*.php', '*.ttf', '*.map', 'LICENSE', 'COPYING', 'COPYING.GPL', 'composer.json' ) )->notName( 'autoload.php' ),
		Finder::create()->files()->in( 'vendor/sabberworm/php-css-parser' )->exclude( 'tests' )->name( array( '*.php', 'composer.json' ) )
	),

	/**
	 * When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	 * original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	 * support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	 * heart contents.
	 *
	 * For more see: https://github.com/humbug/php-scoper#patchers
	 */
	'patchers' => array(
		function ( string $file_path, string $prefix, string $content ) {
			return str_replace(
				array( '"Dompdf\\', '\'Dompdf\\', '"\\\\Dompdf\\', '\'\\\\Dompdf\\' ),
				array( "\"$prefix\\\\Dompdf\\", "'$prefix\\\\Dompdf\\", "\"\\\\$prefix\\\\Dompdf\\", "'\\\\$prefix\\\\Dompdf\\" ),
				$content
			);
		},
	),
);

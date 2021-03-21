<?php declare( strict_types = 1 );

use Isolated\Symfony\Component\Finder\Finder;

$dws_framework_components      = array( 'wp-framework-bootstrapper', 'wp-framework-helpers', 'wp-framework-foundations', 'wp-framework-utilities', 'wp-framework-core', 'wp-framework-settings', 'wp-framework-woocommerce' );
$dws_framework_component_files = array( '*.php', 'LICENSE', 'composer.json', '*.pot', '*.po', '*.mo', '*.svg' );

return array(
	/**
	 * By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
	 * directory. You can however define which files should be scoped by defining a collection of Finders in the
	 * following configuration key.
	 *
	 * For more see: https://github.com/humbug/php-scoper#finders-and-paths
	 */
	'finders'                    => array_map( function ( string $component ) use ( $dws_framework_component_files ) {
		return Finder::create()->files()->in( "vendor/deep-web-solutions/{$component}" )->exclude( 'tests' )->name( $dws_framework_component_files );
	}, $dws_framework_components ),

	/**
	 * When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
	 * original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
	 * support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
	 * heart contents.
	 *
	 * For more see: https://github.com/humbug/php-scoper#patchers
	 */
	'patchers'                   => array(
		function ( string $file_path, string $prefix, string $content ) {
			static $reference_classes = null, $reference_functions = null;

			if ( \is_null( $reference_classes ) || \is_null( $reference_functions ) ) {
				$reference_classes   = array();
				$reference_functions = array();

				$reference_files = Finder::create()->files()->in( 'vendor/deep-web-solutions/' )->name( array( 'wp-references.json', 'other-references.json' ) );
				foreach ( $reference_files as $file ) {
					$references          = json_decode( $file->getContents() );
					$reference_classes   = array_merge( $reference_classes, $references['classes'] );
					$reference_functions = array_merge( $reference_functions, $references['functions'] );
				}

				$reference_classes   = array_unique( $reference_classes );
				$reference_functions = array_unique( $reference_functions );
			}

			foreach ( $reference_functions as $function ) {
				$content = str_replace( '\\' . $prefix . '\\' . $function . '(', '\\' . $function . '(', $content );
			}
			foreach ( $reference_classes as $class ) {
				$content = str_replace( 'use ' . $prefix . '\\' . $class . ';', '', $content );
				$content = str_replace( '\\' . $prefix . '\\' . $class, '\\' . $class, $content );
			}

			return $content;
		},
	),

	'whitelist-global-functions' => false,
);

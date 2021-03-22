<?php declare( strict_types = 1 );

use Isolated\Symfony\Component\Finder\Finder;

$dws_framework_component_files = array( '*.php', 'LICENSE', 'composer.json', '*.pot', '*.po', '*.mo', '*.svg' );
$dws_framework_components      = array( 'wp-framework-bootstrapper', 'wp-framework-helpers', 'wp-framework-foundations', 'wp-framework-utilities', 'wp-framework-core', 'wp-framework-settings', 'wp-framework-woocommerce' );
foreach ( $dws_framework_components as $key => $component ) {
	if ( ! is_dir( getenv('dws_vendorDir') . "/deep-web-solutions/{$component}" ) ) {
		unset( $dws_framework_components[ $key ] );
	}
}

$dws_reference_classes   = array();
$dws_reference_functions = array();

$dws_reference_files = Finder::create()->files()->in( getenv('dws_vendorDir') . '/deep-web-solutions/' )->name( array( 'wp-references.json', 'other-references.json' ) );
foreach ( $dws_reference_files as $file ) {
	$references              = json_decode( $file->getContents(), true );
	$dws_reference_classes   = array_merge( $dws_reference_classes, $references['classes'] );
	$dws_reference_functions = array_merge( $dws_reference_functions, $references['functions'] );
}

$dws_reference_classes   = array_unique( $dws_reference_classes );
$dws_reference_functions = array_unique( $dws_reference_functions );

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
		function ( string $file_path, string $prefix, string $content ) use ( $dws_reference_functions, $dws_reference_classes ) {
			foreach ( $dws_reference_functions as $function ) {
				$content = str_replace( '\\' . $prefix . '\\' . $function . '(', '\\' . $function . '(', $content );
			}
			foreach ( $dws_reference_classes as $class ) {
				$content = str_replace( 'use ' . $prefix . '\\' . $class . ';', '', $content );
				$content = str_replace( '\\' . $prefix . '\\' . $class, '\\' . $class, $content );
			}

			return $content;
		},
	),

	'whitelist-global-functions' => false,
);

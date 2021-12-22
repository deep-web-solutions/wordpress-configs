<?php declare( strict_types = 1 );

use Isolated\Symfony\Component\Finder\Finder;

if ( false !== getenv( 'dws_textDomain' ) ) {
	$dws_plugin_language_domain = getenv( 'dws_textDomain' );
} else {
	$dws_plugin_language_domain     = str_replace( 'wp', 'dws', explode( '/', getenv( 'dws_packageName' ) )[1] );
	$dws_plugin_language_domain     = str_replace( 'wc', 'dws-wc', $dws_plugin_language_domain );
}

$dws_framework_language_domains = array( 'dws-wp-framework-bootstrapper', 'dws-wp-framework-helpers', 'dws-wp-framework-foundations', 'dws-wp-framework-utilities', 'dws-wp-framework-core', 'dws-wp-framework-settings', 'dws-wp-framework-woocommerce' );
$dws_framework_component_files  = array( '*.php', 'LICENSE', 'composer.json', '.js', '.css', '*.svg' );
$dws_framework_components       = array( 'wp-framework-bootstrapper', 'wp-framework-helpers', 'wp-framework-foundations', 'wp-framework-utilities', 'wp-framework-core', 'wp-framework-settings', 'wp-framework-woocommerce' );
foreach ( $dws_framework_components as $key => $component ) {
	if ( ! is_dir( getenv('dws_vendorDir') . "/deep-web-solutions/{$component}" ) ) {
		unset( $dws_framework_components[ $key ] );
		unset( $dws_framework_language_domains[ $key ] );
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
		return Finder::create()->files()->in( "vendor/deep-web-solutions/{$component}" )->exclude( array( 'tests', 'languages' ) )->name( $dws_framework_component_files );
	}, $dws_framework_components ),

	'expose-global-functions' => false,
	'exclude-classes'         => $dws_reference_classes,
	'exclude-functions'       => $dws_reference_functions,
);

<?php

namespace DeepWebSolutions\Config\Composer;

use Composer\Script\Event;
use DeepWebSolutions\Config\Composer\IsolateReferences\{ReferencesPopulator, ReferencesCheckerPopulator};
use PhpParser\{Lexer\Emulative, NodeTraverser, ParserFactory};
use Symfony\Component\Finder\Finder;

/**
 * Static composer actions for creating a list of used WordPress global functions and classes.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Antonius Hegyes <a.hegyes@deep-web-solutions.com>
 * @package DeepWebSolutions\WP-Config\Composer
 */
class IsolateWordPressReferences {
	/**
	 * Action for the 'post-autoload-dump' event.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   Event   $event
	 */
	public static function postAutoloadDump( Event $event ): void {
		$console_IO = $event->getIO();
		$vendorDir  = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		if ( ! $event->isDevMode() ) {
			$console_IO->write( 'Not isolating WordPress functions, due to not being in dev mode.' );
			return;
		}
		if ( getenv( 'CI' ) ) {
			$console_IO->write( 'Not isolating WordPress functions, due to environment config.' );
			return;
		}

		$wp_stubs_path = $vendorDir . '/php-stubs/wordpress-stubs/wordpress-stubs.php';
		if ( ! is_file( $wp_stubs_path ) ) {
			$console_IO->write( 'Not isolating WordPress functions, due to the WordPress stubs not being present.' );
			return;
		}

		/* @noinspection PhpIncludeInspection */
		require $vendorDir . '/autoload.php';

		$parser    = ( new ParserFactory() )->create( ParserFactory::ONLY_PHP7, new Emulative( array( 'phpVersion' => Emulative::PHP_7_4 ) ) );
		$traverser = new NodeTraverser();

		// Compile a list of all WP global functions and classes.
		$wp_classes   = array();
		$wp_functions = array();

		$wp_stubs_visitor = new ReferencesPopulator( $wp_classes, $wp_functions );

		$traverser->addVisitor( $wp_stubs_visitor );
		$traverser->traverse( $parser->parse( file_get_contents( $wp_stubs_path ) ) );
		$traverser->removeVisitor( $wp_stubs_visitor );

		// Output WP result as JSON.
		file_put_contents(
			dirname( $vendorDir ) . '/wp-stubs.json',
			json_encode( array(
				'classes'   => $wp_classes,
				'functions' => $wp_functions
			), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT )
		);

		// Cross-check with all the classes and functions used in the project files.
		$project_wp_classes   = array();
		$project_wp_functions = array();

		$project_files_visitor = new ReferencesCheckerPopulator( $wp_classes, $wp_functions, $project_wp_classes, $project_wp_functions );

		$traverser->addVisitor( $project_files_visitor );

		$project_files = Finder::create()->files()->in( dirname( $vendorDir ) )->exclude( array( 'tests', 'vendor', 'node_modules' ) )->name( '*.php' );
		foreach ( $project_files as $file ) {
			$traverser->traverse( $parser->parse( $file->getContents() ) );
		}

		// Output result as JSON.
		file_put_contents(
			dirname( $vendorDir ) . '/wp-references.json',
			json_encode( array(
				'classes'   => array_values( array_unique( $project_wp_classes, SORT_STRING ) ),
				'functions' => array_values( array_unique( $project_wp_functions, SORT_STRING ) ),
			), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT )
		);
	}
}

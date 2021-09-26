<?php

namespace DeepWebSolutions\Config\Composer;

use Composer\Script\Event;

/**
 * Static composer actions for prefixing dependencies after every update.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Antonius Hegyes <a.hegyes@deep-web-solutions.com>
 * @package DeepWebSolutions\WP-Config\Composer
 */
class PrefixDependencies {
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
			$console_IO->write( 'Not prefixing dependencies, due to not being in dev move.' );
			return;
		}
		if ( ! is_file( $vendorDir . '/bin/php-scoper' ) ) {
			$console_IO->write( 'Not prefixing dependencies, due to PHP-Scoper not being installed' );
			return;
		}

		$console_IO->write( 'Setting vendor dir as an environment variable...' );
		putenv( "dws_vendorDir={$vendorDir}" );

		$console_IO->write( 'Making sure autoloaded files exist...' );

		$composer_package = json_decode( file_get_contents( dirname( $vendorDir ) . '/composer.json' ), true );
		$autoload_files   = array_merge( $composer_package['autoload']['files'] ?? array(), $composer_package['autoload-dev']['files'] ?? array() );
		foreach ( $autoload_files as $file ) {
			$file = dirname( $vendorDir ) . DIRECTORY_SEPARATOR . $file;
			if ( ! is_file( $file ) ) {
				mkdir( dirname( $file ), 0755, true );
				touch( $file );
			}
		}

		$console_IO->write( 'Setting package name as an environment variable...' );
		putenv( "dws_packageName={$composer_package['name']}" );

		$console_IO->write( 'Setting plugin text domain as an environment variable...' );
		putenv( "dws_textDomain={$composer_package['text-domain']}" );

		$console_IO->write( 'Prefixing dependencies...' );

		$event_dispatcher = $event->getComposer()->getEventDispatcher();
		$event_dispatcher->dispatchScript( 'prefix-dependencies', $event->isDevMode() );
	}
}

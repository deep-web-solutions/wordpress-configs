<?php

namespace DeepWebSolutions\Config\Composer;

use Composer\Script\Event;

/**
 * Static composer actions for downgrading dependencies' files after every update.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Antonius Hegyes <a.hegyes@deep-web-solutions.com>
 * @package DeepWebSolutions\WP-Config\Composer
 */
class DowngradePhp {
	/**
	 * Action for the 'post-autoload-dump' event.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   Event   $event
	 *
	 * @throws \JsonException
	 */
	public static function postAutoloadDump( Event $event ): void {
		$console_IO = $event->getIO();
		$vendorDir  = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		if ( ! $event->isDevMode() ) {
			$console_IO->write( 'Not downgrading PHP, due to not being in dev move.' );
			return;
		}
		if ( ! is_file( $vendorDir . '/bin/rector' ) ) {
			$console_IO->write( 'Not downgrading PHP, due to Rector not being installed' );
			return;
		}

		$console_IO->write( 'Setting vendor dir as an environment variable...' );
		file_put_contents( '/tmp/.env', "dws_vendorDir=$vendorDir" . PHP_EOL );

		$console_IO->write( 'Collecting autoloaded files...' );

		$composer_package = json_decode( file_get_contents( dirname( $vendorDir ) . '/composer.json' ), true, 512, JSON_THROW_ON_ERROR );
		$autoload_files   = array_merge( $composer_package['autoload']['files'] ?? array(), $composer_package['autoload-dev']['files'] ?? array() );

		$console_IO->write( 'Setting autoloaded files as an environment variable...' );

		$autoload_files = json_encode( $autoload_files, JSON_THROW_ON_ERROR );
		file_put_contents( '/tmp/.env', "dws_autoloadedFiles=$autoload_files" . PHP_EOL, FILE_APPEND );

		$console_IO->write( 'Downgrading PHP...' );

		$event_dispatcher = $event->getComposer()->getEventDispatcher();
		$event_dispatcher->dispatchScript( 'downgrade-php', $event->isDevMode() );
	}
}

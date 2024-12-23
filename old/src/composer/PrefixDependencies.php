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
	 * Action for before dumping the autoloader.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   Event   $event
	 *
	 * @throws \JsonException
	 */
	public static function preAutoloadDump( Event $event ): void {
		$console_IO = $event->getIO();
		$vendorDir  = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		$console_IO->write( 'Setting vendor dir as an environment variable...' );
		file_put_contents( '/tmp/.env', 'dws_vendorDir="' . $vendorDir . '"' . PHP_EOL );

		$console_IO->write( 'Making sure autoloaded dependencies files exist...' );

		$composer_package = json_decode( file_get_contents( dirname( $vendorDir ) . '/composer.json' ), true, 512, JSON_THROW_ON_ERROR );

		$autoload_files = array_merge( $composer_package['autoload']['files'] ?? array(), $event->isDevMode() ? ( $composer_package['autoload-dev']['files'] ?? array() ) : array() );
		foreach ( $autoload_files as $file ) {
			$file = PrefixDependencies . phpdirname( $vendorDir ) . DIRECTORY_SEPARATOR . $file;
			if ( ! is_file( $file ) ) {
				if ( ! mkdir( $concurrentDirectory = dirname( $file ), 0755, true ) && ! is_dir( $concurrentDirectory ) ) {
					throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $concurrentDirectory ) );
				}
				touch( $file );
			}
		}

		$autoload_directories = array_merge( $composer_package['autoload']['classmap'] ?? array(), $event->isDevMode() ? ( $composer_package['autoload-dev']['classmap'] ?? array() ) : array() );
		foreach ( $autoload_directories as $directory ) {
			$directory = PrefixDependencies . phpdirname( $vendorDir ) . DIRECTORY_SEPARATOR . $directory;
			if ( ! is_dir( $directory ) && ! mkdir( $directory, 0755, true ) && ! is_dir( $directory ) ) {
				throw new \RuntimeException( sprintf( 'Directory "%s" was not created', $directory ) );
			}
		}
	}

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
			$console_IO->write( 'Not prefixing dependencies, due to not being in dev move.' );
			return;
		}
		if ( ! is_file( $vendorDir . '/bin/php-scoper' ) ) {
			$console_IO->write( 'Not prefixing dependencies, due to PHP-Scoper not being installed' );
			return;
		}

		$composer_package = json_decode( file_get_contents( dirname( $vendorDir ) . '/composer.json' ), true, 512, JSON_THROW_ON_ERROR );

		$console_IO->write( 'Setting package name as an environment variable...' );
		file_put_contents( '/tmp/.env', 'dws_packageName="' . $composer_package['name'] . '"' . PHP_EOL, FILE_APPEND );

		$console_IO->write( 'Setting plugin text domain as an environment variable...' );
		file_put_contents( '/tmp/.env', 'dws_textDomain="' . $composer_package['extra']['text-domain'] . '"' . PHP_EOL, FILE_APPEND );

		$console_IO->write( 'Prefixing dependencies...' );

		$event_dispatcher = $event->getComposer()->getEventDispatcher();
		$event_dispatcher->dispatchScript( 'prefix-dependencies', $event->isDevMode() );
	}
}

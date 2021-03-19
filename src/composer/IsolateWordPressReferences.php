<?php

namespace DeepWebSolutions\Config\Composer;

use Composer\Script\Event;
use PhpParser\{Node, NodeTraverser, NodeVisitorAbstract, ParserFactory};
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

		$parser    = ( new ParserFactory() )->create( ParserFactory::ONLY_PHP7 );
		$traverser = new NodeTraverser();

		// Compile a list of all WP global functions and classes.
		$wp_classes   = array();
		$wp_functions = array();

		$wp_stubs_visitor = new class( $wp_classes, $wp_functions ) extends NodeVisitorAbstract {
			private array $wp_classes;
			private array $wp_functions;

			public function __construct( array &$wp_classes, array &$wp_functions ) {
				$this->wp_classes   = &$wp_classes;
				$this->wp_functions = &$wp_functions;
			}

			public function enterNode( Node $node ) {
				if ( $node instanceof Node\Stmt\Class_ ) {
					$this->wp_classes[] = $node->name->name;
					return NodeTraverser::DONT_TRAVERSE_CHILDREN;
				} elseif ( $node instanceof Node\Stmt\Function_ ) {
					$this->wp_functions[] = $node->name->name;
				}

				return parent::enterNode( $node );
			}
		};

		$traverser->addVisitor( $wp_stubs_visitor );
		$traverser->traverse( $parser->parse( file_get_contents( $wp_stubs_path ) ) );
		$traverser->removeVisitor( $wp_stubs_visitor );

		// Output WP result as JSON.
		file_put_contents(
			dirname( $vendorDir ) . '/wp-stubs.json',
			json_encode(
				array(
					'classes'   => $wp_classes,
					'functions' => $wp_functions
				),
				JSON_PRETTY_PRINT
			)
		);

		// Cross-check with all the classes and functions used in the project files.
		$project_wp_classes   = array();
		$project_wp_functions = array();

		$project_files_visitor = new class( $wp_classes, $wp_functions, $project_wp_classes, $project_wp_functions ) extends NodeVisitorAbstract {
			private array $wp_classes;
			private array $wp_functions;

			private array $found_wp_classes;
			private array $found_wp_functions;

			public function __construct( array &$wp_classes, array &$wp_functions, array &$found_wp_classes, array &$found_wp_functions ) {
				$this->wp_classes         = &$wp_classes;
				$this->wp_functions       = &$wp_functions;
				$this->found_wp_classes   = &$found_wp_classes;
				$this->found_wp_functions = &$found_wp_functions;
			}

			public function enterNode( Node $node ) {
				if ( $node instanceof Node\Expr\FuncCall ) {
					$function_name = $node->name->parts[0];
					foreach ( $this->wp_functions as $wp_function ) {
						if ( strtolower( $function_name ) === strtolower( $wp_function ) ) {
							$this->found_wp_functions[] = $function_name;
						}
					}
				} elseif ( $node instanceof Node\Expr\New_ && 'Name' === $node->class->getType() ) {
					$class_name = $node->class->parts[0];
					foreach ( $this->wp_classes as $wp_class ) {
						if ( strtolower( $class_name ) === strtolower( $wp_class ) ) {
							$this->found_wp_classes[] = $class_name;
						}
					}
				} elseif ( $node instanceof Node\Stmt\Class_ && $node->extends ) {
					$class_name = $node->extends->parts[0];
					foreach ( $this->wp_classes as $wp_class ) {
						if ( strtolower( $class_name ) === strtolower( $wp_class ) ) {
							$this->found_wp_classes[] = $class_name;
						}
					}
				} elseif ( $node instanceof Node\Stmt\Function_ ) {
					// Check return type(s).
					$return_types = $this->type_to_string_array( $node->returnType );
					if ( ! empty( $return_types ) ) {
						foreach ( $this->wp_classes as $wp_class ) {
							foreach ( $return_types as $return_type ) {
								if ( strtolower( $return_type ) === strtolower( $wp_class ) ) {
									$this->found_wp_classes[] = $return_type;
								}
							}
						}
					}

					// Check parameters.
					foreach ( $node->getParams() as $param ) {
						$param_types = $this->type_to_string_array( $param->type );
						if ( ! empty( $param_types ) ) {
							foreach ( $this->wp_classes as $wp_class ) {
								foreach ( $param_types as $param_type ) {
									if ( strtolower( $param_type ) === strtolower( $wp_class ) ) {
										$this->found_wp_classes[] = $param_type;
									}
								}
							}
						}
					}
				}

				return parent::enterNode( $node );
			}

			protected function type_to_string_array( $type ): array {
				if ( $type instanceof Node\UnionType ) {
					return array_map( function( $value ): string {
						return $value instanceof Node\Identifier
							? $value->name
							: $value->parts[0];
					}, $type->types );
				} elseif ( $type instanceof Node\NullableType ) {
					return (array) ( $type->type instanceof Node\Identifier
						? $type->type->name
						: $type->type->parts[0] );
				} elseif ( $type instanceof Node\Name ) {
					return (array) $type->parts[0];
				}

				return array();
			}
		};
		$traverser->addVisitor( $project_files_visitor );

		$project_files = Finder::create()->files()->in( dirname( $vendorDir ) )->exclude( array( 'tests', 'vendor', 'node_modules' ) )->name( '*.php' );
		foreach ( $project_files as $file ) {
			$traverser->traverse( $parser->parse( $file->getContents() ) );
		}

		// Output result as JSON.
		file_put_contents(
			dirname( $vendorDir ) . '/wp-references.json',
			json_encode(
				array(
					'classes'   => array_values( array_unique( $project_wp_classes, SORT_STRING ) ),
					'functions' => array_values( array_unique( $project_wp_functions, SORT_STRING ) ),
				),
				JSON_PRETTY_PRINT
			)
		);
	}
}

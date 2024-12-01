<?php declare( strict_types = 1 );

namespace DeepWebSolutions\Config\Composer;

use Composer\Script\Event;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;
use Symfony\Component\Finder\Finder;

/**
 * Static Composer commands to be executed on certain script events
 * to compile a list of used WordPress functions and classes.
 */
class FindWPCoreCalls {
	/**
	 * @param   Event $event The Composer event object.
	 *
	 * @return  void
	 */
	public static function postAutoloadDump( Event $event ): void {
		$console_IO = $event->getIO();
		$vendorDir  = $event->getComposer()->getConfig()->get( 'vendor-dir' );

		if ( ! $event->isDevMode() ) {
			$console_IO->write( 'Not searching for WordPress Core calls due to not being in dev mode.' );
			return;
		}
		if ( getenv( 'CI' ) ) {
			$console_IO->write( 'Not searching for WordPress Core calls due to environment config.' );
			return;
		}

		$wp_stubs_path = $vendorDir . '/php-stubs/wordpress-stubs/wordpress-stubs.php';
		if ( ! is_file( $wp_stubs_path ) ) {
			$console_IO->write( 'Not searching for WordPress Core calls due to the stubs not being installed.' );
			return;
		}

		require_once $vendorDir . '/autoload.php';

		$parser    = new ParserFactory()->createForVersion( PhpVersion::fromComponents( 7, 2 ) ); // Minimum supported in WP6.7 is PHP7.2.
		$traverser = new NodeTraverser();

		// Compile a list of WordPress Core classes and functions.
		$wp_stubs_visitor = new _stubsNodeVisitor( $wp_classes, $wp_functions );

		$traverser->addVisitor( $wp_stubs_visitor );
		$traverser->traverse( $parser->parse( file_get_contents( $wp_stubs_path ) ) );
		$traverser->removeVisitor( $wp_stubs_visitor );

		// Cross-check with all the classes and functions used in the project files.
		$project_files_visitor = new _projectNodeVisitor( $wp_classes, $wp_functions, $project_wp_classes, $project_wp_functions );
		$traverser->addVisitor( $project_files_visitor );

		$project_files = Finder::create()->files()->in( dirname( $vendorDir ) )->exclude( array( 'tests', 'vendor', 'node_modules' ) )->name( '*.php' );
		foreach ( $project_files as $file ) {
			$traverser->traverse( $parser->parse( $file->getContents() ) );
		}

		$traverser->removeVisitor( $project_files_visitor );

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

class _stubsNodeVisitor extends NodeVisitorAbstract {
	protected array $classes;
	protected array $functions;

	public function __construct( array &$classes, array &$functions ) {
		$this->classes   = &$classes;
		$this->functions = &$functions;
	}

	/**
	 * @{inheritDoc}
	 */
	public function enterNode( Node $node ): int|null {
		if ( $node instanceof Node\Stmt\Class_ ) {
			$this->classes[] = $node->name->name;
			return NodeVisitor::DONT_TRAVERSE_CHILDREN;
		}

		if ( $node instanceof Node\Stmt\Function_ ) {
			$this->functions[] = $node->name->name;
		}

		return null;
	}
}
class _projectNodeVisitor extends _stubsNodeVisitor {
	protected array $project_classes;
	protected array $project_functions;

	public function __construct( array $classes, array $functions, array &$project_classes, array &$project_functions ) {
		parent::__construct( $classes, $functions );
		$this->project_classes   = &$project_classes;
		$this->project_functions = &$project_functions;
	}

	public function enterNode( Node $node ): int|null {
		if ( $node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod ) {
			// Check return type(s).
			$return_types = $this->type_to_string_array( $node->returnType );
			if ( ! empty( $return_types ) ) {
				foreach ( $this->classes as $class ) {
					foreach ( $return_types as $return_type ) {
						if ( strtolower( $return_type ) === strtolower( $class ) ) {
							$this->project_classes[] = $return_type;
						}
					}
				}
			}

			// Check parameters.
			foreach ( $node->getParams() as $param ) {
				$param_types = $this->type_to_string_array( $param->type );
				if ( ! empty( $param_types ) ) {
					foreach ( $this->classes as $class ) {
						foreach ( $param_types as $param_type ) {
							if ( strtolower( $param_type ) === strtolower( $class ) ) {
								$this->project_classes[] = $param_type;
							}
						}
					}
				}
			}
		} elseif ( $node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name ) {
			$function_name = $node->name->parts[0];
			foreach ( $this->functions as $function ) {
				if ( strtolower( $function_name ) === strtolower( $function ) ) {
					$this->project_functions[] = $function_name;
				}
			}
		} elseif ( $node instanceof Node\Stmt\Class_ && $node->extends ) {
			$class_name = $node->extends->parts[0];
			foreach ( $this->classes as $class ) {
				if ( strtolower( $class_name ) === strtolower( $class ) ) {
					$this->project_classes[] = $class_name;
				}
			}
		} elseif ( $node instanceof Node\Expr\New_ && 'Name' === $node->class->getType() ) {
			$class_name = $node->class->parts[0];
			foreach ( $this->classes as $class ) {
				if ( strtolower( $class_name ) === strtolower( $class ) ) {
					$this->project_classes[] = $class_name;
				}
			}
		} elseif ( $node instanceof Node\Expr\Instanceof_ || $node instanceof Node\Expr\StaticCall ) {
			if ( $node->class instanceof Node\Name ) {
				$class_name = $node->class->parts[0];
				foreach ( $this->classes as $class ) {
					if ( strtolower( $class_name ) === strtolower( $class ) ) {
						$this->project_classes[] = $class_name;
					}
				}
			}
		}

		return null;
	}

	protected function type_to_string_array( $type ): array {
		if ( $type instanceof Node\UnionType ) {
			return array_map( static function( $value ): string {
				return $value instanceof Node\Identifier
					? $value->name
					: $value->parts[0];
			}, $type->types );
		}

		if ( $type instanceof Node\NullableType ) {
			return (array) ( $type->type instanceof Node\Identifier
				? $type->type->name
				: $type->type->parts[0] );
		}

		if ( $type instanceof Node\Name ) {
			return (array) $type->parts[0];
		}

		return array();
	}
}

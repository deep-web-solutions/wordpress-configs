<?php

namespace DeepWebSolutions\Config\Composer\IsolateReferences;

use PhpParser\Node;

/**
 * Populates an array of classes and one of functions with the all the ones found.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Antonius Hegyes <a.hegyes@deep-web-solutions.com>
 * @package DeepWebSolutions\WP-Config\Composer\IsolateReferences
 */
class ReferencesCheckerPopulator extends ReferencesPopulator {
	// region FIELDS AND CONSTANTS

	/**
	 * Collection of all matching classes found.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @access  protected
	 * @var     array
	 */
	protected array $project_classes;

	/**
	 * Collection of all matching functions found.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @access  protected
	 * @var     array
	 */
	protected array $project_functions;

	// endregion

	// region MAGIC METHODS

	/**
	 * ReferencesPopulator constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array $classes              Array of classes to cross-reference.
	 * @param   array $functions            Array of functions to cross-reference.
	 * @param   array $found_classes        Array to populate with all the matching classes found.
	 * @param   array $found_functions      Array to populate with all the matching functions found.
	 */
	public function __construct( array $classes, array $functions, array &$found_classes, array &$found_functions ) {
		parent::__construct( $classes, $functions );
		$this->project_classes   = &$found_classes;
		$this->project_functions = &$found_functions;
	}

	// endregion

	// region INHERITED METHODS

	/**
	 * Called when entering a node.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   Node    $node   The current AST node being traversed.
	 *
	 * @return  int|null
	 */
	public function enterNode( Node $node ): ?int {
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

	// endregion

	// region HELPERS

	/**
	 * Transforms an AST type node into the string of its ... type.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   mixed   $type
	 *
	 * @return  string[]
	 */
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

	// endregion
}

<?php

namespace DeepWebSolutions\Config\Composer\IsolateReferences;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Populates an array of classes and one of functions with the all the ones found.
 *
 * @since   1.0.0
 * @version 1.0.0
 * @author  Antonius Hegyes <a.hegyes@deep-web-solutions.com>
 * @package DeepWebSolutions\WP-Config\Composer\IsolateReferences
 */
class ReferencesPopulator extends NodeVisitorAbstract {
	// region FIELDS AND CONSTANTS

	/**
	 * Collection of all found classes.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @access  protected
	 * @var     array
	 */
	protected array $classes;

	/**
	 * Collection of all found functions.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @access  protected
	 * @var     array
	 */
	protected array $functions;

	// endregion

	// region MAGIC METHODS

	/**
	 * ReferencesPopulator constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   array   $classes    Array to populate with all the found classes.
	 * @param   array   $functions  Array to populate with all the found functions.
	 */
	public function __construct( array &$classes, array &$functions ) {
		$this->classes   = &$classes;
		$this->functions = &$functions;
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
		if ( $node instanceof Node\Stmt\Class_ ) {
			$this->classes[] = $node->name->name;
			return NodeTraverser::DONT_TRAVERSE_CHILDREN;
		}

		if ( $node instanceof Node\Stmt\Function_ ) {
			$this->functions[] = $node->name->name;
		}

		return null;
	}

	// endregion
}

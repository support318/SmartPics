<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model;

use Uncanny_Automator_Pro\Loops\Loop\Model\Loop\Iterable_Expression\Iterable_Expression;

/**
 * Class Loop
 *
 * Represents a loop post type with title, code, status, and a parent post ID.
 * The loop post can be created, edited, or deleted in WordPress as a custom post type.
 * The iterable expression is stored in post meta.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model
 */
class Loop {

	/**
	 * The ID of the loop post.
	 *
	 * @var int|null
	 */
	protected $id = null;

	/**
	 * The title of the loop post.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * The code associated with the loop post.
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * Filters applied to the loop post.
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * The status of the loop post. Can be 'draft' or 'published'.
	 *
	 * @var string
	 */
	protected $status = 'draft';

	/**
	 * The parent post ID for hierarchical posts.
	 *
	 * @var int|null
	 */
	protected $parent = null;

	/**
	 * The Iterable_Expression object associated with the loop post.
	 *
	 * @var Iterable_Expression|null
	 */
	protected $iterable_expression = null;

	/**
	 * Constructor to initialize the Loop object.
	 *
	 * @param Iterable_Expression $iterable_expression The iterable expression object associated with this loop.
	 */
	public function __construct( Iterable_Expression $iterable_expression ) {
		$this->iterable_expression = $iterable_expression;
	}

	/**
	 * Set the ID of the loop post.
	 *
	 * @param int $id The ID of the loop post.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the ID of the loop post.
	 *
	 * @return int|null The ID of the loop post.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the title of the loop post.
	 *
	 * @param string $title The title of the loop post.
	 * @throws \InvalidArgumentException If the title is empty.
	 */
	public function set_title( $title ) {
		if ( empty( $title ) ) {
			throw new \InvalidArgumentException( 'The title cannot be empty.' );
		}
		$this->title = $title;
	}

	/**
	 * Get the title of the loop post.
	 *
	 * @return string The title of the loop post.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set the code for the loop post.
	 *
	 * @param string $code The code for the loop post.
	 */
	public function set_code( $code ) {
		$this->code = $code;
	}

	/**
	 * Get the code associated with the loop post.
	 *
	 * @return string The code associated with the loop post.
	 */
	public function get_code() {
		return $this->code;
	}

	/**
	 * Set the status of the loop post.
	 *
	 * @param string $status The status of the loop post. Must be either 'draft' or 'publish'.
	 * @throws \InvalidArgumentException If the status is not 'draft' or 'publish'.
	 */
	public function set_status( $status ) {
		if ( ! in_array( $status, array( 'draft', 'publish' ) ) ) {
			throw new \InvalidArgumentException( 'The status must be either "draft" or "publish".' );
		}
		$this->status = $status;
	}

	/**
	 * Get the status of the loop post.
	 *
	 * @return string The status of the loop post.
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Set the parent post ID for hierarchical posts.
	 *
	 * @param int|null $parent The parent post ID.
	 */
	public function set_parent( $parent ) {
		$this->parent = $parent;
	}

	/**
	 * Get the parent post ID.
	 *
	 * @return int|null The parent post ID.
	 */
	public function get_parent() {
		return $this->parent;
	}

	/**
	 * Set the Iterable_Expression object for the loop post.
	 *
	 * @param Iterable_Expression $iterable_expression The iterable expression object.
	 */
	public function set_iterable_expression( Iterable_Expression $iterable_expression ) {
		$this->iterable_expression = $iterable_expression;
	}

	/**
	 * Get the Iterable_Expression object for the loop post.
	 *
	 * @return Iterable_Expression The iterable expression object.
	 */
	public function get_iterable_expression() {
		return $this->iterable_expression;
	}
}

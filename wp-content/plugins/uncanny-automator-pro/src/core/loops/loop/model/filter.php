<?php
namespace Uncanny_Automator_Pro\Loops\Loop\Model;

use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Fields;
use Uncanny_Automator_Pro\Loops\Loop\Model\Loop_Filter\Backup;

/**
 * Class Loop_Filter
 *
 * Represents a loop filter post type with title, code, status, parent post ID, fields, and backup data.
 * The loop filter post can be created, edited, or deleted in WordPress as a custom post type.
 *
 * @package Uncanny_Automator_Pro\Loops\Loop\Model
 */
class Loop_Filter {

	/**
	 * The ID of the loop filter post.
	 *
	 * @var int|null
	 */
	protected $id = null;

	/**
	 * The title of the loop filter post.
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * The code associated with the loop filter post.
	 *
	 * @var string
	 */
	protected $code = '';

	/**
	 * The status of the loop filter post. Can be 'draft' or 'published'.
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
	 * @var string
	 */
	protected $integration = '';

	/**
	 * @var string
	 */
	protected $integration_name = '';

	/**
	 * The Fields instance associated with the loop filter post.
	 *
	 * @var Fields|null
	 */
	protected $fields = null;

	/**
	 * The Backup instance associated with the loop filter post.
	 *
	 * @var Backup|null
	 */
	protected $backup = null;

	/**
	 * Constructor to initialize the Loop_Filter object.
	 *
	 * @param Fields $fields The Fields object associated with this loop filter.
	 * @param Backup $backup The Backup object associated with this loop filter.
	 */
	public function __construct( Fields $fields, Backup $backup ) {
		$this->fields = $fields;
		$this->backup = $backup;
	}

	/**
	 * Set the ID of the loop filter post.
	 *
	 * @param int $id The ID of the loop filter post.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Get the ID of the loop filter post.
	 *
	 * @return int|null The ID of the loop filter post.
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the title of the loop filter post.
	 *
	 * @param string $title The title of the loop filter post.
	 * @throws \InvalidArgumentException If the title is empty.
	 */
	public function set_title( $title ) {
		if ( empty( $title ) ) {
			throw new \InvalidArgumentException( 'The title cannot be empty.' );
		}
		$this->title = $title;
	}

	/**
	 * Get the title of the loop filter post.
	 *
	 * @return string The title of the loop filter post.
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * Set the code for the loop filter post.
	 *
	 * @param string $code The code of the loop filter post.
	 */
	public function set_code( $code ) {
		$this->code = $code;
	}

	/**
	 * Get the code of the loop filter post.
	 *
	 * @return string The code of the loop filter post.
	 */
	public function get_code() {
		return $this->code;
	}

	/**
	 * Set the status of the loop filter post.
	 *
	 * @param string $status The status of the loop filter post. Must be either 'draft' or 'publish'.
	 * @throws \InvalidArgumentException If the status is not 'draft' or 'publish'.
	 */
	public function set_status( $status ) {
		if ( ! in_array( $status, array( 'draft', 'publish' ) ) ) {
			throw new \InvalidArgumentException( 'The status must be either "draft" or "publish".' );
		}
		$this->status = $status;
	}

	/**
	 * Get the status of the loop filter post.
	 *
	 * @return string The status of the loop filter post.
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
	 * Set the integration. Accepts integration code.
	 *
	 * @param string $parent The parent post ID.
	 */
	public function set_integration( $integration ) {
		$this->integration = $integration;
	}

	/**
	 * Get the integration code.
	 *
	 * @return string The integration code.
	 */
	public function get_integration() {
		return $this->integration;
	}

	 /**
	 * Set the integration name. Accepts integration name.
	 *
	 * @param string $parent The parent post ID.
	 */
	public function set_integration_name( $integration_name ) {
		$this->integration_name = $integration_name;
	}

	/**
	 * Get the integration name.
	 *
	 * @return string The integration name.
	 */
	public function get_integration_name() {
		return $this->integration_name;
	}

	/**
	 * Set the Fields object for the loop filter post.
	 *
	 * @param Fields $fields The Fields object.
	 */
	public function set_fields( Fields $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Get the Fields object for the loop filter post.
	 *
	 * @return Fields|null The Fields object.
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Set the Backup object for the loop filter post.
	 *
	 * @param Backup $backup The Backup object.
	 */
	public function set_backup( Backup $backup ) {
		$this->backup = $backup;
	}

	/**
	 * Get the Backup object for the loop filter post.
	 *
	 * @return Backup|null The Backup object.
	 */
	public function get_backup() {
		return $this->backup;
	}
}

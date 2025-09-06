<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Lifterlms_Helpers;
use function Symfony\Component\Translation\t;

/**
 * Class Lifterlms_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Lifterlms_Pro_Helpers extends Lifterlms_Helpers {

	/**
	 * All enrollment change action hooks.
	 *
	 * @var array
	 */
	private $enrollment_action_hooks = array(
		// Courses.
		'llms_user_course_enrollment_created'     => 2,
		'llms_user_course_enrollment_updated'     => 2,
		'llms_user_removed_from_course'           => 4,
		// Memberships.
		'llms_user_membership_enrollment_created' => 2,
		'llms_user_membership_enrollment_updated' => 2,
		'llms_user_removed_from_membership'       => 4,
	);

	/**
	 * Lifterlms_Pro_Helpers constructor.
	 */
	public function __construct( $load_hooks = true ) {
		// Selectively load options
		if ( property_exists( '\Uncanny_Automator\Lifterlms_Helpers', 'load_options' ) ) {

			$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
		}

		if ( $load_hooks ) {
			$this->load_hooks();
		}

	}

	/**
	 * Load all WP hooks.
	 *
	 * @return void
	 */
	public function load_hooks() {

		// All memberships options.
		add_filter( 'uap_option_all_lf_memberships', array( $this, 'add_all_memberships_option' ), 99, 3 );

		// Ajax hooks.
		add_action( 'wp_ajax_lifter_lms_retrieve_product_types', array( $this, 'ajax_lifter_lms_retrieve_product_types' ) );
		add_action( 'wp_ajax_lifter_lms_retrieve_order_statuses', array( $this, 'ajax_lifter_lms_retrieve_order_statuses' ) );
		add_action( 'wp_ajax_lifter_lms_retrieve_enrollment_statuses', array( $this, 'ajax_lifter_lms_retrieve_enrollment_statuses' ) );

		// Product enrollment normalizer.
		foreach ( $this->enrollment_action_hooks as $hook => $args_count ) {
			add_action( $hook, array( $this, 'user_product_enrollment_update' ), 9999, $args_count );
		}
	}

	/**
	 * @param Lifterlms_Pro_Helpers $pro
	 */
	public function setPro( Lifterlms_Pro_Helpers $pro ) {
		parent::setPro( $pro );
	}

	/**
	 * @param string $label
	 * @param string $option_code
	 * @param bool   $any_option
	 *
	 * @return mixed
	 */
	public function all_lf_groups( $label = null, $option_code = 'LFGROUPS', $any_option = true ) {
		if ( ! $this->load_options ) {
			return Automator()->helpers->recipe->build_default_options_array( $label, $option_code );
		}

		if ( ! $label ) {
			$label = esc_attr__( 'Group', 'uncanny-automator-pro' );
		}

		$args = array(
			'post_type'      => 'llms_group',
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options = Automator()->helpers->recipe->options->wp_query( $args, $any_option, esc_attr__( 'Any group', 'uncanny-automator' ) );

		$option = array(
			'option_code'     => $option_code,
			'label'           => $label,
			'input_type'      => 'select',
			'required'        => true,
			// to setup example, lets define the value the child will be based on
			'current_value'   => false,
			'validation_type' => 'text',
			'options'         => $options,
			'relevant_tokens' => array(
				$option_code                => esc_attr__( 'Group title', 'uncanny-automator' ),
				$option_code . '_ID'        => esc_attr__( 'Group ID', 'uncanny-automator' ),
				$option_code . '_URL'       => esc_attr__( 'Group URL', 'uncanny-automator' ),
				$option_code . '_THUMB_ID'  => esc_attr__( 'Group featured image ID', 'uncanny-automator' ),
				$option_code . '_THUMB_URL' => esc_attr__( 'Group featured image URL', 'uncanny-automator' ),
			),
		);

		return apply_filters( 'uap_option_all_lf_groups', $option );
	}

	/**
	 * @param $options
	 *
	 * @return mixed
	 */
	public function add_all_memberships_option( $options, $is_all_label ) {
		if ( empty( $options ) ) {
			return $options;
		}

		if ( 'LFMEMBERSHIP' !== $options['option_code'] ) {
			return $options;
		}

		if ( true === $is_all_label ) {
			$all_groups         = array( '-1' => esc_attr__( 'All memberships', 'uncanny-automator-pro' ) );
			$options['options'] = $all_groups + $options['options'];
		}

		return $options;
	}

	/**
	 * @param $is_any
	 *
	 * @return array
	 */
	public function get_all_lf_engagements( $is_any = false ) {
		$args = array(
			'post_type'      => 'llms_engagement',
			'posts_per_page' => 999,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		);

		$options     = Automator()->helpers->recipe->options->wp_query( $args, $is_any, esc_attr__( 'Any engagement', 'uncanny-automator-pro' ) );
		$engagements = array();
		foreach ( $options as $key => $option ) {
			$engagements[] = array(
				'text'  => $option,
				'value' => $key,
			);
		}

		return $engagements;
	}

	/**
	 * Normalize the user enrollment updated action hooks.
	 *
	 * @param int $user_id                 - The ID of the user.
	 * @param int $product_id              - The ID of the product (course or membership).
	 * @param mixed (string|null) $trigger - The trigger that caused the action or null depending on hook.
	 * @param mixed $new_status            - The new status of the enrollment or null depending on hook.
	 */
	public function user_product_enrollment_update( $user_id, $product_id, $trigger = null, $new_status = null ) {

		$hook = current_filter();

		// Validate the hook.
		$hooks = $this->enrollment_action_hooks;
		if ( ! key_exists( $hook, $hooks ) ) {
			return;
		}

		// Validate the post type of the product ID.
		$post_type = get_post_type( $product_id );
		if ( ! $post_type || ! key_exists( $post_type, $this->get_product_post_types() ) ) {
			return;
		}

		// Get defined statuses.
		$statuses = $this->get_enrollment_statuses();
		if ( is_wp_error( $statuses ) ) {
			return;
		}

		$is_removal = strpos( $hook, 'llms_user_removed_from_' ) === 0;
		$status     = $is_removal ? $new_status : $this->get_user_product_enrollment_status( $user_id, $product_id );

		// Validate the status.
		if ( empty( $status ) || ! key_exists( $status, $statuses ) ) {
			return;
		}

		// Trigger custom action.
		do_action( 'automator_pro_llms_user_enrollment_update', $user_id, $product_id, $post_type, $status );
	}

	/**
	 * Get all LifterLMS product post types
	 *
	 * @param bool $is_options - Flag to return option format.
	 */
	public function get_product_post_types( $is_options = false ) {

		$post_types = array(
			'llms_membership' => esc_attr_x( 'Membership', 'LifterLMS', 'uncanny-automator-pro' ),
			'course'          => esc_attr_x( 'Course', 'LifterLMS', 'uncanny-automator-pro' ),
		);

		if ( $is_options ) {
			$options = array();
			array_walk(
				$post_types,
				function ( $label, $post_type ) use ( &$options ) {
					$object = get_post_type_object( $post_type );
					if ( ! is_null( $object ) && property_exists( $object, 'labels' ) && property_exists( $object->labels, 'singular_name' ) ) {
						$label = $object->labels->singular_name;
					}
					$options[] = array(
						'text'  => $label,
						'value' => $post_type,
					);
				}
			);

			return $options;
		}

		return $post_types;
	}

	/**
	 * Get all LifterLMS enrollment statuses.
	 *
	 * @param bool $is_options - Whether to return the statuses as options or not.
	 *
	 * @return mixed WP_Error|array
	 */
	public function get_enrollment_statuses( $is_options = false ) {

		if ( ! function_exists( 'llms_get_enrollment_statuses' ) ) {
			return new \WP_Error(
				'llms_get_enrollment_statuses_not_found',
				esc_attr_x( 'Function llms_get_enrollment_statuses does not exist', 'LifterLMS', 'uncanny-automator-pro' )
			);
		}

		$statuses = llms_get_enrollment_statuses();
		if ( $is_options ) {
			return array_map(
				function( $status, $label ) {
					return array(
						'text'  => $label,
						'value' => $status,
					);
				},
				array_keys( $statuses ),
				$statuses
			);
		}

		return $statuses;
	}

	/**
	 * Get all LifterLMS order statuses.
	 *
	 * @param bool $is_options - Whether to return the statuses as options or not.
	 *
	 * @return array
	 */
	public function get_user_product_enrollment_status( $user_id, $product_id ) {
		$student = llms_get_student( $user_id, false );
		return $student ? $student->get_enrollment_status( $product_id ) : false;
	}

	/**
	 * AJAX callback to retrieve order product types
	 *
	 * @return JSON
	 */
	public function ajax_lifter_lms_retrieve_product_types() {

		Automator()->utilities->verify_nonce();

		$product_types = $this->get_product_post_types( true );
		if ( is_wp_error( $product_types ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => $product_types->get_error_message(),
					'options' => array(),
				)
			);
		}

		wp_send_json(
			array(
				'success' => true,
				'options' => $product_types,
			)
		);
	}

	/**
	 * AJAX callback to retrieve order statuses
	 *
	 * @return JSON
	 */
	public function ajax_lifter_lms_retrieve_order_statuses() {

		Automator()->utilities->verify_nonce();

		if ( ! function_exists( 'llms_get_order_statuses' ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => esc_attr_x( 'Function llms_get_order_statuses does not exist', 'LifterLMS', 'uncanny-automator-pro' ),
					'options' => array(),
				)
			);
		}

		$statuses = llms_get_order_statuses();
		$options  = array();

		foreach ( $statuses as $status => $label ) {
			$options[] = array(
				'text'  => $label,
				'value' => $status,
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'options' => $options,
			)
		);
	}

	/**
	 * AJAX callback to retrieve enrollment statuses.
	 *
	 * @return JSON
	 */
	public function ajax_lifter_lms_retrieve_enrollment_statuses() {

		Automator()->utilities->verify_nonce();

		$statuses = $this->get_enrollment_statuses( true );
		if ( is_wp_error( $statuses ) ) {
			wp_send_json(
				array(
					'success' => false,
					'error'   => $statuses->get_error_message(),
					'options' => array(),
				)
			);
		}

		return wp_send_json(
			array(
				'success' => true,
				'options' => $statuses,
			)
		);
	}

}

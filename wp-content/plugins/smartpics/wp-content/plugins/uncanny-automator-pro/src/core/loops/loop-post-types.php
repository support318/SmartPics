<?php
namespace Uncanny_Automator_Pro\Loops;

class Loop_Post_Types {

	/**
	 * Register a custom post type called "loop".
	 *
	 * @return void
	 */
	public function register_loop_post_type() {
		$labels = array(
			'name'                  => _x( 'Loops', 'Post type general name', 'uncanny-automator-pro' ),
			'singular_name'         => _x( 'Loop', 'Post type singular name', 'uncanny-automator-pro' ),
			'menu_name'             => _x( 'Loops', 'Admin Menu text', 'uncanny-automator-pro' ),
			'name_admin_bar'        => _x( 'Loop', 'Add New on Toolbar', 'uncanny-automator-pro' ),
			'add_new'               => __( 'Add New', 'uncanny-automator-pro' ),
			'add_new_item'          => __( 'Add New Loop', 'uncanny-automator-pro' ),
			'new_item'              => __( 'New Loop', 'uncanny-automator-pro' ),
			'edit_item'             => __( 'Edit Loop', 'uncanny-automator-pro' ),
			'view_item'             => __( 'View Loop', 'uncanny-automator-pro' ),
			'all_items'             => __( 'All Loops', 'uncanny-automator-pro' ),
			'search_items'          => __( 'Search Loops', 'uncanny-automator-pro' ),
			'parent_item_colon'     => __( 'Parent Loops:', 'uncanny-automator-pro' ),
			'not_found'             => __( 'No loops found.', 'uncanny-automator-pro' ),
			'not_found_in_trash'    => __( 'No loops found in Trash.', 'uncanny-automator-pro' ),
			'featured_image'        => _x( 'Loop Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'archives'              => _x( 'Loop archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'uncanny-automator-pro' ),
			'insert_into_item'      => _x( 'Insert into loop', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'uncanny-automator-pro' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this loop', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'uncanny-automator-pro' ),
			'filter_items_list'     => _x( 'Loop Filter loops list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'uncanny-automator-pro' ),
			'items_list_navigation' => _x( 'Loops list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'uncanny-automator-pro' ),
			'items_list'            => _x( 'Loops list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'uncanny-automator-pro' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'uo-loop' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => 10,
			'supports'           => array( 'title', 'editor', 'custom-fields' ),
		);

		register_post_type( 'uo-loop', $args );
	}

	/**
	 * Registers the filter post type.
	 *
	 * @return void
	 */
	public function register_filter_post_type() {
		$labels = array(
			'name'                  => _x( 'Loop Filters', 'Post type general name', 'uncanny-automator-pro' ),
			'singular_name'         => _x( 'Loop Filter', 'Post type singular name', 'uncanny-automator-pro' ),
			'menu_name'             => _x( 'Loop Filters', 'Admin Menu text', 'uncanny-automator-pro' ),
			'name_admin_bar'        => _x( 'Loop Filter', 'Add New on Toolbar', 'uncanny-automator-pro' ),
			'add_new'               => __( 'Add New', 'uncanny-automator-pro' ),
			'add_new_item'          => __( 'Add New Loop Filter', 'uncanny-automator-pro' ),
			'new_item'              => __( 'New Loop Filter', 'uncanny-automator-pro' ),
			'edit_item'             => __( 'Edit Loop Filter', 'uncanny-automator-pro' ),
			'view_item'             => __( 'View Loop Filter', 'uncanny-automator-pro' ),
			'all_items'             => __( 'All Loop Filters', 'uncanny-automator-pro' ),
			'search_items'          => __( 'Search Loop Filters', 'uncanny-automator-pro' ),
			'parent_item_colon'     => __( 'Parent Loop Filters:', 'uncanny-automator-pro' ),
			'not_found'             => __( 'No loops found.', 'uncanny-automator-pro' ),
			'not_found_in_trash'    => __( 'No loops found in Trash.', 'uncanny-automator-pro' ),
			'featured_image'        => _x( 'Loop Filter Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'uncanny-automator-pro' ),
			'archives'              => _x( 'Loop Filter archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'uncanny-automator-pro' ),
			'insert_into_item'      => _x( 'Insert into loop', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'uncanny-automator-pro' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this loop', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'uncanny-automator-pro' ),
			'filter_items_list'     => _x( 'Loop Filter loops list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'uncanny-automator-pro' ),
			'items_list_navigation' => _x( 'Loop Filters list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'uncanny-automator-pro' ),
			'items_list'            => _x( 'Loop Filters list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'uncanny-automator-pro' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'uo-loop-filter' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => 10,
			'supports'           => array( 'title', 'custom-fields' ),
		);

		register_post_type( 'uo-loop-filter', $args );
	}

}



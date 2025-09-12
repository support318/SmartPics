<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * MemberPress integration.
 *
 * @since 2.9.1
 */
class WPF_MemberPress extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */
	public $slug = 'memberpress';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'MemberPress';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/integrations/memberpress/';

	/**
	 * Instance of WPF_MemberPress_Transactions.
	 *
	 * @since 3.45.0
	 *
	 * @var WPF_MemberPress_Transactions
	 */
	public $transactions;

	/**
	 * Instance of WPF_MemberPress_Subscriptions.
	 *
	 * @since 3.45.0
	 *
	 * @var WPF_MemberPress_Subscriptions
	 */
	public $subscriptions;

	/**
	 * Instance of WPF_MemberPress_Admin.
	 *
	 * @since 3.45.0
	 *
	 * @var WPF_MemberPress_Admin
	 */
	public $admin;

	/**
	 * Instance of WPF_MemberPress_Batch.
	 *
	 * @since 3.45.0
	 *
	 * @var WPF_MemberPress_Batch
	 */
	public $batch;

	/**
	 * Instance of WPF_MemberPress_Courses.
	 *
	 * @since 3.45.0
	 *
	 * @var WPF_MemberPress_Courses
	 */
	public $courses;

	/**
	 * Instance of WPF_MemberPress_Corporate.
	 *
	 * @since 3.45.0
	 *
	 * @var WPF_MemberPress_Corporate
	 */
	public $corporate;

	/**
	 * Gets things started.
	 *
	 * @since 2.9.1
	 */
	public function init() {

		// Load sub-modules
		require_once __DIR__ . '/class-memberpress-transactions.php';
		require_once __DIR__ . '/class-memberpress-subscriptions.php';
		require_once __DIR__ . '/class-memberpress-admin.php';
		require_once __DIR__ . '/class-memberpress-batch.php';

		if ( class_exists( 'memberpress\courses\models\course' ) ) {
			require_once __DIR__ . '/class-memberpress-courses.php';
		}

		if ( defined( 'MPCA_PLUGIN_NAME' ) ) {
			require_once __DIR__ . '/class-memberpress-corporate.php';
		}

		$this->transactions  = new WPF_MemberPress_Transactions();
		$this->subscriptions = new WPF_MemberPress_Subscriptions();
		$this->admin         = new WPF_MemberPress_Admin();
		$this->batch         = new WPF_MemberPress_Batch();

		if ( class_exists( 'memberpress\courses\models\course' ) ) {
			$this->courses = new WPF_MemberPress_Courses();
		}

		if ( defined( 'MPCA_PLUGIN_NAME' ) ) {
			$this->corporate = new WPF_MemberPress_Corporate();
		}

		// Core functionality
		add_filter( 'wpf_user_register', array( $this, 'user_register' ), 10, 2 );
		add_action( 'wpf_tags_modified', array( $this, 'add_to_membership' ), 10, 2 );
		add_action( 'mepr_save_account', array( $this, 'save_account' ) );
		add_filter( 'wpf_user_update', array( $this, 'user_update' ), 10, 2 );
		add_filter( 'wpf_pulled_user_meta', array( $this, 'pulled_user_meta' ), 10, 2 );
	}


	/**
	 * Applies tags based on the user's active product memberships.
	 *
	 * When a transaction expires, the member falls back to the previous membership,
	 * but MemberPress doesn't have a hook or event for that unless it's in a "grouped"
	 * membership, in which a "fallback" transaction is created, and "mepr-txn-status-complete"
	 * is triggered, which applies the new tags and syncs the fields (see apply_tags_checkout()).
	 *
	 * For non-grouped memberships, MemberPress doesn't create a fallback transaction,
	 * or trigger an action, it just runs MeprUser::member_col_memberships() which
	 * recalculates the "memberships" and "inactive_memberships" columns in mepr_members.
	 * To apply tags based on the new membership, we need to run the same logic manually.
	 *
	 * @since 3.44.15
	 *
	 * @param int $user_id The user ID.
	 */
	public function apply_tags_for_active_memberships( $user_id ) {

		$product_ids = $this->get_product_memberships( $user_id );

		if ( empty( $product_ids ) ) {
			return;
		}

		// Prevent looping.
		remove_action( 'wpf_tags_modified', array( $this, 'add_to_membership' ), 10, 2 );

		foreach ( $product_ids as $product_id ) {

			$settings = get_post_meta( $product_id, 'wpf-settings-memberpress', true );

			if ( empty( $settings ) ) {
				continue;
			}

			if ( ! empty( $settings['apply_tags_registration'] ) ) {
				wp_fusion()->user->apply_tags( $settings['apply_tags_registration'], $user_id );
			}

			if ( ! empty( $settings['tag_link'] ) ) {
				wp_fusion()->user->apply_tags( $settings['tag_link'], $user_id );
			}
		}

		add_action( 'wpf_tags_modified', array( $this, 'add_to_membership' ), 10, 2 );
	}

	/**
	 * Get the product memberships for a user.
	 *
	 * @since 3.41.46
	 *
	 * @param int    $user_id The user ID.
	 * @param string $type    The type of memberships to get.
	 * @return array The membership IDs.
	 */
	public function get_product_memberships( $user_id, $type = 'active' ) {

		if ( 'active' === $type ) {
			$col = 'memberships';
		} elseif ( 'inactive' === $type ) {
			$col = 'inactive_memberships';
		} else {
			return array();
		}

		$data = MeprUser::member_data( $user_id, array( $col ) );

		return explode( ',', $data->$col );
	}


	/**
	 * Get the membership status for a user by ID.
	 *
	 * @since 3.41.46
	 *
	 * @param int $user_id The user ID.
	 * @return string The status.
	 */
	public function get_membership_status( $user_id ) {
		$mepr_user = new MeprUser( $user_id );

		if ( $mepr_user->is_active() ) {
			return 'Active';
		} elseif ( $mepr_user->has_expired() ) {
			return 'Inactive';
		} else {
			return 'None';
		}
	}

	/**
	 * Formats special fields for sending
	 *
	 * @since unknown
	 *
	 * @param array $user_meta User meta.
	 * @param bool  $remove_empty Whether to remove empty checkboxes.
	 * @return array User meta.
	 */
	private function format_fields( $user_meta, $remove_empty = false ) {

		if ( empty( $user_meta ) ) {
			return $user_meta;
		}

		$mepr_options = MeprOptions::fetch();

		foreach ( $user_meta as $key => $value ) {

			// Convert checkboxes to an array of their labels (not values).
			if ( is_array( $value ) && 'multiselect' === wpf_get_field_type( $key ) ) {

				$value_labels = array();

				foreach ( $mepr_options->custom_fields as $field_object ) {

					if ( $field_object->field_key == $key ) {

						foreach ( $field_object->options as $option ) {

							if ( isset( $value[ $option->option_value ] ) ) {

								// Checkboxes.
								$value_labels[] = $option->option_name;

							} elseif ( in_array( $option->option_value, $value, true ) ) {

								// Multiselects.
								$value_labels[] = $option->option_name;

							}
						}

						$user_meta[ $key ] = $value_labels;

					}
				}
			} elseif ( is_array( $value ) && 'multiselect (values)' === wpf_get_field_type( $key ) ) {

				$user_meta[ $key ] = array_keys( $value );

			} elseif ( 'radio' === wpf_get_field_type( $key ) ) {

				foreach ( $mepr_options->custom_fields as $field_object ) {

					if ( $field_object->field_key == $key ) {

						foreach ( $field_object->options as $option ) {

							if ( $option->option_value == $value ) {

								$user_meta[ $key ] = $option->option_name;

							}
						}
					}
				}
			} elseif ( 'checkbox' === wpf_get_field_type( $key ) && ! empty( $value ) ) {

				// MemberPress checkboxes sync as 'on' by default.

				$user_meta[ $key ] = true;

			}
		}

		// Possibly clear out empty checkboxes if it's a MP form.

		if ( $remove_empty ) {

			foreach ( $mepr_options->custom_fields as $field_object ) {

				if ( 'checkbox' === $field_object->field_type && $field_object->show_in_account && ! isset( $user_meta[ $field_object->field_key ] ) ) {

					$user_meta[ $field_object->field_key ] = null;

				}
			}
		}

		if ( ! empty( $user_meta['mpca_corporate_account_id'] ) && class_exists( 'MPCA_Corporate_Account' ) ) {

			$corporate_user = MPCA_Corporate_Account::get_one( $user_meta['mpca_corporate_account_id'] );

			if ( $corporate_user ) {
				$corporate_wp_user                        = get_user_by( 'id', $corporate_user->user_id );
				$user_meta['mepr_corporate_parent_email'] = $corporate_wp_user->user_email;
			}
		}

		return $user_meta;
	}

	/**
	 * Filters registration data before sending to the CRM.
	 *
	 * @since 2.9.1
	 *
	 * @param array $post_data Registration data.
	 * @param int   $user_id   User ID.
	 * @return array Registration data.
	 */
	public function user_register( $post_data, $user_id ) {
		$field_map = array(
			'user_first_name'    => 'first_name',
			'user_last_name'     => 'last_name',
			'mepr_user_password' => 'user_pass',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );
		$post_data = $this->format_fields( $post_data );

		return $post_data;
	}

	/**
	 * Add user to membership when tag-link tags are applied.
	 *
	 * @since 2.9.1
	 *
	 * @param int   $user_id   User ID.
	 * @param array $user_tags User tags.
	 */
	public function add_to_membership( $user_id, $user_tags ) {

		$linked_products = get_posts(
			array(
				'post_type'  => 'memberpressproduct',
				'nopaging'   => true,
				'fields'     => 'ids',
				'meta_query' => array(
					array(
						'key'     => 'wpf-settings-memberpress',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( empty( $linked_products ) ) {
			return;
		}

		// Update role based on user tags
		foreach ( $linked_products as $product_id ) {

			$settings = get_post_meta( $product_id, 'wpf-settings-memberpress', true );

			if ( empty( $settings ) || empty( $settings['tag_link'] ) ) {
				continue;
			}

			$tag_id = $settings['tag_link'][0];

			$mepr_user = new MeprUser( $user_id );

			if ( in_array( $tag_id, $user_tags ) && ! $mepr_user->is_already_subscribed_to( $product_id ) ) {

				// Prevent looping
				remove_action( 'mepr-txn-status-complete', array( $this->transactions, 'transaction_complete' ) );

				// Auto enroll
				wpf_log( 'info', $user_id, 'User auto-enrolled in <a href="' . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $product_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

				// Create the MemberPress transaction
				$txn             = new MeprTransaction();
				$txn->user_id    = $user_id;
				$txn->product_id = $product_id;
				$txn->txn_type   = 'subscription_confirmation';
				$txn->gateway    = 'manual';
				$txn->created_at = current_time( 'mysql' );

				$product = new MeprProduct( $txn->product_id );

				// Can't use $txn->create_free_transaction( $txn ); since it forces a redirect, so copied the code from MeprTransaction
				if ( $product->period_type != 'lifetime' ) { // A free recurring subscription? Nope - let's make it lifetime for free here folks

					$expires_at = MeprUtils::db_lifetime();

				} else {
					$product_expiration = $product->get_expires_at( strtotime( $txn->created_at ) );

					if ( is_null( $product_expiration ) ) {
						$expires_at = MeprUtils::db_lifetime();
					} else {
						$expires_at = MeprUtils::ts_to_mysql_date( $product_expiration, 'Y-m-d 23:59:59' );
					}
				}

				$txn->trans_num  = MeprTransaction::generate_trans_num();
				$txn->status     = 'pending';
				$txn->txn_type   = 'payment';
				$txn->gateway    = 'free';
				$txn->expires_at = $expires_at;

				// This will only work before maybe_cancel_old_sub is run
				$upgrade   = $txn->is_upgrade();
				$downgrade = $txn->is_downgrade();

				$event_txn   = $txn->maybe_cancel_old_sub();
				$txn->status = 'complete';
				$txn->store();

				$free_gateway = new MeprBaseStaticGateway( 'free', __( 'Free', 'memberpress' ), __( 'Free', 'memberpress' ) );

				if ( $upgrade ) {

					$free_gateway->upgraded_sub( $txn, $event_txn );

				} elseif ( $downgrade ) {

					$free_gateway->downgraded_sub( $txn, $event_txn );

				}

				MeprEvent::record( 'transaction-completed', $txn ); // Delete this if we use $free_gateway->send_transaction_receipt_notices later
				MeprEvent::record( 'non-recurring-transaction-completed', $txn ); // Delete this if we use $free_gateway->send_transaction_receipt_notices later

				remove_action( 'mepr-signup', array( $this->transactions, 'transaction_complete' ) );

				MeprHooks::do_action( 'mepr-signup', $txn ); // This lets the Corportate Accounts addon know there's been a new signup

				add_action( 'mepr-signup', array( $this->transactions, 'transaction_complete' ) );

			} elseif ( ! in_array( $tag_id, $user_tags ) && $mepr_user->is_already_subscribed_to( $product_id ) ) {

				// Auto un-enroll
				wpf_log( 'info', $user_id, 'User unenrolled from <a href="' . admin_url( 'post.php?post=' . $product_id . '&action=edit' ) . '" target="_blank">' . get_the_title( $product_id ) . '</a> by linked tag <strong>' . wp_fusion()->user->get_tag_label( $tag_id ) . '</strong>' );

				$transactions = $mepr_user->active_product_subscriptions( 'transactions' );
				$did_it       = false;

				foreach ( $transactions as $transaction ) {

					if ( $transaction->product_id == $product_id && ( 'free' == $transaction->gateway || 'manual' == $transaction->gateway ) ) {

						remove_action( 'mepr-event-transaction-expired', array( $this->transactions, 'transaction_expired' ), 20 ); // no need to apply Expired tags

						$transaction->destroy();
						$did_it = true;

						add_action( 'mepr-event-transaction-expired', array( $this->transactions, 'transaction_expired' ), 20 );

					}
				}

				if ( ! $did_it ) {
					wpf_log( 'notice', $user_id, 'Automated unenrollment failed. For security reasons WP Fusion can only unenroll users from transactions created with the "free" or "manual" gateways.' );
				}
			}
		}
	}

	/**
	 * Triggered when account fields are modified from the MemberPress account page.
	 *
	 * @since 2.9.1
	 *
	 * @param object $user User object.
	 */
	public function save_account( $user ) {

		// Modify post data so user_update knows to remove empty fields
		$_POST['from'] = 'profile';

		wp_fusion()->user->push_user_meta( $user->ID, $_POST );
	}

	/**
	 * Adjusts field formatting for custom MemberPress fields.
	 *
	 * @since 2.9.1
	 *
	 * @param array $user_meta User meta.
	 * @param int   $user_id   User ID.
	 * @return array User meta.
	 */
	public function user_update( $user_meta, $user_id ) {

		if ( isset( $user_meta['from'] ) && 'profile' === $user_meta['from'] ) {
			$remove_empty = true;
		} else {
			$remove_empty = false;
		}

		$user_meta = $this->format_fields( $user_meta, $remove_empty );

		$field_map = array(
			'mepr-new-password' => 'user_pass',
		);

		$user_meta = $this->map_meta_fields( $user_meta, $field_map );

		return $user_meta;
	}

	/**
	 * Triggered when user meta is loaded from the CRM.
	 *
	 * @since 2.9.1
	 *
	 * @param array $user_meta User meta.
	 * @param int   $user_id   User ID.
	 * @return array User meta.
	 */
	public function pulled_user_meta( $user_meta, $user_id ) {

		$mepr_options = MeprOptions::fetch();

		foreach ( $mepr_options->custom_fields as $field_object ) {

			if ( ! empty( $user_meta[ $field_object->field_key ] ) && 'checkboxes' === $field_object->field_type ) {

				if ( ! is_array( $user_meta[ $field_object->field_key ] ) ) {
					$loaded_value = explode( ',', $user_meta[ $field_object->field_key ] );
				} else {
					$loaded_value = $user_meta[ $field_object->field_key ];
				}

				$new_value = array();

				foreach ( $field_object->options as $option ) {

					if ( in_array( $option->option_name, $loaded_value ) ) {
						$new_value[ $option->option_value ] = 'on';
					}
				}

				$user_meta[ $field_object->field_key ] = $new_value;

			} elseif ( ! empty( $user_meta[ $field_object->field_key ] ) && $field_object->field_type == 'radios' ) {

				foreach ( $field_object->options as $option ) {

					if ( $user_meta[ $field_object->field_key ] == $option->option_name ) {

						$user_meta[ $field_object->field_key ] = $option->option_value;

					}
				}
			}
		}

		return $user_meta;
	}
}

new WPF_MemberPress();

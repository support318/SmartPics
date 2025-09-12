<?php

// phpcs:ignoreFile

/**
 * PHP Stan bootstrap file.
 *
 * @package WP Fusion
 */

namespace {
	if ( ! defined( 'WPF_MIN_WP_VERSION' ) ) {
		/**
		 * Minimum WordPress version required.
		 *
		 * @phpstan-type string $min_wp_version
		 * @var string $min_wp_version
		 */
		define( 'WPF_MIN_WP_VERSION', '4.0' );
	}

	if ( ! defined( 'WPF_MIN_PHP_VERSION' ) ) {
		/**
		 * Minimum PHP version required.
		 *
		 * @phpstan-type string $min_php_version
		 * @var string $min_php_version
		 */
		define( 'WPF_MIN_PHP_VERSION', '5.6' );
	}

	if ( ! defined( 'WPF_DIR_PATH' ) ) {
		/**
		 * Directory path where WP Fusion is located.
		 *
		 * @phpstan-type string $dir_path
		 * @var string $dir_path
		 */
		define( 'WPF_DIR_PATH', 'path/to/wp-fusion' );
	}

	if ( ! defined( 'WPF_PLUGIN_PATH' ) ) {
		/**
		 * Full plugin path for WP Fusion.
		 *
		 * @phpstan-type string $plugin_path
		 * @var string $plugin_path
		 */
		define( 'WPF_PLUGIN_PATH', 'path/to/wp-fusion' );
	}

	if ( ! defined( 'WPF_DIR_URL' ) ) {
		/**
		 * URL for the WP Fusion directory.
		 *
		 * @phpstan-type string $dir_url
		 * @var string $dir_url
		 */
		define( 'WPF_DIR_URL', 'https://site.com/path/to/wp-fusion' );
	}

	if ( ! defined( 'WPF_STORE_URL' ) ) {
		/**
		 * URL for the WP Fusion store.
		 *
		 * @phpstan-type string $store_url
		 * @var string $store_url
		 */
		define( 'WPF_STORE_URL', 'https://wpfusion.com' );
	}

	if ( ! defined( 'WPF_CONTACT_ID_META_KEY' ) ) {
		/**
		 * Media Key for WP Fusion.
		 */
		define( 'WPF_CONTACT_ID_META_KEY', 'key' );
	}

	if ( ! defined( 'WPF_TAGS_META_KEY' ) ) {
		/**
		 * Meta Key for WP Fusion.
		 */
		define( 'WPF_TAGS_META_KEY', 'key' );
	}

	if ( ! defined( 'COOKIEPATH' ) ) {
		/**
		 * Cookie path.
		 */
		define( 'COOKIEPATH', 'path' );
	}

	if ( ! defined( 'COOKIEDOMAIN' ) ) {
		/**
		 * Cookie domain.
		 */
		define( 'COOKIEDOMAIN', 'domain' );
	}

	if ( ! defined( 'WPF_EDD_ITEM_ID' ) ) {
		/**
		 * Easy Digital Downloads item ID for WP Fusion.
		 *
		 * @phpstan-type string $edd_item_id
		 * @var string $edd_item_id
		 */
		define( 'WPF_EDD_ITEM_ID', 'XXXX' );
	}

	if ( ! class_exists( 'BB_Access_Control_Abstract' ) ) {
		class BB_Access_Control_Abstract {}
	}

	if ( ! class_exists( 'FrmFormAction' ) ) {
		class FrmFormAction {}
	}

	if ( ! class_exists( 'GFFeedAddOn' ) ) {
		class GFFeedAddOn {}
	}

	if ( ! class_exists( 'NF_Abstracts_Action' ) ) {
		class NF_Abstracts_Action {}
	}

	if ( ! class_exists( 'Thrive_Dash_List_Connection_Abstract' ) ) {
		class Thrive_Dash_List_Connection_Abstract {}
	}

	if ( ! class_exists( 'WPEP_Content_Library_Integration' ) ) {
		class WPEP_Content_Library_Integration {}
	}

	if ( ! class_exists( 'WPForms_Provider' ) ) {
		class WPForms_Provider {}
	}

	if ( ! class_exists( 'WS_Form_Action' ) ) {
		class WS_Form_Action {}
	}

	if ( ! class_exists( 'Forminator_Integration_Form_Hooks' ) ) {
		class Forminator_Integration_Form_Hooks {}
	}

	if ( ! class_exists( 'Forminator_Integration_Form_Settings' ) ) {
		class Forminator_Integration_Form_Settings {}
	}

	if ( ! class_exists( 'Forminator_Integration' ) ) {
		class Forminator_Integration {}
	}

	if ( ! class_exists( 'MeprBaseMetaModel' ) ) {
		class MeprBaseMetaModel {
			public function __construct( $obj = null ) {}
			public function initialize( $defaults = array(), $obj = null ) {}
			public function get_values() {}
			public function validate_is_currency( $amount, $min = 0.00, $max = null, $field = '' ) {}
			public function validate_is_numeric( $num, $min = 0, $max = null, $field = '' ) {}
			public function validate_not_empty( $value, $field = '' ) {}
			public function validate_is_in_array( $value, $valid_values = array(), $field = '' ) {}
		}
	}

	if ( ! interface_exists( 'MeprProductInterface' ) ) {
		interface MeprProductInterface {}
	}

	if ( ! interface_exists( 'MeprTransactionInterface' ) ) {
		interface MeprTransactionInterface {}
	}

	if ( ! class_exists( 'MeprTransaction' ) ) {
		#[AllowDynamicProperties]
		class MeprTransaction extends MeprBaseMetaModel implements MeprProductInterface, MeprTransactionInterface {
			public $id;
			public $amount;
			public $total;
			public $tax_amount;
			public $tax_reversal_amount;
			public $tax_rate;
			public $tax_desc;
			public $tax_class;
			public $user_id;
			public $product_id;
			public $coupon_id;
			public $trans_num;
			public $status;
			public $txn_type;
			public $gateway;
			public $prorated;
			public $created_at;
			public $expires_at;
			public $subscription_id;
			public $corporate_account_id;
			public $parent_transaction_id;
			public $order_id;

			public static $payment_str                   = 'payment';
			public static $subscription_confirmation_str = 'subscription_confirmation';
			public static $sub_account_str               = 'sub_account';
			public static $woo_txn_str                   = 'wc_transaction';
			public static $fallback_str                  = 'fallback';

			public static $pending_str   = 'pending';
			public static $failed_str    = 'failed';
			public static $complete_str  = 'complete';
			public static $confirmed_str = 'confirmed';
			public static $refunded_str  = 'refunded';

			public static $free_gateway_str     = 'free';
			public static $manual_gateway_str   = 'manual';
			public static $fallback_gateway_str = 'fallback';

			public function __construct( $obj = null ) {}
			public static function create( $txn ) {}
			public static function update( $txn ) {}
			public static function get_one( $id, $return_type = OBJECT ) {}
			public static function get_one_by_trans_num( $trans_num ) {}
			public function store( $keep_expires_at_time = false ) {}
			public function is_active( $offset = 0 ) {}
			public function is_expired( $offset = 0 ) {}
			public function product() {}
			public function user( $force = false ) {}
			public function subscription() {}
			public function order() {}
			public function coupon() {}
			public function payment_method() {}
			public function is_upgrade() {}
			public function is_downgrade() {}
			public function is_one_time_payment() {}
		}
	}

	if ( ! class_exists( 'MeprDb' ) ) {
		class MeprDb {
			public $transactions;
			public $subscriptions;
			public $orders;

			public static function fetch() {}
			public function create_record( $table, $args, $record = true ) {}
			public function update_record( $table, $id, $args ) {}
			public function get_one_record( $table, $args, $return_type = OBJECT ) {}
			public function get_records( $table, $args = array(), $order_by = '', $limit = '' ) {}
			public function get_count( $table, $args = array() ) {}
			public function delete_records( $table, $args ) {}
		}
	}

	if ( ! class_exists( 'MeprHooks' ) ) {
		class MeprHooks {
			public static function apply_filters( $tag, $value, ...$args ) {}
			public static function do_action( $tag, ...$args ) {}
		}
	}

	if ( ! class_exists( 'MeprUtils' ) ) {
		class MeprUtils {
			public static function ts_to_mysql_date( $ts, $format = 'Y-m-d H:i:s' ) {}
			public static function db_lifetime() {}
			public static function days( $num ) {}
			public static function db_now() {}
			public static function base36_encode( $num ) {}
		}
	}

	if ( ! class_exists( 'MeprOptions' ) ) {
		class MeprOptions {
			public $integrations;

			public static function fetch() {}
			public function payment_method( $gateway ) {}
			public function thankyou_page_url( $query_params = '' ) {}
		}
	}

	if ( ! class_exists( 'MeprSubscription' ) ) {
		class MeprSubscription {
			public static $pending_str   = 'pending';
			public static $active_str    = 'active';
			public static $suspended_str = 'suspended';
			public static $cancelled_str = 'cancelled';

			public $id;
			public $user_id;
			public $product_id;
			public $subscr_id;
			public $status;
			public $created_at;
			public $trial;
			public $trial_amount;
			public $trial_total;
			public $trial_tax_amount;
			public $price;
			public $total;
			public $tax_rate;
			public $tax_amount;
			public $cc_last4;
			public $cc_exp_month;
			public $cc_exp_year;

			public function __construct( $id = null ) {}
			public function destroy() {}
			public function expire_txns() {}
			public function cancel() {}
		}
	}

	if ( ! class_exists( 'MeprProduct' ) ) {
		class MeprProduct {
			public static $access_url_str = 'access_url';

			public $ID;
			public $tax_exempt;

			public function __construct( $id = null ) {}
			public function is_one_time_payment() {}
			public function is_prorated() {}
			public function get_expires_at( $ts ) {}
			public function adjusted_price( $coupon_code = null ) {}
		}
	}

	if ( ! class_exists( 'MeprCoupon' ) ) {
		class MeprCoupon {
			public $ID;
			public $post_title;

			public function __construct( $id = null ) {}
			public static function get_one_from_code( $code ) {}
			public static function is_valid_coupon_code( $code, $product_id ) {}
		}
	}

	if ( ! class_exists( 'MeprEvent' ) ) {
		class MeprEvent extends MeprBaseMetaModel {
			public static $users_str         = 'users';
			public static $transactions_str  = 'transactions';
			public static $subscriptions_str = 'subscriptions';
			public static $drm_str           = 'drm';
			public static $login_event_str   = 'login';

			public $id;
			public $args;
			public $event;
			public $evt_id;
			public $evt_id_type;
			public $created_at;

			public function __construct( $obj = null ) {}
			public function validate() {}
			public function store() {}
			public function destroy() {}
			public function get_data() {}
			public function get_args() {}

			public static function get_one( $id, $return_type = OBJECT ) {}
			public static function get_one_by_event_and_evt_id_and_evt_id_type( $event, $evt_id, $evt_id_type, $return_type = OBJECT ) {}
			public static function get_count() {}
			public static function get_count_by_event( $event ) {}
			public static function get_count_by_evt_id_type( $evt_id_type ) {}
			public static function get_count_by_event_and_evt_id_and_evt_id_type( $event, $evt_id, $evt_id_type ) {}
			public static function get_all( $order_by = '', $limit = '' ) {}
			public static function get_all_by_event( $event, $order_by = '', $limit = '' ) {}
			public static function get_all_by_evt_id_type( $evt_id_type, $order_by = '', $limit = '' ) {}
			public static function record( $event, MeprBaseModel $obj, $args = '' ) {}
			public static function latest( $event ) {}
			public static function get_tablename( $event_type ) {}
			public static function latest_by_elapsed_days( $event, $elapsed_days ) {}
		}
	}

	// PMPro function stubs
	if ( ! function_exists( 'pmpro_getMembershipLevelForUser' ) ) {
		function pmpro_getMembershipLevelForUser( $user_id = null, $force = false ) {}
	}

	if ( ! function_exists( 'pmpro_translate_billing_period' ) ) {
		function pmpro_translate_billing_period( $period, $number = 1 ) {}
	}

	if ( ! function_exists( 'pmpro_login_url' ) ) {
		function pmpro_login_url( $redirect = '' ) {}
	}

	if ( ! function_exists( 'pmpro_set_current_user' ) ) {
		function pmpro_set_current_user() {}
	}

	if ( ! function_exists( 'pmpro_get_currency' ) ) {
		function pmpro_get_currency( $currency = null ) {}
	}

	if ( ! function_exists( 'pmpro_get_group_id_for_level' ) ) {
		function pmpro_get_group_id_for_level( $level_id ) {}
	}

	if ( ! function_exists( 'pmpro_get_level_group' ) ) {
		function pmpro_get_level_group( $group_id ) {}
	}

	if ( ! function_exists( 'pmpro_get_levels_for_group' ) ) {
		function pmpro_get_levels_for_group( $group_id ) {}
	}

	if ( ! function_exists( 'pmpro_getMemberStartdate' ) ) {
		function pmpro_getMemberStartdate( $user_id = null, $level_id = 0 ) {}
	}

	if ( ! function_exists( 'pmpro_next_payment' ) ) {
		function pmpro_next_payment( $user_id = null, $order_status = 'success', $format = 'timestamp' ) {}
	}

	if ( ! function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		function pmpro_getMembershipLevelsForUser( $user_id = null, $include_inactive = false ) {}
	}

	if ( ! function_exists( 'pmpro_getLevel' ) ) {
		function pmpro_getLevel( $level ) {}
	}

	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		function pmpro_hasMembershipLevel( $levels = null, $user_id = null ) {}
	}

	if ( ! function_exists( 'pmpro_changeMembershipLevel' ) ) {
		function pmpro_changeMembershipLevel( $level, $user_id = null, $old_level_status = 'inactive', $cancel_level = null ) {}
	}

	if ( ! function_exists( 'pmpro_cancelMembershipLevel' ) ) {
		function pmpro_cancelMembershipLevel( $level_id, $user_id = null, $status = 'inactive' ) {}
	}

	if ( ! function_exists( 'pmpro_set_expiration_date' ) ) {
		function pmpro_set_expiration_date( $user_id, $level_id, $enddate ) {}
	}

	// Add MemberOrder class stub
	if ( ! class_exists( 'MemberOrder' ) ) {
		class MemberOrder {
			public function __construct( $id = null ) {}
		}
	}

	// Add PMPro_Subscription class stub
	if ( ! class_exists( 'PMPro_Subscription' ) ) {
		class PMPro_Subscription {
			public static function get_subscriptions_for_user( $user_id, $level_id = null, $status = 'active' ) {}
		}
	}

	// Add PMPro_Membership_Level class stub
	if (!class_exists('PMPro_Membership_Level')) {
		#[AllowDynamicProperties]
		class PMPro_Membership_Level {
			public $ID;
			public $id;
			public $name;
			public $description;
			public $confirmation;
			public $initial_payment;
			public $billing_amount;
			public $cycle_number;
			public $cycle_period;
			public $billing_limit;
			public $trial_amount;
			public $trial_limit;
			public $allow_signups;
			public $expiration_number;
			public $expiration_period;
			public $categories;

			public function __construct($id = null) {}
			public function __get($key) {}
			public function get_empty_membership_level() {}
			public function get_membership_level($id) {}
			public function get_membership_level_categories($id) {}
			public function get_membership_level_object($id) {}
			public function save() {}
			public function delete() {}
		}
	}

}

namespace BuddyBossApp\AccessControls {
	class Integration_Abstract {}
}

namespace BuddyBossApp\UserSegment {
	class SegmentsAbstract {}
}

namespace BuddyBossApp\InAppPurchases {
	class IntegrationAbstract {}
}

namespace Elementor {
	class Control_Repeater {}
}

namespace ElementorPro\Modules\Forms\Classes {
	class Integration_Base {}
}

namespace FluentBooking\App\Http\Controllers {
	class IntegrationManagerController {}
}

namespace FluentForm\App\Services\Integrations {
	class IntegrationManager {}
}

namespace FluentForm\App\Http\Controllers {
	class IntegrationManagerController {}
}

namespace IfSo\PublicFace\Services\TriggersService\Triggers {
	class TriggerBase {}
}

namespace Thrive\Automator\Items {
	class App {}

	class Trigger {}

	class Data_Field {}

	class Data_Object {}
}

namespace WPPayForm\App\Services\Integrations {
	class IntegrationManager {}
}

namespace SureCart\Integrations {
	class IntegrationService {}
}

namespace SureCart\Integrations\Contracts {
	interface IntegrationInterface {}

	interface PurchaseSyncInterface {}
}

namespace memberpress\courses\lib {
	class BaseModel {
		public function initialize( $defaults = array(), $obj = null ) {}
		public function get_values() {}
	}

	class Db {
		public $attempts;
		public $answers;
		public static function fetch() {}
		public function update_record( $table, $id, $attrs ) {}
		public function create_record( $table, $attrs, $record = false ) {}
		public function delete_records( $table, $conditions ) {}
		public function get_records( $table, $conditions ) {}
		public function get_one_record( $table, $conditions ) {}
		public static function list_table( $cols, $from, $joins, $args, $order_by, $order, $paged, $search, $perpage, $search_cols ) {}
	}

	class Validate {
		public static function is_numeric( $num, $min = 0, $max = null, $field = '' ) {}
		public static function is_in_array( $value, $valid_values = array(), $field = '' ) {}
	}

	class ValidationException extends \Exception {}
}

namespace memberpress\quizzes\models {
	class Quiz {
		public static function find( $id ) {}
	}

	class Answer {
		public static function get_one( $conditions ) {}
	}

	/**
	 * Attempt model
	 *
	 * @property int $id The attempt ID
	 * @property int $quiz_id The quiz ID
	 * @property int $user_id The user ID
	 * @property int $points_awarded The total number of points awarded
	 * @property int $points_possible The total number of points possible
	 * @property int $bonus_points The total number of bonus points
	 * @property int $score The score percentage
	 * @property string $status The attempt status, 'draft' or 'complete'
	 * @property string $feedback Grader's feedback
	 * @property string $started_at Datetime in MySQL format
	 * @property string $finished_at Datetime in MySQL format
	 */
	class Attempt extends \memberpress\courses\lib\BaseModel {
		public static $draft_str    = 'draft';
		public static $pending_str  = 'pending';
		public static $complete_str = 'complete';

		public $id;
		public $quiz_id;
		public $user_id;
		public $points_awarded;
		public $points_possible;
		public $bonus_points;
		public $score;
		public $feedback;
		public $status;
		public $attempts;
		public $started_at;
		public $finished_at;

		public function __construct( $obj = null ) {}
		public function store( $validate = true ) {}
		public function destroy() {}
		public function quiz() {}
		public function user() {}
		public function is_complete() {}
		public function is_draft() {}
		public function is_pending() {}
		public function get_score() {}
		public function get_score_percent() {}
		public function validate() {}
		public function get_answers() {}
		public function requires_manual_grading() {}
		public static function list_table( $order_by = '', $order = '', $paged = '', $search = '', $perpage = 10, $quiz_id = null ) {}
		public static function get_attempts_with_ungraded_answers() {}
		public static function get_latest_attempts() {}
		public static function get_one( $conditions ) {}
	}
}

namespace memberpress\courses\models {
	class UserProgress {
		public $id;
		public static function find_one_by_user_and_lesson( $user_id, $lesson_id ) {}
		public function destroy() {}
	}
}

namespace memberpress\courses\helpers {
	class App {
		public static function is_gradebook_addon_active() {}
	}
}

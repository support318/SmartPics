<?php
/**
 * AffiliateWP Install
 *
 * @package   AffiliateWP
 * @copyright Copyright (c) 2024, Awesome Motive, Inc
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since     2.25.0
 * @author    Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP;

/**
 * Install class
 *
 * Tasks and methods related to the installation process.
 *
 * @since 2.25.0
 */
class Installation_Tools {

	/**
	 * Instance of the class
	 *
	 * @since 2.25.0
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Default page attributes
	 *
	 * @since 2.25.0
	 *
	 * @var array
	 */
	private array $page_default_attrs;

	/**
	 * Whether the Classic Editor is being used
	 *
	 * @since 2.25.0
	 *
	 * @var bool
	 */
	private bool $has_classic_editor;

	/**
	 * Constructor
	 *
	 * @since 2.25.0
	 */
	public function __construct() {
		$this->has_classic_editor = (
			class_exists( 'Classic_Editor' ) &&
			'block' !== get_option( 'classic-editor-replace' )
		);

		$this->page_default_attrs = [
			'post_status'    => 'publish',
			'post_author'    => get_current_user_id(),
			'post_type'      => 'page',
			'comment_status' => 'closed',
		];
	}

	/**
	 * Gets the instance of the class
	 *
	 * @since 2.25.0
	 *
	 * @return self|null The instance of this class.
	 */
	public static function get_instance() : ?self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Updates the page setting
	 *
	 * @since 2.25.0
	 *
	 * @param string $setting_name The setting name.
	 * @param int    $page_id The page ID.
	 *
	 * @return void
	 */
	private function update_page_setting( string $setting_name, int $page_id ) : void {
		affiliate_wp()->settings->set(
			[
				$setting_name => $page_id,
			],
			true
		);
	}

	/**
	 * Checks if a page exists for a given setting
	 *
	 * @since 2.25.0
	 *
	 * @param string $setting_name The setting name.
	 *
	 * @return bool Whether the page exists or not.
	 */
	private function page_exists( string $setting_name ) : bool {
		// Returns true if the page can be found in our settings, and it is a post.
		return ! empty( get_post( affiliate_wp()->settings->get( $setting_name, 0 ) ) );
	}

	/**
	 * Creates a page with the given attributes
	 *
	 * @since 2.25.0
	 *
	 * @param string $title          The page title.
	 * @param mixed  $content        The page content.
	 * @param string $setting_name   The setting name.
	 * @param bool   $force          Whether to force creation.
	 * @param bool   $update_setting Whether to update the setting with the page ID.
	 *
	 * @return int The page ID
	 */
	private function create_page( string $title, $content, string $setting_name, bool $force = false, bool $update_setting = true ) : int {
		if ( ! $force && $this->page_exists( $setting_name ) ) {
			return (int) affiliate_wp()->settings->get( $setting_name, 0 );
		}

		$page_id = wp_insert_post(
			array_merge(
				[
					'post_title'   => $title,
					'post_content' => $content,
				],
				$this->page_default_attrs
			)
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		if ( $update_setting ) {
			$this->update_page_setting( $setting_name, $page_id );
		}

		return $page_id;
	}

	/**
	 * Creates the Affiliate Area page
	 *
	 * @since 2.25.0
	 *
	 * @param bool $force Whether to force creation.
	 * @param bool $update_setting Whether to update the setting with the page ID.
	 *
	 * @return int The page ID
	 */
	public function create_affiliate_area_page( bool $force = true, bool $update_setting = true ) : int {

		return $this->create_page(
			__( 'Affiliate Area', 'affiliate-wp' ),
			$this->has_classic_editor
				? '[affiliate_area]'
				: serialize_block(
					[
						'blockName'    => 'affiliatewp/affiliate-area',
						'innerBlocks'  => [],
						'innerContent' => [],
						'attrs'        => [],
					]
				),
			'affiliates_page',
			$force,
			$update_setting
		);
	}

	/**
	 * Creates the Affiliate Login page
	 *
	 * @since 2.25.0
	 *
	 * @param bool $force Whether to force creation.
	 * @param bool $update_setting Whether to update the setting with the page ID.
	 *
	 * @return int The page ID
	 */
	public function create_login_page( bool $force = true, bool $update_setting = true ) : int {

		return $this->create_page(
			__( 'Affiliate Login', 'affiliate-wp' ),
			$this->has_classic_editor
				? '[affiliate_login]'
				: serialize_block(
					[
						'blockName'    => 'affiliatewp/login',
						'attrs'        => [],
						'innerContent' => [],
					]
				),
			'affiliates_login_page',
			$force,
			$update_setting
		);
	}

	/**
	 * Creates the Affiliate Registration page
	 *
	 * @since 2.25.0
	 *
	 * @param bool $force Whether to force creation.
	 * @param bool $update_setting Whether to update the setting with the page ID.
	 *
	 * @return int The page ID
	 */
	public function create_registration_page( bool $force = true, bool $update_setting = true ) : int {

		$registration_inner_blocks = [
			[
				'blockName'    => 'affiliatewp/field-name',
				'attrs'        => [
					'type' => 'name',
				],
				'innerContent' => [],
			],
			[
				'blockName'    => 'affiliatewp/field-username',
				'attrs'        => [
					'required' => true,
					'type'     => 'username',
				],
				'innerContent' => [],
			],
			[
				'blockName'    => 'affiliatewp/field-account-email',
				'attrs'        => [
					'type' => 'account',
				],
				'innerContent' => [],
			],
			[
				'blockName'    => 'affiliatewp/field-payment-email',
				'attrs'        => [
					'label' => __( 'Payment Email', 'affiliate-wp' ),
					'type'  => 'payment',
				],
				'innerContent' => [],
			],
			[
				'blockName'    => 'affiliatewp/field-website',
				'attrs'        => [
					'label' => __( 'Website URL', 'affiliate-wp' ),
					'type'  => 'websiteUrl',
				],
				'innerContent' => [],
			],
			[
				'blockName'    => 'affiliatewp/field-textarea',
				'attrs'        => [
					'label' => __( 'How will you promote us?', 'affiliate-wp' ),
					'type'  => 'promotionMethod',
				],
				'innerContent' => [],
			],
			[
				'blockName'    => 'affiliatewp/field-register-button',
				'innerContent' => [],
				'attrs'        => [],
			],
		];

		return $this->create_page(
			__( 'Affiliate Registration', 'affiliate-wp' ),
			$this->has_classic_editor
				? '[affiliate_registration]'
				: serialize_block(
					[
						'blockName'    => 'affiliatewp/registration',
						'innerBlocks'  => $registration_inner_blocks,
						'innerContent' => $registration_inner_blocks,
						'attrs'        => [],
					]
				),
			'affiliates_registration_page',
			$force,
			$update_setting
		);
	}
}

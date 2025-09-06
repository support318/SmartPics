<?php
/**
 * Editor Component
 *
 * Sets up integration code for the block editor.
 *
 * @package     AffiliateWP
 * @subpackage  Core
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.8
 */

use AffiliateWP\Installation_Tools;
use AffiliateWP\Affiliate_Area;
use AffWP\Core\Registration;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- We properly escape.

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * Core class used to set up the Editor component.
 *
 * @since 2.8
 */
final class Affiliate_WP_Editor {

	/**
	 * Set to true if blocks_init has ran.
	 *
	 * @since 2.8
	 *
	 * @var bool True if init ran, otherwise false.
	 */
	private $init_ran;

	/**
	 * Editor constructor.
	 *
	 * @since 2.8
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Sets up the default hooks and actions.
	 *
	 * @since 2.8
	 */
	private function hooks() {
		global $wp_version;

		// Set up Blocks
		add_action( 'init', [ $this, 'blocks_init' ] );

		// Set up block categories
		if ( version_compare( $wp_version, '5.8', '>=' ) ) {
			add_filter( 'block_categories_all', [ $this, 'add_block_category' ], 10, 2 );
		} else {
			add_filter( 'block_categories', [ $this, 'add_block_category' ], 10, 2 );
		}

		// Add form data to meta
		add_action( 'save_post', [ $this, 'save_submission_form_hashes' ] );
	}

	/**
	 * Registers all block assets so that they can be enqueued through the block editor
	 * in the corresponding context.
	 *
	 * @since 2.8
	 */
	public function blocks_init() {

		// Bail early if init has already ran
		if ( true === $this->init_ran ) {
			return;
		}

		$this->init_ran = true;

		$script_asset_path = AFFILIATEWP_PLUGIN_DIR . 'assets/js/editor/build/index.asset.php';
		$script_asset      = include $script_asset_path;

		wp_register_script(
			'affiliatewp-blocks-editor',
			AFFILIATEWP_PLUGIN_URL . 'assets/js/editor/build/index.js',
			$script_asset['dependencies'],
			$script_asset['version']
		);

		wp_set_script_translations(
			'affiliatewp-blocks-editor',
			'affiliate-wp',
			AFFILIATEWP_PLUGIN_DIR . 'languages'
		);

		wp_localize_script(
			'affiliatewp-blocks-editor',
			'affwp_blocks',
			[
				'terms_of_use'                 => $this->terms_of_use_defaults()['id'],
				'terms_of_use_link'            => $this->terms_of_use_defaults()['link'],
				'terms_of_use_label'           => $this->terms_of_use_defaults()['label'],
				'terms_of_use_generator'       => esc_url( affwp_admin_url( 'tools', [ 'tab' => 'terms_of_use_generator' ] ) ),
				'required_registration_fields' => affiliate_wp()->settings->get( 'required_registration_fields' ),
				'affiliate_area_forms'         => affiliate_wp()->settings->get( 'affiliate_area_forms' ),
				'allow_affiliate_registration' => affiliate_wp()->settings->get( 'allow_affiliate_registration' ),
				'affiliate_id'                 => affwp_get_affiliate_id( get_current_user_id() ),
				'affiliate_username'           => affwp_get_affiliate_username( affwp_get_affiliate_id( get_current_user_id() ) ),
				'referral_variable'            => affiliate_wp()->tracking->get_referral_var(),
				'referral_format'              => affwp_get_referral_format(),
				'pretty_referral_urls'         => affwp_is_pretty_referral_urls(),
			]
		);

		wp_register_style(
			'affiliatewp-blocks-editor',
			AFFILIATEWP_PLUGIN_URL . 'assets/css/editor.css',
			[],
			AFFILIATEWP_VERSION
		);

		// Affiliate Content block.
		register_block_type(
			'affiliatewp/affiliate-content',
			[
				'editor_script'   => 'affiliatewp-blocks-editor',
				'editor_style'    => 'affiliatewp-blocks-editor',
				'render_callback' => [ $this, 'affiliate_content_block_render_callback' ],
			]
		);

		// Non-affiliate Content block.
		register_block_type(
			'affiliatewp/non-affiliate-content',
			[
				'editor_script'   => 'affiliatewp-blocks-editor',
				'editor_style'    => 'affiliatewp-blocks-editor',
				'render_callback' => [ $this, 'non_affiliate_content_block_render_callback' ],
			]
		);

		// Opt-in block.
		register_block_type(
			'affiliatewp/opt-in',
			[
				'editor_script'   => 'affiliatewp-blocks-editor',
				'editor_style'    => 'affiliatewp-blocks-editor',
				'render_callback' => [ $this, 'opt_in_block_render_callback' ],
				'attributes'      => [
					'redirect' => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);

		// Affiliate Referral URL block.
		register_block_type(
			'affiliatewp/affiliate-referral-url',
			[
				'editor_script'   => 'affiliatewp-blocks-editor',
				'editor_style'    => 'affiliatewp-blocks-editor',
				'render_callback' => [ $this, 'affiliate_referral_url_block_render_callback' ],
				'attributes'      => [
					'url'    => [
						'type'    => 'string',
						'default' => '',
					],
					'format' => [
						'type'    => 'string',
						'default' => 'default',
					],
					'pretty' => [
						'type'    => 'string',
						'default' => 'default',
					],
				],
			]
		);

		// Affiliate Creatives block.
		register_block_type(
			'affiliatewp/affiliate-creatives',
			[
				'editor_script'   => 'affiliatewp-blocks-editor',
				'editor_style'    => 'affiliatewp-blocks-editor',
				'render_callback' => [ $this, 'affiliate_creatives_block_render_callback' ],
				'attributes'      => [
					'preview' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'number'  => [
						'type'    => 'number',
						'default' => 20,
					],
				],
			]
		);

		// Affiliate Creative block.
		register_block_type(
			'affiliatewp/affiliate-creative',
			[
				'editor_script'   => 'affiliatewp-blocks-editor',
				'editor_style'    => 'affiliatewp-blocks-editor',
				'render_callback' => [ $this, 'affiliate_creative_block_render_callback' ],
				'attributes'      => [
					'id' => [
						'type' => 'integer',
					],
				],
			]
		);

		// Finally, register the dynamic blocks.
		$this->register_dynamic_blocks();
	}

	/**
	 * Fetches default login fields.
	 *
	 * @since 2.8
	 *
	 * @return array list of default login labels.
	 */
	public function login_defaults() {
		return [
			'legend'     => __( 'Log into your account', 'affiliate-wp' ),
			'label'      => [
				'username'     => __( 'Username', 'affiliate-wp' ),
				'password'     => __( 'Password', 'affiliate-wp' ),
				'userRemember' => __( 'Remember Me', 'affiliate-wp' ),
			],
			'buttonText' => __( 'Log In', 'affiliate-wp' ),
		];
	}

	/**
	 * Fetches default registration fields.
	 *
	 * @since 2.8
	 *
	 * @return array list of default registration labels.
	 */
	public function registration_defaults() {
		return [
			'legend' => __( 'Register a new affiliate account', 'affiliate-wp' ),
		];
	}

	/**
	 * Registers blocks that should be added
	 */
	private function register_dynamic_blocks() {

		$login_defaults        = $this->login_defaults();
		$registration_defaults = $this->registration_defaults();

		register_block_type(
			'affiliatewp/affiliate-area',
			[
				'render_callback' => [ $this, 'render_affiliate_area' ],
			]
		);

		register_block_type(
			'affiliatewp/login',
			[
				'attributes'      => [
					'redirect'     => [
						'type'    => 'string',
						'default' => '',
					],
					'legend'       => [
						'type'    => 'string',
						'default' => $login_defaults['legend'],
					],
					'placeholders' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'label'        => [
						'type'    => 'object',
						'default' => [
							'username'     => $login_defaults['label']['username'],
							'password'     => $login_defaults['label']['password'],
							'userRemember' => $login_defaults['label']['userRemember'],
						],
					],
					'placeholder'  => [
						'type'    => 'object',
						'default' => [
							'username' => '',
							'password' => '',
						],
					],
					'buttonText'   => [
						'type'    => 'string',
						'default' => $login_defaults['buttonText'],
					],
				],
				'render_callback' => [ $this, 'render_login_form' ],
			]
		);

		register_block_type(
			'affiliatewp/registration',
			[
				'attributes'       => [
					'placeholders' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'legend'       => [
						'type'    => 'string',
						'default' => $registration_defaults['legend'],
					],
					'redirect'     => [
						'type' => 'string',
					],
				],
				'provides_context' => [
					'affiliatewp/placeholders' => 'placeholders',
					'affiliatewp/redirect'     => 'redirect',
					'affiliatewp/formId'       => 'formId',
				],
				'render_callback'  => [ $this, 'render_registration_form' ],
				'editor_style'     => 'affiliatewp-blocks-editor',
			]
		);

		$blocks = [
			'checkbox'          => [
				'label' => __( 'Option', 'affiliate-wp' ),
			],
			'terms-of-use'      => [
				'label'    => '',
				'required' => true,
			],
			'email'             => [
				'label' => __( 'Email Address', 'affiliate-wp' ),
			],
			'payment-email'     => [
				'label'           => __( 'Payment Email', 'affiliate-wp' ),
				'render_callback' => [ $this, 'render_field_email' ],
			],
			'account-email'     => [
				'label'           => __( 'Account Email', 'affiliate-wp' ),
				'render_callback' => [ $this, 'render_field_email' ],
			],
			'password'          => [
				'label' => __( 'Password', 'affiliate-wp' ),
			],
			'phone'             => [
				'label' => __( 'Phone Number', 'affiliate-wp' ),
			],
			'register-button'   => [
				'label' => '',
			],
			'text'              => [
				'label' => __( 'Text', 'affiliate-wp' ),
			],
			'textarea'          => [
				'label' => __( 'Message', 'affiliate-wp' ),
			],
			'username'          => [
				'label' => __( 'Username', 'affiliate-wp' ),
			],
			'name'              => [
				'label' => __( 'Name', 'affiliate-wp' ),
			],
			'website'           => [
				'label' => __( 'Website', 'affiliate-wp' ),
			],
			'select'            => [
				'label' => __( 'Select one', 'affiliate-wp' ),
			],
			'radio'             => [
				'label' => __( 'Choose one', 'affiliate-wp' ),
			],
			'checkbox-multiple' => [
				'label' => __( 'Choose several options', 'affiliate-wp' ),
			],
		];

		foreach ( $blocks as $block => $args ) {

			if ( isset( $args['render_callback'] ) ) {
				$render_callback = $args['render_callback'];
			} else {
				$render_callback = [ $this, sprintf( 'render_field_%s', str_replace( '-', '_', $block ) ) ];
			}

			register_block_type(
				"affiliatewp/field-$block",
				[
					'attributes'      => [
						'label' => [ 'type' => 'string', 'default' => isset( $args['label'] ) ? $args['label'] : '' ],
					],
					'parent'          => [ 'affiliatewp/registration' ],
					'uses_context'    => [
						'affiliatewp/placeholders',
						'affiliatewp/redirect',
						'affiliatewp/formId',
					],
					'render_callback' => $render_callback,
				]
			);
		}
	}

	/**
	 * Renders the Affiliate Content block.
	 *
	 * @since 2.8
	 *
	 * @param array  $atts    Block attributes (unused).
	 * @param string $content Block content.
	 */
	public function affiliate_content_block_render_callback( array $atts, string $content ) : string {

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return '';
		}

		return $content;
	}

	/**
	 * Renders the Non-affiliate Content block.
	 *
	 * @since 2.8
	 *
	 * @param array  $atts    Block attributes (unused).
	 * @param string $content Block content.
	 */
	public function non_affiliate_content_block_render_callback( array $atts, string $content ) : string {

		if ( affwp_is_affiliate() && affwp_is_active_affiliate() ) {
			return '';
		}

		return $content;
	}

	/**
	 * Renders the Opt-in block
	 *
	 * @since 2.8
	 *
	 * @param array  $atts    Block attributes.
	 * @param string $content Block content (unused).
	 */
	public function opt_in_block_render_callback( array $atts, string $content ) : string {

		return sprintf(
			'<div class="affwp-opt-in-form-block%s">%s</div>',
			isset( $atts['className'] ) ? ' ' . sanitize_text_field( $atts['className'] ) : '',
			( new Affiliate_WP_Shortcodes() )->opt_in_form( $atts )
		);
	}

	/**
	 * Renders the Affiliate Referral URL block.
	 *
	 * @since 2.8
	 *
	 * @param array $atts Block attributes.
	 */
	public function affiliate_referral_url_block_render_callback( array $atts ) : string {

		return sprintf(
			'<p class="affwp-referral-url-block affiliate-referral-url%s">%s</p>',
			isset( $atts['className'] ) ? ' ' . sanitize_text_field( $atts['className'] ) : '',
			( new Affiliate_WP_Shortcodes() )->referral_url( $atts )
		);
	}

	/**
	 * Renders the Affiliate Creatives block.
	 *
	 * @since 2.8
	 *
	 * @param array $atts Block attributes.
	 */
	public function affiliate_creatives_block_render_callback( array $atts ) : string {

		return sprintf(
			'<div class="affwp-creatives-block%s">%s</div>',
			isset( $atts['className'] ) ? ' ' . sanitize_text_field( $atts['className'] ) : '',
			( new Affiliate_WP_Shortcodes() )->affiliate_creatives(
				array_merge(
					$atts,
					[
						'preview' => isset( $atts['preview'] ) && true === $atts['preview'] ? 'yes' : 'no',
					]
				)
			)
		);
	}

	/**
	 * Renders the Affiliate Creative block.
	 *
	 * @since 2.8
	 *
	 * @param array $atts Block attributes.
	 */
	public function affiliate_creative_block_render_callback( array $atts ) : string {

		return sprintf(
			'<div class="affwp-creative-block%s">%s</div>',
			isset( $atts['className'] ) ? ' ' . sanitize_text_field( $atts['className'] ) : '',
			( new Affiliate_WP_Shortcodes() )->affiliate_creative( $atts )
		);
	}

	/**
	 * Adds the "AffiliateWP" category to the block editor.
	 *
	 * @since 2.8
	 *
	 * @param array $categories Array of block categories.
	 *
	 * @return array Modified categories list.
	 */
	public function add_block_category( array $categories ) : array {

		return array_merge(
			$categories,
			[
				[
					'slug'  => 'affiliatewp',
					'title' => __( 'AffiliateWP', 'affiliate-wp' ),
				],
			]
		);
	}

	/**
	 * Return user data if logged in.
	 *
	 * @return array
	 */
	public function user(): array {

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();

			return [
				'user_name'  => "{$current_user->user_firstname} {$current_user->user_lastname}",
				'user_login' => $current_user->user_login,
				'user_email' => $current_user->user_email,
				'url'        => $current_user->user_url,
			];
		}

		return [];
	}

	/**
	 * Render the Affiliate Area.
	 *
	 * @param array  $atts Block attributes.
	 * @param string $content Block content.
	 *
	 * @return string
	 */
	public function render_affiliate_area( array $atts, string $content ) : string {

		affwp_enqueue_script( 'affwp-frontend', 'affiliate_area' );

		ob_start();

		if ( is_user_logged_in() && affwp_is_affiliate() ) {
			affiliate_wp()->templates->get_template_part( 'dashboard' );
			return ob_get_clean();
		}

		if ( is_user_logged_in() && ! affiliate_wp()->settings->get( 'allow_affiliate_registration' ) ) {
			affiliate_wp()->templates->get_template_part( 'no', 'access' );
		}

		// For users that are not authenticated and are using a separate page for login, show the new message.
		if (
			empty( $content ) &&
			! is_user_logged_in() &&
			! Affiliate_Area::get_instance()->is_affiliate_area_the_login_page()
		) {
			echo Affiliate_Area::get_instance()->get_unauthorized_access_message();
		}

		// Render the inner blocks (registration and login); prior to 2.25.0, inner blocks are not allowed since then.
		echo do_blocks( $content );

		return ob_get_clean();
	}

	/**
	 * Render the login form
	 *
	 * @param array $atts Block attributes.
	 *
	 * @return string Form markup or success message when form submits successfully.
	 */
	public function render_login_form( array $atts ) : string {

		if ( is_user_logged_in() ) {
			return '';
		}

		$login_defaults = $this->login_defaults();
		$redirect       = isset( $atts['redirect'] ) ? $atts['redirect'] : '';
		$placeholders   = isset( $atts['placeholders'] ) && true === $atts['placeholders'] ? true : false;

		$label_username      = isset( $atts['label']['username'] ) ? $atts['label']['username'] : $login_defaults['label']['username'];
		$label_password      = isset( $atts['label']['password'] ) ? $atts['label']['password'] : $login_defaults['label']['password'];
		$label_user_remember = isset( $atts['label']['userRemember'] ) ? $atts['label']['userRemember'] : $login_defaults['label']['userRemember'];

		$placeholder_username = '';
		$placeholder_password = '';

		if ( $placeholders ) {
			$placeholder_username = isset( $atts['placeholder']['username'] ) ? $atts['placeholder']['username'] : $login_defaults['placeholder']['username'];
			$placeholder_password = isset( $atts['placeholder']['password'] ) ? $atts['placeholder']['password'] : $login_defaults['placeholder']['password'];
		}

		$button_text = isset( $atts['buttonText'] ) ? $atts['buttonText'] : $login_defaults['buttonText'];
		$legend      = isset( $atts['legend'] ) ? $atts['legend'] : $login_defaults['legend'];

		$classes = [
			isset( $atts['className'] ) ? $atts['className'] : '',
			'affwp-form',
		];

		$form_id_suffix = uniqid();

		ob_start();
		affiliate_wp()->login->print_errors();
		?>

		<form id="affwp-login-form-<?php echo esc_attr( $form_id_suffix ); ?>" class="<?php echo esc_attr( trim( implode( ' ', $classes ) ) ); ?> affwp-login-form" action="" method="post">
			<?php
			/**
			 * Fires at the top of the affiliate login form template
			 *
			 * @since 1.0
			 */
			do_action( 'affwp_affiliate_login_form_top' );
			?>

			<fieldset>
				<legend><?php echo esc_attr( $legend ); ?></legend>

				<?php
				/**
				 * Fires immediately prior to the affiliate login form template fields.
				 *
				 * @since 1.0
				 */
				do_action( 'affwp_login_fields_before' );
				?>

				<p>
					<label for="affwp-login-user-login"><?php echo esc_html( $label_username ); ?></label>
					<input
						id="affwp-login-user-login"
						class="required"
						type="text"
						name="affwp_user_login"
						title="<?php echo esc_attr( $label_username ); ?>"
						placeholder="<?php echo esc_attr( $placeholder_username ); ?>"
					/>
				</p>

				<p>
					<label for="affwp-login-user-pass"><?php echo esc_html( $label_password ); ?></label>
					<input
						id="affwp-login-user-pass"
						class="password required"
						type="password"
						name="affwp_user_pass"
						title="<?php echo esc_attr( $label_password ); ?>"
						placeholder="<?php echo esc_attr( $placeholder_password ); ?>"
					/>
				</p>

				<p>
					<label class="affwp-user-remember" for="affwp-user-remember">
						<input
							id="affwp-user-remember"
							type="checkbox"
							name="affwp_user_remember"
							value="1"
						/> <?php echo esc_html( $label_user_remember ); ?>
					</label>
				</p>


				<p>
					<?php if ( esc_url( $redirect ) ) : ?>
						<input type="hidden" name="affwp_redirect" value="<?php echo esc_url( $redirect ); ?>"/>
					<?php endif; ?>
					<input type="hidden" name="affwp_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'affwp-login-nonce' ) ); ?>"/>
					<input type="hidden" name="affwp_action" value="user_login"/>

					<?php
					// Render CAPTCHA field and submit button.
					echo AffWP_Captcha_Manager::render_captcha_field( 'login', $form_id_suffix, $button_text );
					?>
				</p>

				<p class="affwp-lost-password">
					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'affiliate-wp' ); ?></a>
				</p>

				<?php
				/**
				 * Fires immediately after the affiliate login form template fields.
				 *
				 * @since 1.0
				 */
				do_action( 'affwp_login_fields_after' );
				?>
			</fieldset>

			<?php
			/**
			 * Fires at the bottom of the affiliate login form template (inside the form element).
			 *
			 * @since 1.0
			 */
			do_action( 'affwp_affiliate_login_form_bottom' );
			?>
		</form>

		<?php

		return ob_get_clean();
	}

	/**
	 * Render the form
	 *
	 * @param array  $atts    Block attributes.
	 * @param string $content Block content.
	 *
	 * @return string Form markup or success message when form submits successfully.
	 */
	public function render_registration_form( array $atts, string $content, WP_Block $block ) : string {

		if ( ! affiliate_wp()->settings->get( 'allow_affiliate_registration' ) || affwp_is_affiliate() ) {
			return '';
		}

		$registration_defaults = $this->registration_defaults();

		$legend = $atts['legend'] ?? $registration_defaults['legend'];

		$classes = [
			$atts['className'] ?? '',
			'affwp-form',
		];

		// Generate form ID suffix - use existing if available for consistency
		$form_id_suffix = $block->context['affiliatewp/formId'] ?? uniqid();

		// Ensure form ID is passed to children blocks through context.
		$block->context['affiliatewp/formId'] = $form_id_suffix;

		ob_start();

		affiliate_wp()->register->print_errors();
		?>

		<form id="affwp-register-form-<?php echo esc_attr( $form_id_suffix ); ?>" class="<?php echo esc_attr( trim( implode( ' ', $classes ) ) ); ?> affwp-register-form" action="" method="post">

			<?php
			/**
			 * Fires at the top of the affiliate registration templates' form (inside the form element).
			 *
			 * @since 1.0
			 */
			do_action( 'affwp_affiliate_register_form_top' );
			?>

			<fieldset>
				<legend><?php echo esc_attr( $legend ); ?></legend>

				<?php
				/**
				 * Fires just before the affiliate registration templates' form fields.
				 *
				 * @since 1.0
				 */
				do_action( 'affwp_register_fields_before' );
				?>

				<?php echo do_blocks( $content ); ?>

				<?php
				/**
				 * Fires inside of the affiliate registration form template (inside the form element, after the submit button).
				 *
				 * @since 1.0
				 */
				do_action( 'affwp_register_fields_after' );
				?>
			</fieldset>

			<?php
			/**
			 * Fires at the bottom of the affiliate registration form template (inside the form element).
			 *
			 * @since 1.0
			 */
			do_action( 'affwp_affiliate_register_form_bottom' );
			?>

		</form>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render the classes.
	 *
	 * @param array $classes Array of classes.
	 *
	 * @return string Markup for the class attribute.
	 */
	public function render_classes( $classes = [] ) {

		$classes = array_filter( $classes );

		if ( empty( $classes ) ) {
			return;
		}

		return sprintf( ' class="%s"', esc_attr( implode( ' ', $classes ) ) );
	}

	/**
	 * Render field username input.
	 *
	 * Render username field when using blocks.
	 *
	 * @param array    $atts Field attributes.
	 * @param string   $content Field content.
	 * @param WP_Block $block Block object.
	 *
	 * @return false|string
	 */
	public function render_field_username( array $atts, string $content, WP_Block $block ) {

		$name = 'affwp_user_login';

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'required'    => 'required',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : '',
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',
				'disabled'    => is_user_logged_in() ? 'disabled' : '',

			]
		);

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: ( isset( $this->user()['user_login'] ) ? $this->user()['user_login'] : '' );

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], 'affwp-user-login', '', $block ), 'data' ); ?>

			<input
				type="text"
				id="affwp-user-login"
				name="<?php echo esc_attr( $name ); ?>"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				<?php echo esc_attr( $atts['required'] ); ?>
				<?php echo esc_html( $atts['disabled'] ); ?>
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-name' ] ), 'strip' ); ?>
			/>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Handle wp_kses when content is null.
	 *
	 * @since 2.23.0
	 *
	 * @param string $content Content.
	 * @param array  $args    Arguments.
	 *
	 * @return mixed
	 */
	private function wp_kses( $content, $args ) {

		if ( ! is_string( $content ) ) {
			return $content; // Might be null...
		}

		return wp_kses( $content, $args );
	}

	/**
	 * Render field name input.
	 *
	 * Render name field when using blocks.
	 *
	 * @param array    $atts Field attributes.
	 * @param string   $content Field content.
	 * @param WP_Block $block Block object.
	 *
	 * @return false|string
	 */
	public function render_field_name( array $atts, string $content, WP_Block $block ) {

		$name = 'affwp_user_name';

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'required'    => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : '',
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',
				'disabled'    => is_user_logged_in() ? 'disabled' : '',

			]
		);

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: ( isset( $this->user()['user_name'] ) ? $this->user()['user_name'] : '' );

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], 'affwp-user-name', '', $block ), 'data' ); ?>

			<input
				type="text"
				id="affwp-user-name"
				name="<?php echo esc_attr( $name ); ?>"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				<?php echo esc_attr( $atts['required'] ); ?>
				<?php echo esc_html( $atts['disabled'] ); ?>
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-name' ] ), 'strip' ); ?>
			/>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render the text field.
	 *
	 * Render text field when using blocks.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the text field.
	 */
	public function render_field_text( array $atts, string $content, WP_Block $block ) {

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'type'        => isset( $atts['type'] ) ? $atts['type'] : '',
				'required'    => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : '',
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',
				'disabled'    => isset( $atts['type'] ) && 'username' === $atts['type'] && is_user_logged_in() ? 'disabled' : '',
			]
		);

		switch ( $atts['type'] ) {
			case 'username':
				$label_slug = 'affwp-user-login';
				$name       = 'affwp_user_login';
				$value      = isset( $this->user()['user_login'] ) ? $this->user()['user_login'] : '';
				break;
			case 'name':
				$label_slug = 'affwp-user-name';
				$name       = 'affwp_user_name';
				$value      = isset( $this->user()['user_name'] ) ? $this->user()['user_name'] : '';
				break;
			default:
				$label_slug = 'affwp-' . sanitize_title( $atts['label'] );
				$name       = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_text';
				$value      = '';
				break;
		}

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: $value;

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>

			<input
				type="text" id="<?php echo esc_attr( $label_slug ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				<?php echo esc_attr( $atts['required'] ); ?>
				<?php echo esc_html( $atts['disabled'] ); ?>
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-name' ] ), 'strip' ); ?>
			/>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render the phone field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the phone field.
	 */
	public function render_field_phone( array $atts, string $content, WP_Block $block ) {

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'required'    => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : __( 'Phone Number', 'affiliate-wp' ),
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',

			]
		);

		$label_slug = 'affwp-' . sanitize_title( $atts['label'] );
		$name       = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_phone';

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: '';

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>

			<input
				type="tel"
				id="<?php echo esc_attr( $label_slug ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				<?php echo esc_attr( $atts['required'] ); ?>
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-phone' ] ), 'strip' ); ?>
			/>
		</p>


		<?php
		return ob_get_clean();
	}

	/**
	 * Render the textarea field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the textarea field.
	 */
	public function render_field_textarea( array $atts, string $content, WP_Block $block ) {

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'type'        => isset( $atts['type'] ) ? $atts['type'] : '',
				'required'    => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : __( 'Message', 'affiliate-wp' ),
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',
			]
		);

		switch ( $atts['type'] ) {
			case 'promotionMethod':
				$label_slug = 'affwp-promotion-method';
				$name       = 'affwp_promotion_method';
				break;

			default:
				$label_slug = 'affwp-' . sanitize_title( $atts['label'] );
				$name       = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_textarea';
				break;
		}

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: '';

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>
			<textarea
				name="<?php echo esc_attr( $name ); ?>"
				id="<?php echo esc_attr( $label_slug ); ?>"
				rows="5"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				<?php echo esc_attr( $atts['required'] ); ?>
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-textarea' ] ), 'strip' ); ?>
				><?php echo esc_attr( $value ); ?></textarea>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render the checkbox field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the checkbox field.
	 */
	public function render_field_checkbox( array $atts, string $content, WP_Block $block ) {

		$atts = array_merge(
			$atts,
			[
				'required' => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'    => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : '',

			]
		);

		$label_slug = 'affwp-' . sanitize_title( $atts['label'] );
		$name       = str_replace( '-', '_', $label_slug ) . '_checkbox';
		$value      = '1';

		$current = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: false;

		ob_start();
		?>
		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<input
				type="checkbox"
				id="<?php echo esc_attr( $label_slug ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				<?php echo $this->wp_kses( checked( $value, $current, false ), 'strip' ); ?>
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-checkbox' ] ), 'strip' ); ?>
				<?php echo esc_attr( $atts['required'] ); ?>
			/>

			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Terms of Use defaults.
	 *
	 * @since 2.10.0
	 *
	 * @return array Default terms of use settings.
	 */
	private function terms_of_use_defaults(): array {
		$id    = affiliate_wp()->settings->get( 'terms_of_use' ) ? affiliate_wp()->settings->get( 'terms_of_use' ) : '';
		$link  = $id ? get_permalink( $id ) : '';
		$label = $link ? sprintf(
			// translators: %1$s: open link tag, %2$s close link tag.
			__( 'Agree to our %1$sTerms of Use and Privacy Policy%2$s', 'affiliate-wp' ),
			'<a href="' . $link . '" target="_blank">',
			'</a>'
		) : __( 'Agree to our Terms of Use and Privacy Policy', 'affiliate-wp' );

		return [
			'id'    => $id,
			'link'  => esc_url( $link ),
			'label' => $label,
		];
	}

	/**
	 * Render the Terms of Use checkbox.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the terms of use checkbox.
	 */
	public function render_field_terms_of_use( array $atts, string $content, WP_Block $block ) {

		$atts = array_merge(
			$atts,
			[
				'label'    => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : $this->terms_of_use_defaults()['label'],
				'style'    => isset( $atts['style'] ) ? $atts['style'] : '',
				'id'       => isset( $atts['id'] ) ? $atts['id'] : $this->terms_of_use_defaults()['id'],
				'required' => isset( $atts['required'] ) ? '' : 'required',

			]
		);

		// Return if no label.
		if ( ! $atts['label'] ) {
			return '';
		}

		// Bail if a post ID can't be found.
		if ( 2 === $atts['style'] ) {

			if ( ! $atts['id'] ) {
				// An ID is always required for this style.
				return '';
			}

			// Bail if attempting to display the current page within the preview.
			if ( get_the_ID() === $atts['id'] ) {
				return '';
			}

			$terms_of_use = get_post( $atts['id'] );

			// Bail if no terms found.
			if ( ! $terms_of_use ) {
				return '';
			}
		}

		$label_slug = 'affwp-' . sanitize_title( $atts['label'] );
		$name       = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_terms-of-use';
		$value      = '1';

		$current = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: false;

		$checked = checked( $value, $current, false );

		$field_classes = [
			'affwp-field',
			'affwp-field-terms-of-use',
		];

		$classes = [ isset( $atts['className'] ) ? $atts['className'] : '' ];

		ob_start();

		if ( 2 === $atts['style'] ) :

			?>
			<div class="affwp-field-terms-of-use-content">
				<?php echo isset( $terms_of_use, $terms_of_use->post_content ) ? wp_kses_post( $terms_of_use->post_content ) : ''; ?>
			</div>

			<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
				<input
					type="checkbox"
					id="<?php echo esc_attr( $label_slug ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					<?php echo $this->wp_kses( $checked, 'strip' ); ?>
					<?php echo $this->wp_kses( $this->render_classes( $field_classes ), 'strip' ); ?>
					<?php echo esc_attr( $atts['required'] ); ?>
				/>

				<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>
			</p>

		<?php else : ?>

			<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
				<input
					type="checkbox" id="<?php echo esc_attr( $label_slug ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					<?php echo $this->wp_kses( $checked, 'strip' ); ?>
					<?php echo $this->wp_kses( $this->render_classes( $field_classes ), 'strip' ); ?>
					<?php echo esc_attr( $atts['required'] ); ?>
				/>

				<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>
			</p>

			<?php
		endif;
		return ob_get_clean();
	}

	/**
	 * Render the select field
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object
	 *
	 * @return false|string Markup for the select field.
	 */
	public function render_field_select( array $atts, string $content, WP_Block $block ) {

		$options = isset( $atts['options'] ) ? $atts['options'] : [];

		$label = isset( $atts['label'] ) && ! empty( $atts['label'] ) ? __( $atts['label'], 'affiliate-wp' ) : '';

		$label_slug = 'affwp-' . sanitize_title( $label );

		$name = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_select';

		$field_classes = [
			'affwp-field',
			'affwp-field-select',
		];

		$classes = [
			isset( $atts['className'] ) ? $atts['className'] : '',
		];

		$required_attr = isset( $atts['required'] ) && $atts['required'] ? 'required' : '';

		$label_classes = '';

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $label, $label_slug, $label_classes, $block ), 'data' ); ?>

			<select
				name="<?php echo esc_attr( $name ); ?>"
				id="<?php echo esc_attr( $label_slug ); ?>"
				<?php echo $this->wp_kses( $this->render_classes( $field_classes ), 'strip' ); ?>
				<?php echo esc_attr( $required_attr ); ?>>
				<option value=""></option>
				<?php foreach ( $options as $option_index => $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render the radio field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object
	 *
	 * @return false|string Markup for the radio field.
	 */
	public function render_field_radio( array $atts, string $content, WP_Block $block ) {

		$options = isset( $atts['options'] ) ? $atts['options'] : [];

		$label = isset( $atts['label'] ) && ! empty( $atts['label'] ) ? __( $atts['label'], 'affiliate-wp' ) : '';

		$label_slug = 'affwp-' . sanitize_title( $label );

		$name = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_radio';

		$field_classes = [
			'affwp-field',
			'affwp-field-radio',
		];

		$required_attr = isset( $atts['required'] ) && $atts['required'] ? 'required' : '';

		$classes = [
			isset( $atts['className'] ) ? $atts['className'] : '',
		];

		$label_classes = '';

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $label, $label_slug, $label_classes, $block ), 'data' ); ?>

			<?php foreach ( $options as $option_index => $option ) : ?>
				<label class="affwp-label-radio">
					<input
						type="radio"
						name="<?php echo esc_attr( $name ); ?>"
						value="<?php echo esc_attr( $option ); ?>"
						<?php echo esc_attr( $required_attr ); ?>
					/><?php echo esc_html( $option ); ?>
				</label>
			<?php endforeach; ?>

		</p>

		<?php

		return ob_get_clean();
	}

	/**
	 * Render the Checkbox Multiple field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the checkbox multiple field.
	 */
	public function render_field_checkbox_multiple( array $atts, string $content, WP_Block $block ) {

		$options = isset( $atts['options'] ) ? $atts['options'] : [];

		$label = isset( $atts['label'] ) && ! empty( $atts['label'] ) ? __( $atts['label'], 'affiliate-wp' ) : '';

		$label_slug = 'affwp-' . sanitize_title( $label );

		$name = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_checkbox-multiple';

		$field_classes = [
			'affwp-field',
			'affwp-field-checkbox-multiple',
		];

		$classes = [
			isset( $atts['className'] ) ? $atts['className'] : '',
		];

		$label_classes = '';

		ob_start();

		?>

		<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $label, $label_slug, $label_classes, $block ), 'data' ); ?>

			<?php foreach ( $options as $option_index => $option ) : ?>
				<label class="affwp-label-checkbox-multiple">
					<input
						type="checkbox"
						name="<?php echo esc_attr( $name ); ?>[]"
						value="<?php echo esc_attr( $option ); ?>" /><?php echo esc_html( $option ); ?>
				</label>
			<?php endforeach; ?>

		</p>

		<?php

		return ob_get_clean();
	}

	/**
	 * Render the password fields.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the password fields.
	 */
	public function render_field_password( array $atts, string $content, WP_Block $block ) {

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'label'               => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : __( 'Password', 'affiliate-wp' ),
				'label_confirm'       => isset( $atts['labelConfirm'] ) && ! empty( $atts['labelConfirm'] ) ? $atts['labelConfirm'] : __( 'Confirm Password', 'affiliate-wp' ),
				'placeholder'         => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',
				'placeholder_confirm' => isset( $atts['placeholderConfirm'] ) && $show_placeholders ? $atts['placeholderConfirm'] : '',

			]
		);

		$name = 'affwp_password_text';

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: '';

		$field_classes = [
			'affwp-field',
			'affwp-field-password',
		];

		$classes = [ isset( $atts['className'] ) ? $atts['className'] : '' ];

		ob_start();
		?>

		<?php if ( ! is_user_logged_in() ) : ?>
			<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
				<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], 'affwp-user-pass', '', $block ), 'data' ); ?>

				<input
					type="password"
					id="affwp-user-pass"
					value="<?php echo esc_attr( $value ); ?>"
					name="<?php echo esc_attr( $name ); ?>"
					title="<?php echo esc_attr( $atts['label'] ); ?>"
					placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
					required
					<?php echo $this->wp_kses( $this->render_classes( $field_classes ), 'strip' ); ?>
				/>
			</p>

			<p<?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?>>
				<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label_confirm'], 'affwp-user-pass2', '', $block ), 'data' ); ?>

				<input
					type="password"
					id="affwp-user-pass2"
					name="<?php echo esc_attr( $name ); ?>_confirm"
					title="<?php echo esc_attr( $atts['label_confirm'] ); ?>"
					placeholder="<?php echo esc_attr( $atts['placeholder_confirm'] ); ?>"
					required
					<?php echo $this->wp_kses( $this->render_classes( $field_classes ), 'strip' ); ?>
				/>
			</p>

		<?php endif; ?>

		<?php

		return ob_get_clean();
	}

	/**
	 * Render the website field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the website field.
	 */
	public function render_field_website( array $atts, string $content, WP_Block $block ) {

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'type'        => isset( $atts['type'] ) ? $atts['type'] : '',
				'required'    => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : __( 'Website URL', 'affiliate-wp' ),
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',

			]
		);

		switch ( $atts['type'] ) {
			case 'websiteUrl':
				$label_slug = 'affwp-user-url';
				$name       = str_replace( '-', '_', $label_slug );
				$value      = isset( $this->user()['url'] ) ? $this->user()['url'] : '';
				break;
			default:
				$label_slug = 'affwp-' . sanitize_title( $label );
				$name       = str_replace( '-', '_', $label_slug ) . '_website';
				$value      = '';
				break;
		}

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: $value;

		ob_start();
		?>

		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>

			<input
				type="url"
				id="<?php echo esc_attr( $label_slug ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-website' ] ), 'strip' ); ?>
				<?php echo esc_attr( $atts['required'] ); ?>
			/>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render the email field.
	 *
	 * @param array    $atts Block attributes.
	 * @param string   $content Block content.
	 * @param WP_Block $block WP_Block Object.
	 *
	 * @return false|string Markup for the email field.
	 */
	public function render_field_email( array $atts, string $content, WP_Block $block ) {

		$show_placeholders = isset( $block->context, $block->context['affiliatewp/placeholders'] )
			? $block->context['affiliatewp/placeholders']
			: '';

		$atts = array_merge(
			$atts,
			[
				'type'        => isset( $atts['type'] ) ? $atts['type'] : '',
				'required'    => isset( $atts['required'] ) && $atts['required'] ? 'required' : '',
				'label'       => isset( $atts['label'] ) && ! empty( $atts['label'] ) ? $atts['label'] : __( 'Email Address', 'affiliate-wp' ),
				'placeholder' => isset( $atts['placeholder'] ) && $show_placeholders ? $atts['placeholder'] : '',

			]
		);

		$value    = '';
		$disabled = '';

		switch ( $atts['type'] ) {
			case 'payment':
				$label_slug = 'affwp-payment-email';
				$name       = 'affwp_payment_email';
				break;
			case 'account':
				$label_slug       = 'affwp-user-email';
				$name             = 'affwp_user_email';
				$value            = isset( $this->user()['user_email'] ) ? $this->user()['user_email'] : '';
				$disabled         = is_user_logged_in() ? ' disabled="disabled"' : '';
				$atts['required'] = true; // Account email is always required.
				break;
			default:
				$label_slug = 'affwp-' . sanitize_title( $atts['label'] );
				$name       = esc_attr( str_replace( '-', '_', $label_slug ) ) . '_email';
				break;
		}

		$value = isset( $_REQUEST['affwp_register_nonce'] ) && wp_verify_nonce( $_REQUEST['affwp_register_nonce'], 'affwp-register-nonce' ) && isset( $_REQUEST[ $name ] )
			? $_REQUEST[ $name ]
			: $value;

		ob_start();
		?>
		<p<?php echo $this->wp_kses( $this->render_classes( [ isset( $atts['className'] ) ? $atts['className'] : '' ] ), 'strip' ); ?>>
			<?php echo $this->wp_kses( $this->render_field_label( $atts, $atts['label'], $label_slug, '', $block ), 'data' ); ?>

			<input
				type="email"
				id="<?php echo esc_attr( $label_slug ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
				title="<?php echo esc_attr( $atts['label'] ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				<?php echo $this->wp_kses( $this->render_classes( [ 'affwp-field', 'affwp-field-email' ] ), 'strip' ); ?>
				<?php echo esc_attr( $atts['required'] ); ?>
				<?php echo esc_html( $disabled ); ?>
			/>
		</p>

		<?php
		return ob_get_clean();
	}

	/**
	 * Generate the form field label.
	 *
	 * @param array $atts        Block attributes.
	 * @param mixed $field_label Block content.
	 *
	 * @return mixed Form field label markup.
	 */
	public function render_field_label( $atts, $field_label, $label_for, $label_classes, $block ) {

		$label = isset( $field_label ) ? $field_label : '';

		/**
		 * Filter the required text in the field label.
		 *
		 * @param string $field_label Form field label text.
		 */
		$required_text = (string) apply_filters( 'affwp_registration_form_label_required_text', __( '(required)', 'affiliate-wp' ), $field_label );

		// Checkboxes don't need required text.
		if ( 'affiliatewp/field-checkbox' === $block->name ) {
			$required_text = '';
		}

		$required_attr = ( isset( $atts['required'] ) && $atts['required'] ) ? 'required' : '';

		$required_label = ! empty( $required_attr ) || ( 'affiliatewp/field-password' === $block->name ) ? sprintf( ' <span class="required">%s</span>', esc_html( $required_text ) ) : '';

		/*
		 * Format an array of allowed HTML tags and attributes for the $required_label value.
		 *
		 * @link https://codex.wordpress.org/Function_Reference/wp_kses
		 */
		$allowed_html = [
			'span' => [ 'class' => [] ],
		];

		if ( ! isset( $atts['hidden'] ) ) {
			printf(
				'<label for="%1$s"%2$s class="affwp-field-label">%3$s%4$s</label>',
				esc_attr( $label_for ),
				$label_classes ? ' class="' . esc_attr( $label_classes ) . '"' : '',
				wp_kses_post( $label ),
				wp_kses( $required_label, $allowed_html )
			);
		}
	}


	/**
	 * Renders the form submit button.
	 *
	 * @since 2.18.0 (Aubrey Portwood) Updated to use self::get_parent_form() to obtain the proper form for the registration button.
	 *
	 * @param array    $atts    Block attributes.
	 * @param string   $content Block contents.
	 * @param WP_Block $block   WP Block object.
	 *
	 * @return false|string Form submit button markup. Empty string if no form could be found.
	 */
	public function render_field_register_button( array $atts, string $content, WP_Block $block ) {

		$block_context = isset( $block->context ) ? $block->context : '';
		$redirect      = isset( $block_context['affiliatewp/redirect'] ) ? $block_context['affiliatewp/redirect'] : '';
		$btn_text      = isset( $atts['text'] ) ? $atts['text'] : __( 'Register', 'affiliate-wp' );
		$form          = $this->get_parent_form( $block ); // Get the parent submission form the button is in.
		$post_id       = get_the_ID();
		$hash_data     = $this->get_submission_forms_hash_data( $post_id );
		$form_hash     = '';

		if ( is_wp_error( $form ) || ! isset( $hash_data['method'] ) ) {
			return '';
		}

		if ( isset( $hash_data['method'] ) && 'checksum' === $hash_data['method'] ) {
			$form_hash = $form->get_checksum();
		}

		// If no checksum was found, we are probably using the old hash method.
		if ( empty( $form_hash ) ) {
			$form_hash = $form->get_hash();
		}

		$classes = [
			isset( $atts['className'] ) ? $atts['className'] : '',
			'button',
		];

		ob_start();
		?>


		<?php
		/**
		 * Fires inside of the affiliate registration form template (inside the form element, prior to the submit button).
		 *
		 * @since 1.0
		 */
		do_action( 'affwp_register_fields_before_submit' );
		?>

		<input type="hidden" name="affwp_honeypot" value=""/>
		<input type="hidden" name="affwp_redirect" value="<?php echo esc_url( $redirect ); ?>"/>
		<input type="hidden" name="affwp_register_nonce" value="<?php echo esc_attr( wp_create_nonce( 'affwp-register-nonce' ) ); ?>"/>
		<input type="hidden" name="affwp_action" value="affiliate_register"/>
		<?php if ( ! is_wp_error( $form ) ) : ?>
			<input type="hidden" name="affwp_post_id" value="<?php echo esc_attr( $post_id ); ?>"/>
			<input type="hidden" name="affwp_block_hash" value="<?php echo esc_attr( $form_hash ); ?>">
		<?php endif; ?>

		<?php
		// Handle reCAPTCHA v3 vs other CAPTCHA types differently.
		$captcha_type = AffWP_Captcha_Manager::get_active_type();
		$version      = affiliate_wp()->settings->get( 'recaptcha_type', 'v2' );


		if ( 'recaptcha' === $captcha_type && 'v3' === $version ) {
			// reCAPTCHA v3 requires submit button integration - use full field method.
			// Ensure we use the same form ID suffix that was used for the form element
			$form_id_suffix = $block->context['affiliatewp/formId'] ?? null;
			
			// If no context found, try to find form on page and extract ID
			if ( null === $form_id_suffix ) {
				// This should rarely happen but provides a safety net
				$form_id_suffix = uniqid();
			}
			
			$captcha_output = AffWP_Captcha_Manager::render_captcha_field( 'register', $form_id_suffix, $btn_text );

			// Apply custom classes to the submit button in the output.
			$class_html     = $this->wp_kses( $this->render_classes( $classes ), 'strip' );
			$class_attr     = trim( str_replace( 'class="', '', $class_html ) );
			$captcha_output = str_replace( 'class="button g-recaptcha"', 'class="button g-recaptcha ' . $class_attr . '"', $captcha_output );

			echo $captcha_output;
		} else {
			// For v2, hCaptcha, Turnstile - render widget separately then submit button.
			echo AffWP_Captcha_Manager::render_captcha_widget( 'register' );
			?>

			<input <?php echo $this->wp_kses( $this->render_classes( $classes ), 'strip' ); ?> type="submit" value="<?php echo esc_attr( $btn_text ); ?>" />
			<?php
		}
		?>

		<?php
		/**
		 * Fires inside of the affiliate registration form template (inside the form element, after the submit button).
		 *
		 * @since 1.0
		 */
		do_action( 'affwp_register_fields_after' );
		?>

		<?php

		return ob_get_clean();
	}

	/**
	 * Get the parent submission form the button is in.
	 *
	 * @since 2.18.0
	 *
	 * @param WP_Block $block The button block.
	 *
	 * @return \AffWP\Core\Registration\Form_Field_Container|WP_Error The parent submission form or error.
	 */
	private function get_parent_form( WP_Block $block ) {

		if ( 'affiliatewp/field-register-button' !== $block->name ) {

			return new WP_Error(
				'not_registration_button_block',
				__( "Can only find parent of 'affiliatewp/field-register-button' block type." ),
				[
					'block' => $block,
				]
			);
		}

		foreach ( $this->get_submission_forms( get_the_ID() ) as $form ) {

			if ( empty( $form->get_block_name() ) ) {
				continue; // There is no way to get the parent form if the block name is empty.
			}

			// Note, the parent block name may also be empty, that's why we did the test before.
			if ( $this->get_block_parent_name( $block ) !== $form->get_block_name() ) {
				continue; // Skip forms that don't have the parent's block name.
			}

			return $form;
		}

		return new WP_Error(
			'no_parent_submission_form',
			sprintf(
				// Translators: %s is the name of the block, e.g. affiliatewp/field-register-button.
				__( 'No parent found for block: %s' ),
				$block->name
			),
			[
				'block' => $block,
			]
		);
	}

	/**
	 * Get a blocks (parent) name.
	 *
	 * @since 2.18.0
	 *
	 * @param WP_Block $block The block.
	 *
	 * @return string Empty if none.
	 */
	private function get_block_parent_name( WP_Block $block ) : string {
		return $block->block_type->parent[0] ?? '';
	}

	/**
	 * Retrieves the block names for submission form types.
	 *
	 * @since 2.8
	 *
	 * @return string[] List of block names considered submission form types.
	 */
	protected function get_submission_form_types() {
		return [ 'login', 'registration', 'affiliate-area' ];
	}

	/**
	 * Retrieve the submission form fields, given an affiliateWP registration block.
	 *
	 * @param WP_Block|array $form_block WP_Block object or parsed block array.
	 *
	 * @return array List of registration form fields for the specified block.
	 * @since 2.8
	 * @since 2.11.0 Added block_attrs to Form_Field_Container object.
	 */
	public function get_submission_form_fields( $form_block ) : array {

		if ( $form_block instanceof WP_Block ) {
			$form_block = $form_block->parsed_block;
		}

		$result = [];

		$form_types = $this->get_submission_form_types();

		foreach ( $form_types as $type ) {

			if ( "affiliatewp/{$type}" !== $form_block['blockName'] ) {
				continue;
			}

			foreach ( $form_block['innerBlocks'] as $field ) {
				$inner_block   = WP_Block_Type_Registry::get_instance()->get_registered( $field['blockName'] );
				$default_label = '';

				if ( isset( $inner_block->attributes['label'] ) && isset( $inner_block->attributes['label']['default'] ) ) {
					$default_label = $inner_block->attributes['label']['default'];
				}

				$default_attrs = [
					'label'    => $default_label,
					'type'     => '',
					'required' => false,
				];

				$attrs = wp_parse_args( $field['attrs'], $default_attrs );

				// Ignore submit button.
				if ( 'affiliatewp/field-register-button' === $field['blockName'] ) {
					continue; // Skip if field is a register button.
				}

				$result[] = new Registration\Form_Field_Container(
					[
						'field_type'  => $field['blockName'],
						'label'       => $attrs['label'],
						'legacy_type' => $attrs['type'],
						'required'    => $attrs['required'],
						'block_attrs' => $field['attrs'],
					]
				);
			}
		}

		return $result;
	}

	/**
	 * Retrieve the submission form.
	 *
	 * @param WP_Block|array $block An instance of WP_Block, or the parsed block.
	 *
	 * @return Registration\Form_Container|\WP_Error The form object, or WP_Error if the block is invalid.
	 * @since 2.8
	 */
	public function get_block_submission_form( $block ) {

		if ( $block instanceof WP_Block ) {
			$block = $block->parsed_block;
		}

		$form_types = $this->get_submission_form_types();

		foreach ( $form_types as $type ) {

			if ( is_array( $block ) && "affiliatewp/{$type}" === $block['blockName'] ) {

				return new Registration\Form_Container(
					[
						'fields'     => $this->get_submission_form_fields( $block ),
						'block_name' => $block['blockName'] ?? '',
					]
				);
			}
		}

		return new \WP_Error(
			'invalid_block',
			'An invalid block was provided. The block must be an affiliatewp/registration or affiliatewp/affiliate-area block.',
			[
				'block' => $block,
			]
		);
	}

	/**
	 * Retrieve all submission forms for the provided post ID.
	 *
	 * Loop through all forms in a given post and get all submission
	 * forms found inside blocks.
	 *
	 * @since 2.8
	 * @since 2.11.0 Started to use find_submission_forms_from_blocks() to recursive search through blocks.
	 *
	 * @param int $post_id The post ID.
	 * @return array List of submission form objects for the provided post.
	 */
	public function get_submission_forms( int $post_id ) : array {

		$forms_found = [];
		$post        = get_post( $post_id );

		$form_types = $this->get_submission_form_types();

		affiliate_wp()->editor->blocks_init();

		$blocks = parse_blocks( $post->post_content );

		// Force blocks to possibly be registered early.
		if ( has_block( 'affiliatewp/affiliate-area', $post ) ) {

			foreach ( $blocks as $block_index => $block ) {
				if ( isset( $block['blockName'] ) && 'affiliatewp/affiliate-area' !== $block['blockName'] ) {
					continue;
				}

				if ( ! isset( $block['innerBlocks'] ) ) {
					continue;
				}

				foreach ( $block['innerBlocks'] as $inner_block ) {

					$forms_found[] = new Registration\Form_Container(
						[
							'fields'      => $this->get_submission_form_fields( $inner_block ),
							'block_attrs' => isset( $inner_block['attrs'] ) ? $inner_block['attrs'] : [],
							'block_name'  => $inner_block['blockName'] ?? '',
						]
					);
				}

				// This will prevent looping through this same blocks again since we forced to loop earlier.
				unset( $blocks[ $block_index ] );
			}
		}

		foreach ( $form_types as $type ) {

			if ( 'affiliate-area' === $type ) {
				continue;
			}

			if ( has_block( "affiliatewp/{$type}", $post ) ) {
				$forms_found = $this->find_submission_forms_from_blocks( $blocks, $type, $forms_found );
			}
		}

		return $forms_found;
	}

	/**
	 * Get found forms in an array of blocks.
	 *
	 * From an initial array of blocks, loop recursively through all blocks and
	 * your children blocks and returns found forms.
	 *
	 * @since 2.10.0
	 *
	 * @param array  $blocks      Blocks list.
	 * @param string $type        Block type.
	 * @param array  $forms_found Forms found.
	 * @return array              List of forms found.
	 */
	public function find_submission_forms_from_blocks( array $blocks, string $type, array $forms_found = [] ) : array {

		if ( empty( $blocks ) ) {
			return $forms_found;
		}

		foreach ( $blocks as $block ) {

			if (
				isset( $block['blockName'] ) &&
				"affiliatewp/{$type}" === $block['blockName']
			) {
				$forms_found[] = new Registration\Form_Container(
					[
						'fields'      => $this->get_submission_form_fields( $block ),
						'block_attrs' => isset( $block['attrs'] ) ? $block['attrs'] : [],
						'block_name'  => $block['blockName'] ?? '',
					]
				);
			}

			if ( ! isset( $block['innerBlocks'] ) || ! is_array( ( $block['innerBlocks'] ) ) ) {
				continue; // If this block doesn't have child blocks we can skip to the next block.
			}

			$forms_found = $this->find_submission_forms_from_blocks( $block['innerBlocks'], $type, $forms_found );

		}

		return $forms_found;
	}

	/**
	 * Saves the submission form hashes to the database.
	 *
	 * @since 2.8
	 * @since 2.11.0 Changed hash method to checksum.
	 * @since 2.27.6 Added condition to prevent saving empty post meta.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_submission_form_hashes( int $post_id ) {

		// Prevent hashes being changed if the user updated something without saving.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$forms = $this->get_submission_forms( $post_id );
		$meta  = [];

		foreach ( $forms as $form ) {

			if ( $form instanceof Registration\Form_Container ) {
				$meta[] = $form->get_checksum();
			}
		}

		if ( empty( $meta ) ) {
			return; // Don't save meta if no forms can be found.
		}

		update_post_meta( $post_id, 'affwp_submission_forms_hashes', $meta );
	}

	/**
	 * Retrieves a single affiliate submission form given a post ID and form hash.
	 *
	 * @since 2.8
	 *
	 * @param int        $post_id The post ID containing the form.
	 * @param string|int $hash    Submission form hash (registration form).
	 *
	 * @return Registration\Form_Container|\WP_Error
	 */
	public function get_submission_form( int $post_id, $hash ) {

		// Retrieve hash data for forms by given post ID.
		$hash_data = $this->get_submission_forms_hash_data( $post_id );

		if ( empty( $hash_data ) ) {
			return new \WP_Error(
				'submission_form_hash_not_found',
				__( 'The hash supplied do not match any forms', 'affiliate-wp' ),
				[
					'post_id' => $post_id,
					'hash'    => $hash,
				]
			);
		}

		// Get all submission forms.
		$forms = $this->get_submission_forms( $post_id );

		foreach ( $forms as $form ) {

			$form_hash = 'checksum' === $hash_data['method']
				? $form->get_checksum()
				: $form->get_hash();

			if ( ! in_array( $form_hash, $hash_data['hashes'], true ) ) {
				continue; // Form hash is not in hashes for this post.
			}

			// If the hash from the registration form is in the hashes for this post and the hash matches the form hash.
			if ( (string) $form_hash !== (string) $hash ) {
				continue; // Not the right form.
			}

			return $form; // It's this form, because the hash matches...
		}

		// Couldn't find the form.
		return new \WP_Error(
			'submission_form_not_found',
			__( 'A form for the provided hash could not be found', 'affiliate-wp' ),
			[
				'post_id' => $post_id,
				'hash'    => $hash,
			]
		);
	}

	/**
	 *
	 * Get hash method and values from a given post.
	 *
	 * We try to find the checksums first, otherwise we look for md5 hashes
	 * from the old hash method, we also return the hashing method to allow
	 * other methods to know how to identify the hash type and hash functions to use.
	 *
	 * @param int $post_id The page post ID.
	 *
	 * @return array
	 */
	public function get_submission_forms_hash_data( int $post_id ) : array {

		$checksum_hashes = get_post_meta( $post_id, 'affwp_submission_forms_hashes', true );

		if ( ! empty( $checksum_hashes ) ) {
			// We have checksum hashes.
			return [
				'method' => 'checksum',
				'hashes' => $checksum_hashes,
			];
		}

		$md5_hashes = get_post_meta( $post_id, 'affwp_affiliate_submission_forms', true );

		if ( empty( $md5_hashes ) ) {
			return []; // No md5 hashes (also no checksum hashes).
		}

		// md5 hashes only.
		return [
			'method' => 'md5',
			'hashes' => $md5_hashes,
		];
	}
}

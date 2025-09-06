<?php
/**
 * Class to register Multi Currency addon settings.
 *
 * @package    AffiliateWP
 * @subpackage Core
 * @copyright  Copyright (c) 2024, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.26.1
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 *
 * phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine
 */

namespace AffiliateWP\Admin\Settings\V2\Sections;

use AffiliateWP\Admin\Settings\V2\Callbacks\License_Activator;
use AffiliateWP\Utils\Icons;
use function AffiliateWP\Multi_Currency\affiliate_wp_multi_currency;
use function AffiliateWP\Multi_Currency\convert_rate_to_string;

affwp_require_util_traits( 'db' );

/**
 * Addons class.
 *
 * @since 2.26.1
 */
final class Multi_Currency extends Base {

	use \AffiliateWP\Utils\DB;

	/**
	 * Store the default AffiliateWP currency select for this site.
	 *
	 * @since 2.26.1
	 * @var string The currency set for AffiliateWP.
	 */
	private string $site_currency;

	/**
	 * The API handler used by this site.
	 *
	 * @since 2.26.1
	 * @var string
	 */
	private string $api_handler;

	/**
	 * Whether the addon is installed.
	 *
	 * @since 2.26.1
	 * @var bool
	 */
	private bool $is_multi_currency_installed;

	/**
	 * Whether it has an API connected or not.
	 *
	 * @since 2.26.1
	 * @var bool
	 */
	private bool $is_activated;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->site_currency               = affwp_get_currency();
		$this->api_handler                 = affiliate_wp()->settings->get( 'multi_currency_rates_update_method', 'manual' );
		$this->is_multi_currency_installed = function_exists( '\AffiliateWP\Multi_Currency\affiliate_wp_multi_currency' );

		$this->is_activated = (
			$this->is_multi_currency_installed &&
			affiliate_wp_multi_currency()->exchange_rate_manager->is_activated( $this->api_handler )
		);
	}

	/**
	 * Retrieve the default select2 settings.
	 *
	 * @since 2.26.1
	 * @return array Select2 array of settings.
	 */
	private function get_currency_select2_settings() : array {
		return [
			'width'                   => '240px',
			'minimumResultsForSearch' => 1,
			'placeholder'             => __( 'Search...', 'affiliate-wp' ),
		];
	}

	/**
	 * The unique handle for this section.
	 *
	 * @since 2.26.1
	 * @return string
	 */
	protected function get_handle() : string {
		return 'multi_currency';
	}

	/**
	 * The section name.
	 *
	 * @since 2.26.1
	 * @return string
	 */
	protected function get_title() : string {
		return __( 'Multi-Currency', 'affiliate-wp' );
	}

	/**
	 * Whether this is for addon or not.
	 *
	 * @since 2.26.1
	 * @return bool
	 */
	protected function is_addon(): bool {
		return true;
	}

	/**
	 * Retrieve the license level for this section.
	 *
	 * @since 2.26.1
	 * @return string
	 */
	protected function get_license_level(): string {
		return 'plus';
	}

	/**
	 * The tab name where this section will be added.
	 *
	 * @since 2.26.1
	 * @return string
	 */
	protected function get_tab_name() : string {
		return 'commissions';
	}

	/**
	 * Return the currency keys.
	 *
	 * @since 2.26.1
	 * @return array The currency array.
	 */
	private function get_currencies() : array {
		if ( ! $this->is_activated ) {
			return affwp_get_currencies();
		}

		return affiliate_wp_multi_currency()->exchange_rate_manager->get_currencies( $this->api_handler );
	}

	/**
	 * Get the code from the first currency available.
	 *
	 * @since 2.26.1
	 * @return string|null
	 */
	private function get_first_available_currency_code() : ?string {
		return array_key_first( $this->get_currencies() );
	}

	/**
	 * Register the settings and section, enqueue necessary styles.
	 *
	 * @since 2.26.1
	 * @return void
	 * @throws \Exception The exception error.
	 * */
	public function init(): void {
		// This will register the settings and sections.
		parent::init();

		// Check if we need to disconnect from any APIs.
		$this->maybe_disconnect_from_apis();

		// This will add the necessary styles to render the repeater.
		$this->enqueue_assets();
	}

	/**
	 * Check if user returned to manual without disconnecting, ensures it disconnects from any API.
	 * Also disconnects if the currency changed to an unsupported one.
	 *
	 * @since 2.26.1
	 * @return void
	 * @throws \Exception The exception error.
	 */
	private function maybe_disconnect_from_apis() : void {
		if ( ! $this->is_multi_currency_installed ) {
			return; // Bail if Multi-Currency is not installed.
		}

		if ( ! filter_input( INPUT_GET, 'settings-updated' ) ) {
			return; // The deactivation should occur only when settings were updated.
		}

		if (
			'manual' !== $this->api_handler &&
			! in_array(
				$this->site_currency,
				array_keys( affiliate_wp_multi_currency()->exchange_rate_manager->get_unsupported_currencies( $this->api_handler ) ),
				true
			)
		) {
			return; // Bail if it is using an API.
		}

		// Ensure it goes back to manual again.
		affiliate_wp()->settings->set(
			[
				'multi_currency_rates_update_method' => 'manual',
			],
			true
		);

		// Disconnect from any APIs.
		affiliate_wp_multi_currency()->exchange_rate_manager->deactivateAll();

		// Remove Action Scheduler events.
		\AffiliateWP\Multi_Currency\Events::get_instance()->deregister_event_update_exchange_rates_daily();
	}

	/**
	 * Register the hook that adds the settings styles.
	 *
	 * @return void
	 */
	private function enqueue_assets() : void {
		add_action( 'admin_enqueue_scripts', [ $this, 'action_enqueue_assets' ] );
	}

	/**
	 * Enqueue the styles.
	 *
	 * @return void
	 */
	public function action_enqueue_assets() : void {
		if ( ! affiliatewp_is_settings_page( $this->get_tab_name() ) ) {
			return; // Bail if isn't the 'commissions' tab.
		}

		/* TODO: Maybe change the file name to admin-settings.css to be more generic */
		wp_enqueue_style(
			'affiliatewp-multi-currency-settings',
			sprintf(
				'%1$sadmin-multi-currency%2$s.css',
				affiliate_wp()->scripts->get_css_path(),
				affiliate_wp()->scripts->get_suffix()
			),
			[],
			affiliate_wp()->scripts->get_version()
		);

		$script_handle = 'affiliatewp-admin-multi-currency-settings';

		affiliate_wp()->scripts->enqueue( $script_handle );

		$data = wp_json_encode(
			[
				'rowTemplate'               => affiliatewp_render_template(
					$this->get_row_html(),
					[
						'currency'         => $this->get_first_available_currency_code(),
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is escaped at this point.
						'currency_options' => $this->render_exchange_rate_options(),
					]
				),
				'firstAvailableCurrencyCode' => $this->get_first_available_currency_code(),
				'currency_select2_settings'  => $this->get_currency_select2_settings(),
			]
		);

		wp_add_inline_script( $script_handle, "affiliatewp.parseArgs( {$data}, affiliatewp.multiCurrencySettings.data );" );

		// Save recent currencies so we can set them up automatically.
		wp_localize_script(
			$script_handle,
			'affiliatewpRecentCurrencies',
			$this->get_woocommerce_currencies_from_recent_orders()
		);
	}

	/**
	 * Gets a list of recent currencies from recent WooCommerce orders.
	 *
	 * @since 2.27.3
	 * @since AFFWPN Notice prevented when WooCommerce isn't active.
	 *
	 * @param string $default_currency When there are none, this currency will be the default.
	 *
	 * @return array
	 */
	private function get_woocommerce_currencies_from_recent_orders( string $default_currency = '' ) : array {

		global $wpdb;
		global $table_prefix;

		if ( ! $this->table_exists( "{$table_prefix}wc_orders" ) ) {
			return [ strtoupper( $default_currency ) ]; // No WooCommerce.
		}

		// Get the WooCommerce currencies from the last # of orders.
		$recent_currencies = $wpdb->get_col(
			$wpdb->prepare(

				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_prefix cannot be quoted.
				"SELECT DISTINCT `currency` FROM {$table_prefix}wc_orders WHERE `type` = %s LIMIT %d",
				'shop_order',

				/**
				 * Increase or decrease the sample (LIMIT) of recent currencies.
				 *
				 * This is a number that goes into LIMIT query in SQL.
				 *
				 * @since 2.27.3
				 *
				 * @param int $limit The number of currencies to obtain a list from.
				 *
				 * @return array
				 */
				apply_filters(
					'affiliatewp_woocommerce_recent_currencies_limit',
					100
				)
			)
		);

		if (
			! is_array( $recent_currencies )
			&& ! empty( $default_currency )

			// Make sure that the currency is one AffiliateWP supports.
			&& in_array(
				strtoupper( $default_currency ),
				array_keys( affwp_get_currencies() ),
				true
			)
		) {
			return [ strtoupper( $default_currency ) ]; // No currencies, user wants default.
		}

		if ( ! is_array( $recent_currencies ) ) {
			return []; // No currencies.
		}

		return array_unique(
			array_map(
				'strtoupper',

				// Validate DB data an currencies AffiliateWP supports.
				array_filter(
					$recent_currencies,
					function( $value ) {

						// Validate data type.
						return is_string( $value )

							// Make sure the currency is one we support in AffiliateWP.
							&& in_array(
								strtoupper( $value ),
								array_keys( affwp_get_currencies() ),
								true
							);
					}
				)
			)
		);
	}

	/**
	 * Retrieve the tooltip text for the Multi-Currency section.
	 *
	 * The tooltip includes a brief explanation of the addon's functionality and a link to learn more.
	 *
	 * @since 2.26.1
	 * @return string The tooltip text.
	 */
	public function get_tooltip() : string {
		return sprintf(
			/* translators: 1: Link to the doc page on tiers. 2: Additional link attributes. 3: Accessibility text. */
			'<p>' . __( 'The Multi-Currency addon ensures accurate affiliate commissions by converting the order\'s originating currency into the affiliate program\'s currency using real-time exchange rates.', 'affiliate-wp' ) . '</p>' .
			'<p>' . __( 'Essential for stores operating in multiple currencies.', 'affiliate-wp' ) . '</p>' .
			__( '<a href="%1$s" %2$s>Learn more%3$s</a>', 'affiliate-wp' ),
			esc_url( 'https://affiliatewp.com/docs/multi-currency' ),
			'target="_blank" rel="noopener"',
			sprintf(
				'<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span>',
				/* translators: Hidden accessibility text. */
				__( '(opens in a new tab)', 'affiliate-wp' )
			)
		);
	}

	/**
	 * The settings for this section.
	 *
	 * @since 2.26.1
	 * @return array[] The array of settings.
	 * @throws \Exception Exception thrown by affiliate_wp_multi_currency();
	 */
	protected function get_settings() : array {
		// This will be used by a closure function later, so we need to bind the value to a local variable.
		$is_activated = $this->is_activated;

		$settings = [
			'multi_currency' => [
				'name'            => $this->get_title(),
				'desc'            => __( 'Enable Multi-Currency.', 'affiliate-wp' ),
				'type'            => 'checkbox',
				'std'             => '0',
				'education_modal' => [
					'enabled'        => ! affiliatewp_can_access_plus_features(),
					'name'           => $this->get_title(),
					'license_level'  => $this->get_license_level(),
					'utm_content'    => $this->get_title(),
					'require_addon'  => affiliate_wp()->settings->addons[ $this->get_handle() ] ?? [],
					'show_pro_badge' => false,
					'feature_name'   => 'multi-currency',
					'is_checked'     => affiliate_wp()->settings->get( 'multi_currency' ) === 1,
				],
			],
		];

		if ( ! $this->is_multi_currency_installed || ! affiliatewp_can_access_plus_features() ) {
			return $settings;
		}

		return array_merge(
			$settings,
			[
				'multi_currency_rates_update_method'     => [
					'name'       => __( 'Exchange Rate Method', 'affiliate-wp' ),
					'type'       => 'select',
					'options'    => [
						'manual' => __( 'Manual', 'affiliate-wp' ),
					],
					'std'        => 'manual',
					'class'      => 'affwp-multi-currency-update-method',
					'visibility' => [
						'required_field' => 'multi_currency',
						'value'          => true,
					],
					'select2'    => [
						'width' => '350px',
					],
					'tooltip'           => sprintf(
						'<p>%1$s</p><p>%2$s</p>%3$s',
						esc_html__( 'Select manual for full control over rates.', 'affiliate-wp' ),
						esc_html__( 'Select an API provider to automatically update your exchange rates daily.', 'affiliate-wp' ),
						sprintf(
						/* translators: 1: Link to the doc page on tiers. 2: Additional link attributes. 3: Accessibility text. */
							__( '<a href="%1$s" %2$s>Learn more%3$s</a>', 'affiliate-wp' ),
							esc_url( 'https://affiliatewp.com/docs/multi-currency/#setting-up-exchange-rate-api' ),
							'target="_blank" rel="noopener"',
							sprintf(
								'<span class="screen-reader-text"> %s</span><span aria-hidden="true" class="dashicons dashicons-external"></span>',
								/* translators: Hidden accessibility text. */
								esc_html__( '(opens in a new tab)', 'affiliate-wp' )
							)
						)
					),
				],
				'multi_currency_api_key'                 => [
					'name'              => __( 'API Key', 'affiliate-wp' ),
					'callback'          => [ License_Activator::get_instance(), 'render' ],
					'std'               => '',
					'disabled'          => $is_activated,
					'activator_options' => [
						'activate_ajax_action'     => 'multi_currency_activate_exchange_api',
						'deactivate_ajax_action'   => 'multi_currency_deactivate_exchange_api',
						'status_callback'          => function() use ( $is_activated ) {
							return $is_activated;
						},
						'status_message'           => affiliate_wp_multi_currency()->exchange_rate_manager->get_help_text( $this->api_handler ),
						'button_label_activated'   => __( 'Disconnect', 'affiliate-wp' ),
						'button_label_deactivated' => __( 'Connect', 'affiliate-wp' ),
						'activation_message'       => __( 'The API was successfully connected.', 'affiliate-wp' ),
						'deactivation_message'     => __( 'The API was disconnected successfully.', 'affiliate-wp' ),
						'settings_to_watch'        => [ 'multi_currency_rates_update_method' ],
					],
					'visibility'        => [
						[
							'required_field' => 'multi_currency_rates_update_method',
							'value'          => 'manual',
							'compare'        => '!=',
						],
						[
							'required_field' => 'multi_currency',
							'value'          => true,
						],
					],
				],
				'multi_currency_currency_exchange_rates' => [
					'name'       => __( 'Exchange Rates', 'affiliate-wp' ),
					'desc'       => '',
					'type'       => '',
					'callback'   => [ $this, 'currency_exchange_rates_callback' ],
					'visibility' => [
						'required_field' => 'multi_currency',
						'value'          => true,
					],
				],
			]
		);
	}

	/**
	 * Generates the HTML to render a row for the repeatable component.
	 *
	 * @since 2.26.1
	 * @return string The row template.
	 */
	private function get_row_html() : string {
		ob_start();

		$is_editable = 'manual' === $this->api_handler || ! $this->is_activated;

		?>

		<div class="affwp-multi-currency-row">
			<div class="affwp-multi-currency-dropdown-container">
				<select
					name="affwp_settings[multi_currency_currency_exchange_rates][{{index}}][currency]"
					class="small-text affwp-multi-currency-field-currency"
					data-select2-settings="<?php echo esc_attr( wp_json_encode( $this->get_currency_select2_settings() ) ); ?>"
				>{{currency_options}}</select>
			</div>

			<div class="affwp-multi-currency-exchange-rate-container">
				<span class="affwp-multi-currency-exchange-rate-explainer">
					<span>1</span>
					<span class="affwp-multi-currency-currency">{{currency}}</span>
					<span>=</span>
					<span
						class="affwp-multi-currency-exchange-rate"
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped by the function.
						echo affiliatewp_tag_attr( 'style', $is_editable ? 'display: none' : '' );
						?>
					>{{exchange_rate_formatted}}</span>
				</span>
				<input
					id="affwp_settings[multi_currency_currency_exchange_rates][{{index}}][exchange_rate]"
					name="affwp_settings[multi_currency_currency_exchange_rates][{{index}}][exchange_rate]"
					type="<?php echo esc_attr( $is_editable ? 'number' : 'hidden' ); ?>"
					required
					step="any"
					min="0"
					value="{{exchange_rate}}"
					class="small-text affwp-multi-currency-field-exchange-rate"
				>
				<span class="affwp-multi-currency-site-currency"><?php echo esc_html( $this->site_currency ); ?></span>
			</div>
			<a
				href="#"
				class="affwp-remove-exchange-rate"
				title="<?php esc_html_e( 'Remove Exchange Rate', 'affiliate-wp' ); ?>"
				style="display:none"
			>
				<?php Icons::render( 'remove' ); ?>
			</a>
		</div>

		<?php

		return ob_get_clean();
	}

	/**
	 * Render the exchange rate dropdown options.
	 *
	 * @since 2.26.1
	 *
	 * @param string|null $current The current option.
	 *
	 * @return string Options HTML.
	 */
	private function render_exchange_rate_options( ?string $current = null ) : string {
		ob_start();

		foreach ( $this->get_currencies() as $option => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $option ),
				$current ? selected( $option, $current, false ) : '',
				esc_html( $label )
			);
		}

		return ob_get_clean();
	}

	/**
	 * Callback for the Currency Exchange Rates field.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function currency_exchange_rates_callback() : void {
		$exchange_rates = affiliate_wp()->settings->get(
			'multi_currency_currency_exchange_rates',
			[
				[
					'currency'                => $this->get_first_available_currency_code(),
					'exchange_rate'           => 1,
					'exchange_rate_formatted' => 1,
				],
			]
		);

		$row_html       = $this->get_row_html();

		?>

		<script>
			document.addEventListener( 'DOMContentLoaded', function() {
				if ( ! affiliatewp.has( 'multiCurrencySettings' ) ) {
					console.error( 'Missing multiCurrencySettings scripts.' );
					return;
				}

				affiliatewp.multiCurrencySettings.initRepeater();
			} );
		</script>

		<div id="affwp-multi-currency">
			<div id="affwp-multi-currency-rows">
				<div class="affwp-multi-currency-row affwp-multi-currency-row--header">
					<div class="affwp-multi-currency-dropdown-container">
						<strong><?php esc_html_e( 'Currency', 'affiliate-wp' ); ?></strong>
					</div>
					<div class="affwp-multi-currency-exchange-rate-container">
						<strong><?php esc_html_e( 'Exchange Rate', 'affiliate-wp' ); ?></strong>
						<?php if ( class_exists( 'AffiliateWP\Multi_Currency\Admin\Admin' ) ) : ?>
							<a
								id="affwp-update-exchange-rates"
								href="#"
								<?php

								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped by the function.
								echo affiliatewp_tag_attr(
									'style',
									(
										$this->api_handler !== 'manual' &&
										get_option( 'affwp_multi_currency_api_is_connected' )
									)
										? ''
										: 'display: none'
								);

								?>
							>
								<?php esc_html_e( '(Update rates)', 'affiliate-wp' ); ?>
								</a>
						<?php endif; ?>
					</div>
				</div>

				<?php foreach ( $exchange_rates as $key => $exchange_rate ) : ?>

					<?php

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is escaped at this point.
					echo affiliatewp_render_template(
						$row_html,
						[
							'index'                   => esc_html( $key ),
							'exchange_rate'           => esc_html( $exchange_rate['exchange_rate'] ),
							'exchange_rate_formatted' => esc_html(
								function_exists( '\AffiliateWP\Multi_Currency\convert_rate_to_string' )
									? convert_rate_to_string( (float) $exchange_rate['exchange_rate'], true )
									: ''
							),
							'currency'                => esc_html( $exchange_rate['currency'] ),
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is escaped at this point.
							'currency_options'        => $this->render_exchange_rate_options( $exchange_rate['currency'] ),
						]
					);

					?>

				<?php endforeach; ?>

			</div>
		</div>

		<div id="affwp-multi-currency-actions">
			<button
				id="affwp-new-exchange-rate"
				name="affwp-new-exchange-rate"
				class="button"
			>
				<?php esc_html_e( 'Add Exchange Rate', 'affiliate-wp' ); ?>
			</button>
		</div>

		<div id="affwp-multi-currency-api-status">
			<?php

			echo $this->is_activated
				? esc_html( affiliate_wp_multi_currency()->exchange_rate_manager->get_status_message( $this->api_handler ) )
				: '';

			?>
		</div>

		<?php
	}
}

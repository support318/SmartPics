<?php
/**
 * Custom Links.
 *
 * This class handles Custom Links actions.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateArea
 * @copyright   Copyright (c) 2023 Awesome Motive, inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14.0
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

#[\AllowDynamicProperties]

class Affiliate_WP_Custom_Links {

	/**
	 * Custom links found.
	 *
	 * @var array
	 */
	private array $custom_links = array();

	/**
	 * Construct.
	 *
	 * @since 2.14.0
	 */
	public function __construct() {

		// Add our hooks.
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 2.14.0
	 */
	public function hooks() : void {

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'affwp_render_custom_link_generator', array( $this, 'render' ) );
		add_action( 'wp_ajax_affwp_save_custom_link', array( $this, 'save' ) );
	}

	/**
	 * Handle the add and update AJAX requests.
	 *
	 * @return void
	 */
	public function save() : void {

		// Security check.
		if ( ! check_ajax_referer( 'affiliate-wp-custom-link', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid request - reload the page and try again', 'affiliate-wp' ),
				)
			);

			exit;
		}

		// Mount args array. Data will be sanitized later.
		$args = array(
			'link'           => filter_input( INPUT_POST, 'url' ),
			'campaign'       => filter_input( INPUT_POST, 'campaign' ),
			'affiliate_id'   => affwp_get_affiliate_id( get_current_user_id() ),
			'custom_link_id' => filter_input( INPUT_POST, 'custom_link_id' ),
		);

		// Check for valid urls.
		if ( filter_var( $args['link'], FILTER_VALIDATE_URL ) === false ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Please enter a valid URL for this site', 'affiliate-wp' ),
				)
			);

			exit;
		}

		// Save custom link. If a custom_link_id is supplied, will try to update, otherwise, a new one will be created.
		$custom_link = empty( $args['custom_link_id'] )
			? affwp_add_custom_link( $args )
			: affwp_update_custom_link( $args );

		// Custom link failed to save.
		if ( false === $custom_link ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Failed to save link - please try again', 'affiliate-wp' ),
				)
			);

			exit;
		}

		// The add method returns an int on success and the update method a true, so we can safely get the custom link object now.
		$custom_link = is_int( $custom_link )
				? affwp_get_custom_link( $custom_link )
				: affwp_get_custom_link( $args['custom_link_id'] );

		// Tells if it is a new item or an update.
		$updated = ! empty( $args['custom_link_id'] );

		// Send AJAX success message.
		wp_send_json_success(
			array(
				'message' => $updated
					? esc_html__( 'Link updated', 'affiliate-wp' )
					: esc_html__( 'Link created', 'affiliate-wp' ),
				'updated' => $updated,
				'fields'  => array(
					'ID'               => absint( $custom_link->ID ),
					'link'             => esc_url( $custom_link->get_custom_link() ),
					'destination_link' => esc_url( $custom_link->link ),
					'date_created'     => esc_html( $custom_link->get_formatted_date_created() ),
				),
			)
		);

		exit;
	}

	/**
	 * Register necessary scripts.
	 *
	 * @return void
	 */
	public function register_scripts() : void {

		wp_register_script(
			'affwp-custom-link',
			sprintf(
				'%1$sassets/js/custom-link%2$s.js',
				AFFILIATEWP_PLUGIN_URL,
				( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min'
			),
			array(
				'jquery',
				'affiliatewp-tooltip',
			),
			AFFILIATEWP_VERSION,
			true
		);

		$json = wp_json_encode(
			array(
				'template' => $this->row_template(),
				'i18n'     => array(
					'invalid_url'                     => __( 'Please enter a valid URL for this site', 'affiliate-wp' ),
					'save_failed'                     => __( 'Failed to save link - please try again', 'affiliate-wp' ),
					'invalid_request'                 => __( 'Invalid request - reload the page and try again', 'affiliate-wp' ),
					'custom_link_btn_create'          => __( 'Create Custom Link', 'affiliate-wp' ),
					'custom_link_btn_update'          => __( 'Update Custom Link', 'affiliate-wp' ),
					'copied'                          => __( 'Link copied!', 'affiliate-wp' ),
					'saving'                          => __( 'Saving...', 'affiliate-wp' ),
					'copy_affiliate_link'             => __( 'Copy link', 'affiliate-wp' ),
					'edit_affiliate_link'             => __( 'Edit link', 'affiliate-wp' ),
					'successfully_created'            => __( 'Link created', 'affiliate-wp' ),
					'successfully_created_and_copied' => __( 'Link created and copied to clipboard', 'affiliate-wp' ),
					'successfully_updated'            => __( 'Link updated', 'affiliate-wp' ),
					'successfully_updated_and_copied' => __( 'Link updated and copied to clipboard', 'affiliate-wp' ),
				),
				'nonce'    => wp_create_nonce( 'affiliate-wp-custom-link' ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		wp_add_inline_script(
			'affwp-custom-link',
			"const affWpCustomLinksVars = {$json}",
			'before'
		);
	}

	/**
	 * Fetch all the custom links for a given affiliate.
	 *
	 * @since 2.14.0
	 *
	 * @param int $affiliate_id The affiliate ID to retrieve data.
	 * @return void
	 */
	public function fetch_affiliate_custom_links( int $affiliate_id ) : void {

		$this->custom_links = empty( $affiliate_id )
			? array()
			: affiliate_wp()->custom_links->get_custom_links(
				array(
					'number'       => -1,
					'order'        => 'DESC',
					'affiliate_id' => absint( $affiliate_id ),
				)
			);
	}

	/**
	 * Return table classes to use in the HTML table.
	 *
	 * @since 2.14.0
	 *
	 * @return array
	 */
	private function get_table_classes() : array {
		return array_filter(
			array(
				'affwp-table',
				'affwp-custom-links-table',
				empty( $this->custom_links ) ? 'affwp-hidden' : '',
			)
		);
	}

	/**
	 * Display the custom link table.
	 *
	 * @since 2.14.0
	 *
	 * @param int $affiliate_id Affiliate ID to fetch data.
	 * @return void
	 */
	public function render( int $affiliate_id ): void {
		ob_start();

		affwp_enqueue_script( 'affwp-custom-link', 'force_custom_link_scripts' );
		?>

		<form id="affwp-custom-link-generator" class="affwp-form affwp-custom-link-generator" method="get" action="#affwp-generate-ref-url">

			<div class="affwp-card">
				<div class="affwp-card__header">
					<h3>
						<?php esc_html_e( 'Custom Link Generator', 'affiliate-wp' ); ?>
					</h3>
					<p>
						<?php esc_html_e( 'Enter any URL from this website in the form below to generate a custom link.', 'affiliate-wp' ); ?>
					</p>
				</div>
				<div class="affwp-card__content affwp-card__content--form">

					<div>
						<label for="affwp-url"><?php esc_html_e( 'Page URL', 'affiliate-wp' ); ?></label>
						<input type="url" name="url" id="affwp-url" value="" required>
					</div>

					<div class="affwp-generator-campaign-text-link-wrap">
						<a id="affwp-generator-toggle-campaign" href="#toggle-campaign"><?php esc_html_e( 'Add a campaign (optional)', 'affiliate-wp' ); ?></a>
					</div>

					<div class="affwp-campaign-wrap affwp-hidden">
						<label for="affwp-campaign"><?php esc_html_e( 'Campaign Name (optional)', 'affiliate-wp' ); ?></label>
						<input type="text" name="campaign" id="affwp-campaign" value="" maxlength="50">
					</div>

				</div>
				<div class="affwp-card__footer affwp-custom-link-submit-wrap">
					<span id="affwp-generator-submit-notices">
						<input id="affwp-generator-submit-btn" type="submit" class="button" value="<?php esc_html_e( 'Create Custom Link', 'affiliate-wp' ); ?>" />
					</span>
				</div>
			</div>
			<input type="hidden" class="affwp-affiliate-id" value="<?php echo esc_attr( urldecode( affwp_get_referral_format_value() ) ); ?>">
			<input type="hidden" class="affwp-custom-link-id" value="0">
		</form>

		<?php

		// Fetch custom links.
		$this->fetch_affiliate_custom_links( $affiliate_id );

		// The Custom Link Generator row template.
		$row_template = $this->row_template();

		?>

		<table id="affwp-custom-links-table" class="<?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead>
				<th data-custom-link><?php esc_html_e( 'Custom Link', 'affiliate-wp' ); ?></th>
				<th data-date-created><?php esc_html_e( 'Date Created', 'affiliate-wp' ); ?></th>
			</thead>
			<tbody>
			<?php
			foreach ( $this->custom_links as $custom_link ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is safe.
				echo $this->render_template(
					$row_template,
					array(
						'ID'               => esc_html( $custom_link->ID ),
						'link'             => esc_url( $custom_link->get_custom_link() ),
						'destination_link' => esc_url( $custom_link->link ),
						'date_created'     => esc_html( $custom_link->get_formatted_date_created() ),
					)
				);
			}
			?>
			</tbody>
		</table>

		<?php

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content already escaped.
		echo ob_get_clean();
	}

	/**
	 * Return the custom link table row template.
	 *
	 * Used to inject the table row markup into the DOM using PHP or JS.
	 *
	 * @since 2.14.0
	 *
	 * @return string
	 */
	private function row_template() : string {

		ob_start();

		?>

		<tr id="custom-link-row-{{ID}}" data-custom-link="{{link}}" data-destination-link="{{destination_link}}" data-custom-link-id="{{ID}}">
			<td data-field="link">
				<div class="affwp-custom-link-row">
					<span class="affwp-custom-link affwp-tooltip-url-copy">
						{{link}}
					</span>
					<div class="affwp-custom-link-actions">
						<button class="affwp-tooltip affwp-tooltip-button-copy" data-tippy-content="<?php esc_attr_e( 'Copy link', 'affiliate-wp' ); ?>">
							<span class="affwp-copy-custom-link affwp-custom-link-action affwp-custom-link">
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="-0.25 -0.25 24.5 24.5" stroke-width="2" height="20" width="20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M16.75 4.5V1.75C16.75 1.19772 16.3023 0.75 15.75 0.75H1.75C1.19772 0.75 0.75 1.19771 0.75 1.75V15.75C0.75 16.3023 1.19772 16.75 1.75 16.75H4.5"></path><path stroke="currentColor" stroke-linejoin="round" d="M7.25 8.25C7.25 7.69771 7.69772 7.25 8.25 7.25H22.25C22.8023 7.25 23.25 7.69772 23.25 8.25V22.25C23.25 22.8023 22.8023 23.25 22.25 23.25H8.25C7.69771 23.25 7.25 22.8023 7.25 22.25V8.25Z"></path></svg>
							</span>
						</button>

						<button class="affwp-tooltip affwp-tooltip-edit" data-tippy-content="<?php esc_attr_e( 'Edit link', 'affiliate-wp' ); ?>">
							<span class="affwp-edit-custom-link affwp-custom-link-action">
								<svg viewBox="-0.25 -0.25 24.5 24.5" xmlns="http://www.w3.org/2000/svg" stroke-width="2" height="20" width="20"><path d="M13.045,14.136l-3.712.531.53-3.713,9.546-9.546A2.25,2.25,0,0,1,22.591,4.59Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18.348 2.469L21.53 5.651" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path><path d="M18.75,14.25v7.5a1.5,1.5,0,0,1-1.5,1.5h-15a1.5,1.5,0,0,1-1.5-1.5v-15a1.5,1.5,0,0,1,1.5-1.5h7.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg>
							</span>
						</button>
					</div>
				</div>
			</td>
			<td data-field="date_created">
				{{date_created}}
			</td>
		</tr>

		<?php

		return ob_get_clean();
	}

	/**
	 * Returns HTML template string replacing {{var}} with the data provided.
	 *
	 * @param string $template The HTML template.
	 * @param array  $data Key pair values to replace within the template.
	 * @return string
	 */
	public function render_template( string $template, array $data = array() ) : string {
		if ( empty( $data ) ) {
			return $template;
		}

		foreach ( $data as $key => $value ) {
			$template = str_replace( "{{{$key}}}", $value, $template );
		}

		return $template;
	}
}

<?php
/**
 * Admin: Addons Page Functions
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.7.15
 */

use AffWP\Core\License\License_Data;
use AffWP\Components\Addons\Installer;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Renders the Addons page content.
 *
 * @since 1.7.15
 * @since 2.9.6 New page layout based on license level.
 *
 * @return void
 */
function affwp_add_ons_admin() {

	$license_data = new License_Data();

	// Get license ID.
	$license_id = $license_data->get_license_id();

	// Get license type.
	$license_type = strtolower( $license_data->get_license_type( $license_id ) );

	ob_start();

	try {
		affwp_add_ons_get_feed();
	} catch ( \Exception $exception ) {
		?>
			<?php echo $exception->getMessage(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- No need for escaping. ?>
		<?php
	};

	?>

	<div class="wrap" id="affwp-addons">
		<h1>
			<?php esc_html_e( 'AffiliateWP Addons', 'affiliate-wp' ); ?>
		</h1>
		<?php

		if ( ! empty( $license_type ) ) :
			/* translators: 1: Text about refreshing the addon data, 2: Refresh addons page URL, 3: Refresh link to be displayed */
			echo wp_kses(
				sprintf(
					'<p>%1$s<a href="%2$s">%3$s</a></p>',
					__( 'Missing an addon that you think you should be able to see? Click here to ', 'affiliate-wp' ),
					esc_url_raw( add_query_arg( [ 'affwp_refresh_addons' => '1' ] ) ),
					__( 'Refresh', 'affiliate-wp' )
				),
				[
					'p' => [],
					'a' => [
						'href' => [],
					],
				]
			);
		endif;

		affwp_addons_layout(
			[
				'personal',
				'plus',
				'professional',
				'official-free',
				'pro',
			]
		);

		?>

	</div>

	<?php

	echo ob_get_clean();
}

/**
 * Gets the Addons page layout based on AffiliateWP version.
 *
 * @since 2.12.2
 * @since 2.25.1 Change method to render only Activated Addons and All Addons sections.
 *
 * @param array $categories The addon categories.
 *
 * @return void
 */
function affwp_addons_layout( array $categories = [] ) {

	// Display activated addons.
	$activated_addons = affwp_display_addons( $categories, 'active', false );

	if ( ! empty( $activated_addons ) ) {
		echo sprintf(
			'<h4 class="affwp-addon-section-title">%1$s</h4>%2$s',
			esc_html( __( 'Activated Addons', 'affiliate-wp' ) ),
			$activated_addons
		);
	}

	// Display all addons, excluding the active ones.
	echo sprintf(
		'<h4 class="affwp-addon-section-title">%1$s</h4>%2$s',
		esc_html( __( 'All Addons', 'affiliate-wp' ) ),
		affwp_display_addons( $categories, 'all', false )
	);
}

/**
 * Gets the Addons page feed.
 *
 * @since 1.7.15
 * @since 2.9.6 Feed pulls in JSON data now.
 *
 * @since 2.25.1 Updated so it won't show an error when addons.json is unreachable.
 * @see   https://github.com/awesomemotive/affiliate-wp/issues/5210
 *
 * @param bool $update Update the cache.
 *
 * @return void
 *
 * @throws \Exception If there is an error.
 */
function affwp_add_ons_get_feed( $update = false ) {

	// Update the cache.
	if (
		! is_array( get_transient( 'affiliatewp_add_ons_feed' ) ) // If cached data is corrupt or never set.
		|| true === $update // Function caller wants it updated.

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- We aren't storing this data anywhere.
		|| isset( $_GET['affwp_refresh_addons'] ) // Force update via ?affwp_refresh_addons.
	) {

		$addons = json_decode(
			wp_remote_retrieve_body(
				wp_remote_get(
					esc_url_raw( 'https://affiliatewp.com/wp-content/addons.json' ),
					array(
						'sslverify' => false,
					)
				)
			),
			true
		)
			?? []; // json_decode didn't work.

		if ( empty( $addons ) ) {

			delete_transient( 'affiliatewp_add_ons_feed' ); // Reset the cache for next time.

			/* translators: %s - Retrieving addons error */
			throw new \Exception(
				sprintf(
					'<div class="error"><p>%s</p></div>',
					__( 'There was an error retrieving the addons list from the server. Please try again later.', 'affiliate-wp' )
				)
			);
		}

		// Retrieve the feed, decode, and prepare an array of addons (will be pulled from this cache later on).
		set_transient( 'affiliatewp_add_ons_feed', array_map( 'affwp_prepare_addon_data', $addons ), HOUR_IN_SECONDS );
	}
}

/**
 * Get the addon badge HTML.
 *
 * @since 2.25.1
 *
 * @param string $license_type The addon required license.
 *
 * @return string The badge markup.
 */
function affiliatewp_get_addon_badge( string $license_type ) : string {

	if ( ! affwp_is_upgrade_required( $license_type ) ) {
		return '';
	}

	return sprintf(
		'<span class="affwp-addon-badge">%s</span>',
		'professional' === $license_type
			? 'pro'
			: $license_type
	);
}

/**
 * Display addons.
 *
 * @since 2.9.6
 * @since 2.25.1 Added filter_by and echo parameters.
 *
 * @param array  $category Category of addon to display.
 * @param string $filter_by Filter by 'active' or all addons (will still ignore active ones). Default 'all'.
 * @param bool   $echo Whether it should echo the content or not.
 *
 * @return string
 */
function affwp_display_addons( array $category, string $filter_by = 'all', bool $echo = true ) : string {

	// Get addons.
	$cache = get_transient( 'affiliatewp_add_ons_feed' );

	if ( false === $cache ) {
		return '';
	}

	$status = affiliate_wp()->settings->get( 'license_status', '' );

	$status = ( is_object( $status ) && isset( $status->license ) )
		? $status->license
		: $status;

	ob_start();

	// Loop through and display each addon.
	foreach( $cache as $addon ):

		// External Referral Links should not be installed on the same site as AffiliateWP.
		if ( 'External Referral Links' === $addon['title'] ) {
			continue;
		}

		// Skip if addon doesn't belong to the given category.
		if ( ! isset( $addon['category'] ) || ! is_array( $category ) || ! in_array( $addon['category'], $category, true ) ) {
			continue;
		}

		// Update with current status and action.
		$addon = affwp_update_addon_data( $addon );

		// Use bool to determine if checked and input text below.
		$is_active = isset( $addon['status'] ) && $addon['status'] === 'active' ? true : false;

		// Skip addons based on the filter.
		if ( ( 'active' === $filter_by && ! $is_active ) || ( 'all' === $filter_by && $is_active ) ) {
			continue;
		}

		$classes = sprintf(
			'affwp-addon%s',
			empty( $addon['recommended'] ) ? '' : ' affwp-addon-recommended'
		);
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div class="affwp-addon-details">
				<span class="affwp-addon-img">
					<img src="<?php echo esc_url( $addon['image'] ); ?>" class="attachment-affwp-post-thumbnail size-affwp-post-thumbnail wp-post-image" alt="<?php echo esc_attr( $addon['title'] ); ?>" loading="lazy" title="<?php echo esc_attr( $addon['title'] ); ?>"/>
				</span>
				<div class="affwp-addon-text">
					<h3 class="affwp-addon-name">
						<a href="<?php echo esc_url( $addon['addon_url'] ); ?>" target="_blank" title="Learn more" rel="noopener noreferrer"><?php echo esc_html( $addon['title'] ); ?></a>
					</h3>
					<p><?php echo esc_html( $addon['excerpt'] ); ?></p>
				</div>
			</div>
			<div class="affwp-addon-action">

				<?php if ( isset( $addon['action'] ) && $addon['action'] === 'upgrade' ):  ?>

					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Content is safe.
					echo affiliatewp_get_addon_badge( $addon['category'] );
					?>

					<a href="<?php echo esc_url( $addon['upgrade_url'] );?>" class="button-secondary" target="_blank" rel="noopener noreferrer">Upgrade Now</a>

				<?php else : ?>

					<button for="<?php echo esc_attr( $addon['slug'] ); ?>" class="affwp-styled-checkbox">
						<input class="<?php if ( $is_active ) { echo esc_attr( 'checked' ); } ?>" type="checkbox"
							name="<?php echo esc_attr( $addon['slug'] ); ?>"
							data-action="<?php echo esc_attr( $addon['action'] ); ?>"
							data-plugin="<?php echo esc_attr( $addon['path'] ); ?>"
							data-plugin-id="<?php echo esc_attr( $addon['id'] ); ?>"
							<?php if ( $is_active ) { echo esc_html( 'checked' ); } ?>/>
						<span><?php echo esc_html( $addon['input_label'] ); ?></span>
					</button>

				<?php endif; ?>
			</div>
		</div>
		<?php
	endforeach;

	$html = ob_get_clean();

	if ( empty( $html ) ) {
		return '';
	}

	$html = sprintf( '<div class="affwp-addons-list">%s</div>', $html );

	if ( false === $echo ) {
		return $html;
	}

	echo $html;
}

/**
 * Return status of a addon.
 *
 * @since 2.9.6
 *
 * @param string $path Addon file path.
 *
 * @return string One of the following: active | installed | missing.
 */
function affwp_get_addon_status( $path ) {
	// Check if plugin is active.
	if ( is_plugin_active( $path ) ) {
		return 'active';
	}

	$plugins = get_plugins();

	// Check if plugin is installed.
	if ( ! empty( $plugins[ $path ] ) ) {
		return 'installed';
	}

	return 'missing';
}

/**
 * Determine if user's license level has access.
 *
 * @since 2.9.6
 * @since 2.12.2 Added support for new categories.
 *
 * @param string $category Addon category.
 *
 * @return bool
 */
function affwp_has_access( $category ) {
	// Get license ID.
	$license_data = affiliate_wp()->settings->get( 'license_status', array() );
	$license_id   = isset( $license_data->price_id ) ? absint( $license_data->price_id ) : false;

	// Bail if no license id.
	if ( $license_id === false ) {
		return false;
	}

	// Get license type.
	$license_type = strtolower( ( new License_Data() )->get_license_type( $license_id ) );

	// Professional and Ultimate license types have access to all addons.
	if ( in_array( $license_type, array( 'professional', 'ultimate' ), true ) ) {
		return true;
	}

	// In 2.12.2 we switched to use 'personal', 'plus', and 'professional' categories.
	if ( defined( 'AFFILIATEWP_VERSION' ) && version_compare( AFFILIATEWP_VERSION, '2.12.1', '>' ) ) {

		// Personal license types have access to 'personal' addons.
		if ( in_array( $license_type, array( 'personal' ), true ) && 'personal' === $category ) {
			return true;
		}

		// Plus license types have access to both 'personal' and 'plus' addons.
		if ( in_array( $license_type, array( 'plus' ), true ) && ( 'personal' === $category || 'plus' === $category ) ) {
			return true;
		}
		return false;
	}

	// For previous Core versions, Personal and Plus license types have access to 'official-free' addons.
	if ( in_array( $license_type, array( 'personal', 'plus' ), true ) && 'official-free' === $category ) {
		return true;
	}

	return false;
}

/**
 * Set addon path.
 *
 * Path is required to determine if the plugin is installed and/or activated.
 * Ideally addons will follow a default structure but this handles edge cases.
 *
 * @param string $slug Addon slug.
 *
 * @return string Return addon path.
 */
function affwp_set_addon_path( $slug ) {
	// Plugin paths that use 'affwp' instead of 'affiliatewp'.
	if ( in_array( $slug, array(
		'affiliate-dashboard-sharing',
	), true ) ) {
		return sprintf( 'affiliatewp-%1$s/affwp-%1$s.php', $slug );
	}

	// Plugins paths that use the 'affiliate-wp'.
	if ( in_array( $slug, array(
		'lifetime-commissions',
		'paypal-payouts',
		'recurring-referrals',
	), true ) ) {
		return sprintf( 'affiliate-wp-%1$s/affiliate-wp-%1$s.php', str_replace( '-for-affiliatewp', '', $slug ) );
	}

	// Plugins paths without the 'for-'.
	if ( in_array( $slug, array(
		'affiliate-forms-for-ninja-forms',
		'affiliate-forms-for-gravity-forms',
	), true ) ) {
		return sprintf( 'affiliatewp-%1$s/affiliatewp-%1$s.php', str_replace( 'for-', '', $slug ) );
	}

	// Plugins paths without the '-for-affiliatewp'.
	if ( in_array( $slug, array(
		'zapier-for-affiliatewp',
	), true ) ) {
		return sprintf( 'affiliatewp-%1$s/affiliatewp-%1$s.php', str_replace( '-for-affiliatewp', '', $slug ) );
	}

	// Plugins paths without the '-notifications'.
	if ( in_array( $slug, array(
		'pushover-notifications',
	), true ) ) {
		return sprintf( 'affiliate-wp-%1$s/affiliate-wp-%1$s.php', str_replace( '-notifications', '', $slug ) );
	}

	return sprintf( 'affiliatewp-%1$s/affiliatewp-%1$s.php', $slug );
}

/**
 * Set addon category.
 *
 * @since 2.12.2
 *
 * @param array $categories Addon categories.
 *
 * @return string Return addon's primary category.
 */
function affwp_set_addon_category( $categories ) {

	// In 2.12.2 we switched to use 'personal', 'plus', and 'professional' categories.
	if ( defined( 'AFFILIATEWP_VERSION' ) && version_compare( AFFILIATEWP_VERSION, '2.12.1', '>' ) ) {
		if ( in_array( 'personal', $categories, true ) ) {
			return 'personal';
		}
		if ( in_array( 'plus', $categories, true ) ) {
			return 'plus';
		}
		return 'professional';
	}

	// For previous versions, use 'official-free' or 'pro'.
	return in_array( 'pro', $categories, true ) ? 'pro' : 'official-free';
}

/**
 * Prepare addon data.
 *
 * @since 2.9.6
 *
 * @param array $addon Addon data.
 *
 * @return array Extended addon data.
 */
function affwp_prepare_addon_data( $addon ) {

	// Bail if no addon.
	if ( empty( $addon ) ) {
		return array();
	}

	$slug     = isset( $addon['slug'] ) ? $addon['slug'] : '';
	$category = isset( $addon['category'] ) && is_array( $addon['category'] ) ?
		affwp_set_addon_category( $addon['category'] ) :
		affwp_set_addon_category( array() );

	// Set up UTM params.
	$utm_content = isset( $addon['title'] ) ? urlencode( $addon['title'] ) : '';
	$utm         = esc_url( "?utm_source=WordPress&amp;utm_campaign=plugin&amp;utm_medium=addons&amp;utm_content={$utm_content}" );

	return array(
		'id'           => isset( $addon['id'] )      ? absint( $addon['id'] ) : 0,
		'title'        => isset( $addon['title'] )   ? $addon['title']        : '',
		'excerpt'      => isset( $addon['excerpt'] ) ? $addon['excerpt']      : '',
		'image'        => isset( $addon['image'] )   ? $addon['image']        : '',
		'addon_url'    => isset( $addon['url'] ) ? sprintf( '%1$s%2$s', $addon['url'], $utm ) : '',
		'page_url'     => isset( $addon['url'] ) ? sprintf( '%1$s%2$s', $addon['url'], $utm ) : '',
		'doc_url'      => isset( $addon['doc'] ) ? sprintf( '%1$s%2$s', $addon['doc'], $utm ) : '',
		'path'         => affwp_set_addon_path( $slug ),
		'plugin_allow' => affwp_has_access( $category ),
		'slug'         => $slug,
		'category'     => $category,
		'upgrade_url'  => sprintf( '%1$s%2$s', esc_url( "https://affiliatewp.com/account/downloads/" ), $utm ),
		// Defaults.
		'status'       => 'missing',
		'status_label' => __( 'Not Installed', 'affiliate-wp' ),
	);
}

/**
 * Update addon data.
 *
 * Separated from the prepare addon data step for current (not cached) info.
 *
 * @since 2.9.6
 *
 * @param array $addon Addon data.
 *
 * @return array Updated addon data.
 */
function affwp_update_addon_data( $addon ) {

	// Bail if no addon.
	if ( empty( $addon ) ) {
		return array();
	}

	// Set the status.
	$addon['status'] = affwp_get_addon_status( $addon['path'] );

	// Set the status label and action.
	switch ( $addon['status'] ) {
		case 'active':
			$addon['status_label'] = __( 'Active', 'affiliate-wp' );
			$addon['input_label']  = __( 'Deactivate', 'affiliate-wp' );
			$addon['action']       = $addon['plugin_allow'] ? 'deactivate' : 'upgrade';
			$addon['addon_url']    = ! empty( $addon['doc_url'] ) ? $addon['doc_url'] : $addon['page_url']; // Link to docs if active.
			break;

		case 'installed':
			$addon['status_label'] = __( 'Inactive', 'affiliate-wp' );
			$addon['input_label']  = __( 'Activate', 'affiliate-wp' );
			$addon['action']       = $addon['plugin_allow'] ? 'activate' : 'upgrade';
			break;

		case 'missing':
			$addon['status_label'] = __( 'Not Installed', 'affiliate-wp' );
			$addon['input_label']  = __( 'Install Addon', 'affiliate-wp' );
			$addon['action']       = $addon['plugin_allow'] ? 'install' : 'upgrade';
			break;

		default:
			$addon['status_label'] = __( 'Not Installed', 'affiliate-wp' );
			$addon['input_label']  = __( 'Install Addon', 'affiliate-wp' );
			$addon['action']       = 'upgrade';
	}

	return $addon;
}

/**
 * Install an addons page plugin.
 *
 * @since 2.9.6
 */
function affwp_install_addons_page_plugin() {

	if ( ! current_user_can( 'install_plugins' ) ) {

		wp_send_json_error(
			array(
				'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
			)
		);

		exit;
	}

	// Security check.
	if ( ! check_ajax_referer( 'affiliate-wp-addons-nonce', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
			)
		);
	}

	if ( empty( $_POST['addonID'] ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'Missing addon ID.', 'affiliate-wp' ),
			)
		);
	}

	// Get data.
	$addon_id = isset( $_POST['addonID'] ) ? absint( $_POST['addonID'] ) : false;

	$installer = new Installer();
	$status = $installer->install_addon( $addon_id );

	if ( false === $status ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'Could not be installed. Try again.', 'affiliate-wp' ),
			)
		);
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_affwp_install_addons_page_plugin', 'affwp_install_addons_page_plugin' );

/**
 * Activate an addons page plugin.
 *
 * @since 2.9.6
 */
function affwp_activate_addons_page_plugin() {

	if ( ! current_user_can( 'activate_plugins' ) ) {

		wp_send_json_error(
			array(
				'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
			)
		);

		exit;
	}

	// Security check.
	if ( ! check_ajax_referer( 'affiliate-wp-addons-nonce', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
			)
		);
	}

	// Bail if plugin is missing.
	if ( ! isset( $_POST['plugin'] ) || empty( $_POST['plugin'] ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'Missing addon path.', 'affiliate-wp' ),
			)
		);
	}

	// Sanitize addon path and activate plugin.
	if ( null !== activate_plugin( sanitize_text_field( $_POST['plugin'] ) ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'Could not be activated. Please deactivate from the Plugins page.', 'affiliate-wp' ),
			)
		);
	}

	wp_send_json_success( esc_html__( 'Plugin activated.', 'affiliate-wp' ) );

}
add_action( 'wp_ajax_affwp_activate_addons_page_plugin', 'affwp_activate_addons_page_plugin' );

/**
 * Dectivate an addons page plugin.
 *
 * @since 2.9.6
 */
function affwp_deactivate_addons_page_plugin() {

	// Check for permissions.
	if ( ! current_user_can( 'deactivate_plugins' ) ) {
		wp_send_json_error( esc_html__( 'Plugin deactivation is disabled for you on this site.', 'affiliate-wp' ) );
		exit;
	}

	// Security check.
	if ( ! check_ajax_referer( 'affiliate-wp-addons-nonce', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'You do not have permission.', 'affiliate-wp' ),
			)
		);
	}

	// Bail if plugin is missing.
	if ( empty( $_POST['plugin'] ) ) {
		wp_send_json_error(
			array(
				'error' => esc_html__( 'Could not deactivate the addon. Please deactivate from the Plugins page.', 'affiliate-wp' ),
			)
		);
	}

	// Sanitize addon path and deactivate.
	$deactivate = deactivate_plugins( sanitize_text_field( $_POST['plugin'] ), false, false );

	wp_send_json_success( esc_html__( 'Plugin deactivated.', 'affiliate-wp' ) );
}

add_action( 'wp_ajax_affwp_deactivate_addons_page_plugin','affwp_deactivate_addons_page_plugin' );

/**
 * Enqueue JS and CSS files.
 *
 * @since 2.9.6
 */
function affwp_enqueue_assets() {

	$plugin_url = untrailingslashit( AFFILIATEWP_PLUGIN_URL );
	$min        = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

	// Addons page style and script.
	wp_enqueue_style(
		'affwp_admin_addons',
		"{$plugin_url}/assets/css/admin-addons{$min}.css",
		null,
		AFFILIATEWP_VERSION
	);

	wp_enqueue_script(
		'affwp_admin_addons',
		"{$plugin_url}/assets/js/admin-addons{$min}.js",
		array( 'jquery' ),
		AFFILIATEWP_VERSION,
		true
	);

	wp_localize_script(
		'affwp_admin_addons',
		'affwp_admin_addons_vars',
		array(
			'nonce'                      => wp_create_nonce( 'affiliate-wp-addons-nonce' ),
			'ajax_url'                   => admin_url( 'admin-ajax.php' ),
			'installing'                 => esc_html__( 'Installing...',   'affiliate-wp' ),
			'activating'                 => esc_html__( 'Activating...',   'affiliate-wp' ),
			'deactivating'               => esc_html__( 'Deactivating...', 'affiliate-wp' ),
			'activate'                   => esc_html__( 'Activate',        'affiliate-wp' ),
			'deactivate'                 => esc_html__( 'Deactivate',      'affiliate-wp' ),
			'status_active'              => wp_kses(
				__( 'Status: <span class="affwp-status-active">Active</span>', 'affiliate-wp' ),
				[
					'span' => [
						'class' => [],
					],
				]
			),
			'status_inactive'            => wp_kses(
				__( 'Status: <span class="affwp-status-installed">Inactive</span>', 'affiliate-wp' ),
				[
					'span' => [
						'class' => [],
					],
				]
			),
			'error_could_not_install'    => wp_kses(
				sprintf( /* translators: %s - AffiliateWP downloads URL. */
					__( 'Could not install the plugin automatically. Please <a href="%s" target="_blank" rel="noopener noreferrer">download</a> it and install it manually.', 'affiliate-wp' ),
					esc_url( 'https://affiliatewp.com/account/downloads/?utm_source=WordPress&amp;utm_campaign=plugin&amp;utm_medium=addons&amp;utm_content=addon-page-error' )
				),
				array(
					'a' => array(
						'href' => [],
						'rel'  => [],
					),
				)
			),
			'error_could_not_activate'   => wp_kses(
				sprintf( /* translators: %s - Admin plugins page URL. */
					__( 'Could not activate the plugin. Please activate it on the <a href="%s">Plugins page</a>.', 'affiliate-wp' ),
					esc_url( admin_url( 'plugins.php' ) )
				),
				array(
					'a' => array(
						'href' => [],
					),
				)
			),
			'error_could_not_deactivate' => wp_kses(
				sprintf( /* translators: %s - Admin plugins page URL */
					__( 'Could not deactivate the plugin. Please activate it on the <a href="%s">Plugins page</a>.', 'affiliate-wp' ),
					esc_url( admin_url( 'plugins.php' ) )

				),
				array(
					'a' => array(
						'href' => [],
					),
				)
			),
		)
	);

}
add_action( 'admin_enqueue_scripts', 'affwp_enqueue_assets' );

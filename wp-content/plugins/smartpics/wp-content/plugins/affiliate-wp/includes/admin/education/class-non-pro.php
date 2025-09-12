<?php
/**
 * AffiliateWP Admin Education for non-pro sites.
 *
 * Load the resources necessary to handle AffiliateWP Product Education modals for non-pro sites.
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\Education
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.18.0
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP\Admin\Education;

/**
 * Product education non-pro class.
 *
 * @since 2.18.0
 */
class Non_Pro extends Core {

	/**
	 * Load all hooks.
	 *
	 * @since 2.18.0
	 */
	public function init() {

		// Initiate core.
		parent::init();

		add_action( 'plugins_loaded', array( $this, 'hooks' ) );
	}

	/**
	 * Hooks.
	 *
	 * @since 2.18.0
	 */
	public function hooks() {

		// Execute core hooks.
		parent::init();

		// Allowed only on AffiliateWP pages.
		if ( ! $this->allow_load() ) {
			return;
		}

		// Use is a PRO already, no need to load this.
		if ( affwp_can_access_pro_features() ) {
			return;
		}

		add_action( 'affiliatewp_admin_education_strings', array( $this, 'append_pro_feature_upgrade_strings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
	}

	/**
	 * Load enqueues.
	 *
	 * @since 2.18.0
	 */
	public function enqueues() {

		// Enqueue core scripts.
		parent::init();

		// Only Personal and Plus license holders.
		affiliate_wp()->scripts->enqueue(
			'affiliatewp-admin-education-non-pro',
			array(
				'jquery-confirm',
				'affiliatewp-admin-education-core',
			),
			sprintf(
				'%1$sadmin-education-non-pro%2$s.js',
				affiliate_wp()->scripts->get_path(),
				affiliate_wp()->scripts->get_suffix(),
			)
		);
	}

	/**
	 * Update the strings to add pro feature only contents.
	 *
	 * @since 2.18.0
	 *
	 * @param array $js_strings The strings to localize.
	 *
	 * @return array
	 */
	public function append_pro_feature_upgrade_strings( array $js_strings = array() ) : array {

		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned -- We do not want to align these.
		return array_merge_recursive(
			$js_strings,
			[
				'upgrade' => array(
					'pro'  => $this->get_upgrade_contents( 'pro' ),
					'plus' => $this->get_upgrade_contents( 'plus' ),
				),
				'thanks_for_interest' => esc_html__( 'Thanks for your interest in AffiliateWP Pro!', 'affiliate-wp' ),
				'upgrade_bonus' => wpautop(
					wp_kses(
						__( '<strong>Bonus:</strong> AffiliateWP users get <span>60% off</span> regular price, automatically applied at checkout.', 'affiliate-wp' ),
						array(
							'strong' => [],
							'span'   => [],
						)
					)
				),
			]
		);
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
	}

	/**
	 * Retrieve modal contents from a specific license type.
	 *
	 * @since 2.23.2
	 *
	 * @param string $type The license type. Possible values: personal, plus and pro.
	 *
	 * @return array
	 */
	private function get_upgrade_contents( string $type ) : array {

		if ( ! in_array( $type, array( 'personal', 'plus', 'pro' ), true ) ) {
			return array();
		}

		$directory = sprintf(
			'%1$sincludes/admin/education/upgrade-contents/%2$s',
			AFFILIATEWP_PLUGIN_DIR,
			$type
		);

		// Check if directory exists.
		if ( ! is_dir( $directory ) ) {
			return array();
		}

		$contents = array();

		// Try to open the directory.
		$handle = opendir( $directory );

		if ( ! $handle ) {
			return array();
		}

		// Loop through each file in the directory.
		while ( false !== ( $file = readdir( $handle ) ) ) {

			// Skip . and .. and non-PHP files.
			if ( $file === '.' || $file === '..' || pathinfo( $file, PATHINFO_EXTENSION ) !== 'php' ) {
				continue;
			}

			// Extract file name without extension.
			$file_name = pathinfo( $file, PATHINFO_FILENAME );

			// Read file content (assumes each file returns an array).
			$content = require "{$directory}/{$file}";

			// Store content using file name (without extension) as key.
			if ( is_array( $content ) && ! empty( $content ) ) {

				// These props can also be a function.
				foreach ( array( 'message', 'modal' ) as $prop ) {

					if ( isset( $content[ $prop ] ) && is_callable( $content[ $prop ] ) ) {
						$content[ $prop ] = call_user_func( $content[ $prop ] );
					}
				}

				$contents[ $file_name ] = $content;
			}
		}

		// Close the directory handle.
		closedir( $handle );

		return $contents;
	}

	/**
	 * Generates and returns the upgrade medium parameter.
	 *
	 * @since 2.23.2
	 *
	 * @return string
	 */
	public static function get_utm_medium() : string {

		$page_prefix = 'affiliate-wp-';
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		// Check if the page starts with 'affiliate-wp-' and remove the prefix.
		if ( strpos( $page, $page_prefix ) === 0 ) {
			$upgrade_utm_medium = substr( $page, strlen( $page_prefix ) );
		} else {
			$upgrade_utm_medium = 'settings'; // default value if the page does not start with 'affiliate-wp-'
		}

		// Append 'tab' or 'action' if they exist, replacing underscores with hyphens.
		if ( isset( $_GET['tab'] ) ) {
			$tab_value = str_replace( '_', '-', sanitize_text_field( $_GET['tab'] ) );
			$upgrade_utm_medium .= '-' . $tab_value;
		} elseif ( isset( $_GET['action'] ) ) {
			$action_value = str_replace( '_', '-', sanitize_text_field( $_GET['action'] ) );
			$upgrade_utm_medium .= '-' . $action_value;
		}

		return $upgrade_utm_medium;
	}

	/**
	 * Get an upgrade modal text.
	 *
	 * @since 2.18.0
	 *
	 * @return string
	 */
	public static function upgrade_modal_text() : string {

		return '<p>' .
			sprintf(
				wp_kses( /* translators: %s - affiliatewp.com contact page URL. */
					__( 'Thank you for considering upgrading. If you have any questions, please <a href="%s" target="_blank" rel="noopener noreferrer">let us know</a>.', 'affiliate-wp' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				esc_url(
					affwp_utm_link(
						'https://affiliatewp.com/contact/',
						'Upgrade Follow Up Modal',
						'Contact Support'
					)
				)
			) .
			'</p>' .
			'<p>' .
			wp_kses(
				__( 'After upgrading, your license key will remain the same.<br>You may need to do a quick refresh to unlock your new addons. In your WordPress admin, go to <strong>AffiliateWP &raquo; Settings</strong>. If you don\'t see your updated plan, click <em>refresh</em>.', 'affiliate-wp' ),
				array(
					'strong' => array(),
					'br'     => array(),
					'em'     => array(),
				)
			) .
			'</p>' .
			'<p>' .
			sprintf(
				wp_kses( /* translators: %s - WPForms.com upgrade license docs page URL. */
					__( 'Check out <a href="%s" target="_blank" rel="noopener noreferrer">our documentation</a> for step-by-step instructions.', 'affiliate-wp' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
					)
				),
				'https://affiliatewp.com/docs/upgrade-affiliatewp-license/'
			) .
			'</p>';
	}
}

// Initiate.
( new Non_Pro() )->init();

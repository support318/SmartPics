<?php
/**
 * Base class for Settings register.
 *
 * @package    AffiliateWP
 * @subpackage Core
 * @copyright  Copyright (c) 2024, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.26.1
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP\Admin\Settings\V2\Sections;

/**
 * Addons class.
 *
 * @since 2.26.1
 */
abstract class Base {
	/**
	 * A unique handle name.
	 *
	 * This will be used as a namespace for the settings, for hooks and when registering the section.
	 *
	 * @since 2.26.1
	 * @return string The section name.
	 */
	abstract protected function get_handle() : string;

	/**
	 * Must return the tab name where the settings must be registered.
	 *
	 * @see \Affiliate_WP_Settings::register_admin_tabs For availble tab names.
	 * @since 2.26.1
	 * @return string The tab name.
	 */
	abstract protected function get_tab_name() : string;

	/**
	 * Commonly used to render the section title.
	 *
	 * @since 2.26.1
	 * @return string The section name.
	 */
	abstract protected function get_title() : string;

	/**
	 * Retrieve the array of settings.
	 *
	 * @since 2.26.1
	 * @return array The array of settings.
	 */
	abstract protected function get_settings() : array;

	/**
	 * Retrieve the necessary license level to enable the settings section.
	 *
	 * @see \affwp_is_upgrade_required For the possible value.
	 * @since 2.26.1
	 * @return string The license level.
	 */
	protected function get_license_level() : string {
		return 'plus';
	}

	/**
	 * Retrieve the section help text.
	 *
	 * @since 2.26.1
	 * @return string The help text.
	 */
	protected function get_help_text() : string {
		return '';
	}

	/**
	 * Retrieve the section tooltip content.
	 *
	 * @since 2.26.1
	 * @return string The tooltip content.
	 */
	protected function get_tooltip() : string {
		return '';
	}

	/**
	 * Retrieve the template used to render the section.
	 *
	 * Only table is available now, but others can be created.
	 *
	 * @see \affwp_do_settings_fields To know more on how templates are used.
	 * @since 2.26.1
	 * @return string The template name.
	 */
	protected function get_template() : string {
		return 'table';
	}

	/**
	 * Retrieve the visibility rules.
	 *
	 * @see \Affiliate_WP_Settings::register_section To check how visibility rules are used.
	 * @since 2.26.1
	 * @return array The rules array.
	 */
	protected function get_visibility_rules() : array {
		return [];
	}

	/**
	 * Retrieve if this is for an addon.
	 *
	 * @since 2.26.1
	 * @return bool Whether is an addon or not.
	 */
	protected function is_addon() : bool {
		return false;
	}

	/**
	 * Used to append the section to the global sections array.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function action_register_section() : void {
		add_action( 'admin_init', [ $this, 'register_section' ] );
	}

	/**
	 * Add a filter to append the settings to the global settings array.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function filter_register_settings() : void {
		add_filter(
			sprintf( 'affwp_settings_%s', $this->get_tab_name() ),
			[ $this, 'register_settings' ]
		);
	}

	/**
	 * Prepare the section for registration.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function register_section() : void {
		affiliate_wp()->settings->register_section(
			$this->get_tab_name(),
			$this->get_handle(),
			$this->get_title(),
			
			/**
			 * Allow plugins to add more settings to this section.
			 *
			 * @since 2.26.1
			 *
			 * @param array The array of settings for the MTC section.
			 */
			apply_filters(
				// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- We are replacing with underscores using sprintf instead.
				sprintf(
					'affiliatewp_register_section_%1$s%2$s',
					$this->is_addon() ? 'addon_' : '',
					$this->get_handle()
				),
				array_keys( $this->get_settings() )
			),
			$this->get_help_text(),
			$this->get_visibility_rules(),
			$this->get_template(),
			$this->get_license_level(),
			$this->get_tooltip()
		);
	}

	/**
	 * Prepare the settings for registration.
	 *
	 * @since 2.26.1
	 * @param array $settings The array of settings.
	 * @return array The array with merged settings.
	 */
	public function register_settings( array $settings ) : array {
		return array_merge(
			$settings,
			$this->get_settings()
		);
	}

	/**
	 * Register the section and add the settings.
	 *
	 * @since 2.26.1
	 * @return void
	 */
	public function init() : void {
		$this->filter_register_settings();
		$this->action_register_section();
	}
}

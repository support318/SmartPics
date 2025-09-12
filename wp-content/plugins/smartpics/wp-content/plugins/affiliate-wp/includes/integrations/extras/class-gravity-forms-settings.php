<?php
/**
 * Gravity Forms Form Settings for AffiliateWP
 *
 * @since 2.27.8
 * @author Aubrey Portwood <aportwood@am.co>
 *
 * @see https://docs.gravityforms.com/category/developers/php-api/add-on-framework/
 *
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore -- The GF API requires it.
 * phpcs:disable Squiz.Commenting.FileComment.MissingPackageTag
 */

namespace AffiliateWP\Integrations\Extra;

\GFForms::include_addon_framework();

/**
 * Gravity Forms Form Settings for AffiliateWP
 *
 * @since 2.27.8
 */
class Gravity_Forms_Settings extends \GFAddOn {

	/**
	 * Version of this integration.
	 *
	 * We are using the Addon API so it needs a version.
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_version = \AFFILIATEWP_VERSION;

	/**
	 * Minimum GF Version
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '1.9';

	/**
	 * Slug
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_slug = 'affiliatewp-gravityforms-settings';

	/**
	 * Plugin Path
	 *
	 * Because we use the Addon API here it needs the plugin responsible
	 * for adding these values.
	 *
	 * Note, no Addon is shown, we just use the API.
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_path = 'affiliatewp/affiliatewp.php';

	/**
	 * Path to this file.
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Title of the Menu
	 *
	 * This now adds a menu for AffiliateWP to the side of
	 * the forms settings page. No, it does not need to be
	 * translated.
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_title = 'AffiliateWP';

	/**
	 * Title of the Menu (Short)
	 *
	 * @since 2.27.8
	 *
	 * @var string
	 */
	protected $_short_title = 'AffiliateWP';

	/**
	 * Instance
	 *
	 * This instance, see `self::get_instance()`.
	 *
	 * @since 2.27.8
	 *
	 * @var Gravity_Forms_Settings
	 */
	private static $_instance = null;

	/**
	 * Instance
	 *
	 * If you don't include this it won't load properly.
	 *
	 * Required per https://docs.gravityforms.com/category/developers/php-api/add-on-framework/
	 *
	 * @since 2.27.8
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new Gravity_Forms_Settings();
		}

		return self::$_instance;
	}

	/**
	 * Menu Icon
	 *
	 * Shows the AffiliateWP icon.
	 *
	 * @since 2.27.8
	 */
	public function get_menu_icon() {
		return '<?xml version="1.0" encoding="UTF-8"?> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 71 67" fill="none"><path d="M56.6038 38.9403C55.9849 38.9403 55.3661 39.0022 54.7472 39.0641L45.8357 23.0358C47.8161 20.6841 48.9919 17.6518 48.9919 14.3718C48.9919 6.88373 42.9271 0.81897 35.439 0.81897C27.9509 0.81897 21.8861 6.94561 21.8861 14.3718C21.8861 17.5899 23.0001 20.5604 24.9185 22.912L15.6357 39.1259C14.955 39.0022 14.2743 38.9403 13.5935 38.9403C6.1054 38.9403 0.0406494 45.005 0.0406494 52.4931C0.0406494 59.9813 6.1054 66.046 13.5935 66.046C21.0816 66.046 27.1464 59.9813 27.1464 52.4931C27.1464 49.2751 26.0325 46.3046 24.114 43.953L33.3968 27.7391C34.0775 27.8628 34.7583 27.9247 35.439 27.9247C36.0579 27.9247 36.6767 27.8628 37.2956 27.8009L46.207 43.8292C44.2267 46.1809 43.0509 49.2132 43.0509 52.4931C43.0509 59.9813 49.1157 66.046 56.6038 66.046C64.0919 66.046 70.1566 59.9813 70.1566 52.4931C70.1566 45.005 64.0919 38.9403 56.6038 38.9403ZM35.439 9.73044C37.9763 9.73044 40.0804 11.8346 40.0804 14.3718C40.0804 16.9091 37.9763 19.0132 35.439 19.0132C32.9017 19.0132 30.7976 16.9091 30.7976 14.3718C30.7976 11.8346 32.9017 9.73044 35.439 9.73044ZM13.5935 57.1345C11.0562 57.1345 8.95212 55.0304 8.95212 52.4931C8.95212 49.9559 11.0562 47.8518 13.5935 47.8518C16.1308 47.8518 18.2349 49.9559 18.2349 52.4931C18.2349 55.0304 16.1308 57.1345 13.5935 57.1345ZM56.6038 57.1345C54.0665 57.1345 51.9624 55.0304 51.9624 52.4931C51.9624 49.9559 54.0665 47.8518 56.6038 47.8518C59.1411 47.8518 61.2452 49.9559 61.2452 52.4931C61.2452 55.0304 59.2029 57.1345 56.6038 57.1345Z" fill="#E34F43"></path></svg>';
	}

	/**
	 * Fields
	 *
	 * @since 2.27.8
	 *
	 * @param mixed $form The form.
	 *
	 * @return array Form fields for AffiliateWP.
	 */
	public function form_settings_fields( $form ) {

		foreach ( affwp_get_referral_types() as $value => $type ) {

			$choices[] = [
				'label' => $type['label'] ?? '',
				'value' => $value,
			];
		}

		return [
			[
				'title'  => __( 'AffiliateWP', 'affiliate-wp' ),

				'fields' => [

					// Enable/Disable.
					[
						'name'          => 'affwp_allow_referrals',
						'label'         => __( 'Enable affiliate referral creation for this form', 'affiliate-wp' ),
						'type'          => 'checkbox',
						'choices'       => [
							[
								'label' => __( 'Enable referrals', 'affiliate-wp' ),
								'name'  => 'affwp_allow_referrals',
							],
						],
						'default_value' => (bool) ( $form['affwp_allow_referrals'] ?? false ),
					],

					// Referral Type.
					[
						'name'          => 'affwp_referral_type',
						'label'         => __( 'Referral Type', 'affiliate-wp' ),
						'type'          => 'select',
						'choices'       => $choices,
						'default_value' => $form['affwp_referral_type'] ?? 'sale',
					],
				],
			],
		];
	}

	/**
	 * Save Settings
	 *
	 * This syncs the settings so that settings are available to legacy code.
	 *
	 * @since 2.27.8
	 *
	 * @param array $form     The Form.
	 * @param array $settings The settings.
	 *
	 * @return bool
	 */
	public function save_form_settings( $form, $settings ) : bool {

		return parent::save_form_settings(
			array_merge(
				$form,
				[
					'affwp_allow_referrals' => $settings['affwp_allow_referrals'] ?? '0',
					'affwp_referral_type'   => $settings['affwp_referral_type'] ?? '',
				]
			),
			$settings
		);
	}
}

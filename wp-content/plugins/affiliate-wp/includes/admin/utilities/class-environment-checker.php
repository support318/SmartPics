<?php
/**
 * Environment Checker
 *
 * Checks to see if the environment matches the passed conditions.
 * Supported conditions include:
 *
 * - Type of license (personal, plus, professional, ultimate).
 *
 * @package    AffiliateWP
 * @subpackage Admin\Utils
 * @copyright  Copyright (c) 2022, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.9.5
 */

namespace AffWP\Utils;

use AffWP\Core\License\License_Data;

#[\AllowDynamicProperties]

/**
 * Environment checker class.
 *
 * Checks environment against given license status and/or version.
 *
 * @since 2.9.5
 */
class Environment_Checker {

	/**
	 * @var License_Data
	 */
	protected $license_data;

	/**
	 * Types of license conditions that we support.
	 *
	 * @since 2.9.5
	 *
	 * @var string[]
	 */
	protected $valid_license_conditions = array(
		'personal',
		'plus',
		'professional',
		'ultimate',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->license_data = new License_Data();
	}

	/**
	 * Checks to see if this environment is valid given the specified conditions.
	 *
	 * @since 2.9.5
	 *
	 * @param array $conditions Conditions to check.
	 *
	 * @return bool
	 */
	public function is_valid( $conditions ) {
		// No conditions, always show.
		if ( empty( $conditions ) ) {
			return true;
		}

		$license_conditions = $this->get_license_conditions( $conditions );

		// First ensure we have a corresponding license type.
		if ( false === $this->license_type_matches( $license_conditions ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns only license-type conditions.
	 *
	 * @since 2.9.5
	 *
	 * @param array $conditions Conditions to check.
	 *
	 * @return array
	 */
	private function get_license_conditions( $conditions ) {
		return array_filter(
			$conditions,
			function( $condition ) {
				return $this->is_license_type( $condition );
			}
		);
	}

	/**
	 * Determines if a condition is a license type.
	 *
	 * @since 2.9.5
	 *
	 * @param string $condition Condition to check.
	 * @return bool
	 */
	private function is_license_type( $condition ) {
		return false !== array_search(
			$condition,
			$this->valid_license_conditions,
			true
		);
	}

	/**
	 * Determines if the current license type matches one of the license conditions.
	 *
	 * @since 2.9.5
	 *
	 * @param string $license_type License type that we're checking the site for.
	 *
	 * @return bool
	 */
	public function license_type_matches( $license_conditions ) {
		// Get license ID.
		$license_id = $this->license_data->get_license_id();

		// No price ID is found, assume Personal.
		if ( null === $license_id ) {
			$license_type = 'personal';
		} else {
			$license_type = $this->license_data->get_license_type( $license_id );
		}

		return in_array( strtolower( $license_type ), $license_conditions, true );
	}

}

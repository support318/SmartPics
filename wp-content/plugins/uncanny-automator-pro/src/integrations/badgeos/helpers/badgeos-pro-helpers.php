<?php


namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Badgeos_Helpers;

/**
 * Class Badgeos_Pro_Helpers
 *
 * @package Uncanny_Automator_Pro
 */
class Badgeos_Pro_Helpers extends Badgeos_Helpers {

	/**
	 * Badgeos_Pro_Helpers constructor.
	 */
	public function __construct() {
		// Selectively load options

		add_action(
			'wp_ajax_select_achievements_from_types_BOAWARDACHIEVEMENT',
			array(
				$this,
				'select_achievements_from_types_func',
			)
		);
		add_action(
			'wp_ajax_select_ranks_from_types_BOAWARDRANKS',
			array(
				$this,
				'select_ranks_from_types_func',
			)
		);
		add_action(
			'wp_ajax_select_ranks_from_types_EARNSRANK',
			array(
				$this,
				'select_ranks_from_types_func',
			)
		);
		add_action(
			'wp_ajax_select_achievements_from_types_REVOKEACHIEVEMENT',
			array(
				$this,
				'select_achievements_from_types_func',
			)
		);
		add_action(
			'wp_ajax_select_ranks_from_types_REVOKERANK',
			array(
				$this,
				'select_ranks_from_types_func',
			)
		);
	}

	/**
	 * @param Badgeos_Pro_Helpers $pro
	 */
	public function setPro( Badgeos_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

}

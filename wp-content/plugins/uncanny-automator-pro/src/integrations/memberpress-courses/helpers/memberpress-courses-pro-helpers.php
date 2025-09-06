<?php

namespace Uncanny_Automator_Pro;

use memberpress\courses\lib as lib;
use memberpress\courses\models as models;
use Uncanny_Automator\Memberpress_Courses_Helpers;

/**
 * Class Memberpress_Courses_Pro_Helpers
 *
 * @package Uncanny_Automator
 */
class Memberpress_Courses_Pro_Helpers extends Memberpress_Courses_Helpers {

	/**
	 * @var Memberpress_Courses_Pro_Helpers
	 */
	public $pro;

	/**
	 * @var bool
	 */
	public $load_options = true;

	/**
	 * Memberpress_Courses_Pro_Helpers constructor.
	 */
	public function __construct() {
		$this->load_options = Automator()->helpers->recipe->maybe_load_trigger_options( __CLASS__ );
	}

	/**
	 * @param Memberpress_Courses_Pro_Helpers $pro
	 */
	public function setPro( Memberpress_Courses_Pro_Helpers $pro ) {
		$this->pro = $pro;
	}

	/**
	 * @param $label
	 * @param $option_code
	 * @param $args
	 *
	 * @return array|mixed|void
	 */
	public function get_all_mp_quiz( $label = null, $option_code = 'MPC_QUIZ', $args = array() ) {
		if ( ! $label ) {
			$label = esc_attr__( 'Quiz', 'uncanny-automator-pro' );
		}

		$args = wp_parse_args(
			$args,
			array(
				'uo_include_any' => false,
				'uo_any_label'   => esc_attr__( 'Any quiz', 'uncanny-automator-pro' ),
			)
		);

		$query_args = array(
			'post_type'      => 'mpcs-quiz',
			'posts_per_page' => 999,
			'post_status'    => 'publish',
		);
		$options    = Automator()->helpers->recipe->wp_query( $query_args, $args['uo_include_any'], $args['uo_any_label'] );

		$option = array(
			'option_code'              => $option_code,
			'label'                    => $label,
			'input_type'               => 'select',
			'required'                 => true,
			'options'                  => $options,
			'relevant_tokens'          => array(),
			'custom_value_description' => _x( 'Quiz ID', 'Memberpress', 'uncanny-automator-pro' ),
		);

		return apply_filters( 'uap_option_get_all_mp_quiz', $option );
	}


}

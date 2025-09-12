<?php

namespace Uncanny_Automator_Pro;

/**
 * Class WP_POST_HAS_META_KEY
 *
 * @package Uncanny_Automator_Pro
 */
class WP_POST_HAS_META_KEY extends Action_Condition {

	/**
	 * Method define_condition
	 *
	 * @return void
	 */
	public function define_condition() {

		$this->integration  = 'WP';
		$this->name         = __( '{{A specific}} meta key exists for {{a post}}', 'uncanny-automator-pro' );
		$this->code         = 'POST_HAS_META_KEY';
		$this->dynamic_name = sprintf(
		/* translators: the meta key */
			esc_html__( '{{A specific:%1$s}} meta key exists for {{a post:%2$s}}', 'uncanny-automator-pro' ),
			'METAKEY',
			'POST'
		);
		$this->is_pro        = true;
		$this->requires_user = false;
		$this->deprecated    = false;
	}

	/**
	 * Method fields
	 *
	 * @return array
	 */
	public function fields() {

		return array(
			$this->field->text(
				array(
					'option_code' => 'METAKEY',
					'label'       => esc_html__( 'Meta key', 'uncanny-automator-pro' ),
					'required'    => true,
				)
			),
			$this->field->text(
				array(
					'option_code' => 'POST',
					'label'       => esc_html__( 'Post', 'uncanny-automator-pro' ),
					'required'    => true,
				)
			),
		);
	}

	/**
	 * Evaluate_condition
	 *
	 * Has to use the $this->condition_failed( $message ); method if the
	 * condition is not met.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function evaluate_condition() {

		$meta_key = $this->get_parsed_option( 'METAKEY' );
		$post_id  = $this->get_parsed_option( 'POST' );

		$condition_met = metadata_exists( 'post', $post_id, $meta_key );

		// If the conditions is not met, send an error message and mark the condition as failed.
		if ( false === (bool) $condition_met ) {
			$message = __( 'Post does not have the required meta key: ', 'uncanny-automator-pro' ) . $meta_key;
			$this->condition_failed( $message );
		}
	}

}

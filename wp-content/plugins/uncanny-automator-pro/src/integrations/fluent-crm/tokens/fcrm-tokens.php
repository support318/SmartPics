<?php

namespace Uncanny_Automator_Pro;

use FluentCrm\App\Models\Lists;
use FluentCrm\App\Models\Tag;

/**
 * Class Fcrm_Tokens
 *
 * @package Uncanny_Automator
 */
class Fcrm_Tokens {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'FCRM';

	/**
	 * Wpff_Tokens constructor.
	 */
	public function __construct() {
		add_filter( 'automator_maybe_parse_token', array( $this, 'fcrm_token' ), 20, 6 );
		add_filter( 'automator_fluent_crm_token_contact_id', array( $this, 'token_contact_id_enabled_pro_trigger' ), 10, 3 );
	}

	/**
	 * The token listing and parsing is handled in free. Callback method to
	 * the 'automator_fluent_crm_token_contact_id' filter.
	 *
	 * Using the trigger code, this method inserts the trigger code of the various
	 * Pro triggers so they can also obtain the 'Contact ID' token.
	 *
	 * @param array $trigger_codes
	 * @param array $trigger
	 * @param array $args
	 *
	 * @return array The trigger codes.
	 */
	public function token_contact_id_enabled_pro_trigger( $trigger_codes, $trigger, $args ) {

		$trigger_codes[] = 'ANONFCRMREMOVEUSERLIST';
		$trigger_codes[] = 'ANONFCRMREMOVEUSERTAG';

		return $trigger_codes;

	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return null|string
	 */
	public function fcrm_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( $pieces ) {
			$trigger_log_id = isset( $replace_args['trigger_log_id'] ) ? absint( $replace_args['trigger_log_id'] ) : 0;
			$trigger_id     = $pieces[0];
			if ( ! isset( $pieces[2] ) ) {
				return $value;
			}
			$trigger_meta = $pieces[2];
			if (
				( 'FCRMREMOVEUSERLIST' === $pieces['1'] && 'FCRMLIST' === $trigger_meta ) ||
				( 'ANONFCRMREMOVEUSERLIST' === $pieces['1'] && 'FCRMLIST' === $trigger_meta ) ||
				( 'FCRMREMOVEUSERTAG' === $pieces['1'] && 'FCRMTAG' === $trigger_meta ) ||
				( 'ANONFCRMREMOVEUSERTAG' === $pieces['1'] && 'FCRMTAG' === $trigger_meta )
			) {
				// value is the list or lists(if any list was selected) that the subscriber was added too
				global $wpdb;
				// Get a serialized array of list_ids OR tag_ids added to subscriber
				$entry = $wpdb->get_var(
					"SELECT meta_value
													FROM {$wpdb->prefix}uap_trigger_log_meta
													WHERE meta_key = '$trigger_meta'
													AND automator_trigger_log_id = $trigger_log_id
													AND automator_trigger_id = $trigger_id
													LIMIT 0, 1"
				);
				if ( $entry ) {
					if ( 'FCRMLIST' === $trigger_meta ) {
						// ids added to subscriber during trigger
						$list_ids = maybe_unserialize( $entry );
						if ( is_array( $list_ids ) ) {
							$list_names = array();
							// All lists available in Fluent CRM
							$lists = Lists::orderBy( 'title', 'DESC' )->get();
							// List selected in the trigger ( 0 === any list )
							$trigger_list = $trigger_data[0]['meta']['FCRMLIST'];
							if ( ! empty( $lists ) ) {
								foreach ( $lists as $list ) {
									if ( 0 === absint( $trigger_list ) && in_array( $list->id, $list_ids ) ) {
										// Any list was selected
										$list_names[] = esc_html( $list->title );
									} elseif ( (int) $list->id === (int) $trigger_list ) {
										// a specific list selected
										$list_names[] = esc_html( $list->title );
									}
								}
							}

							return implode( ', ', $list_names );
						}
					}
					if ( 'FCRMTAG' === $trigger_meta ) {
						// ids added to subscriber during trigger
						$tag_ids = maybe_unserialize( $entry );
						if ( is_array( $tag_ids ) ) {
							$tag_names = array();
							// All tags available in Fluent CRM
							$tags = Tag::orderBy( 'title', 'DESC' )->get();
							// Tag selected in the trigger ( 0 === any tag )
							$trigger_tag = $trigger_data[0]['meta']['FCRMTAG'];
							if ( ! empty( $tags ) ) {
								foreach ( $tags as $tag ) {
									if ( 0 === absint( $trigger_tag ) && in_array( $tag->id, $tag_ids ) ) {
										// Any tag was selected
										$tag_names[] = esc_html( $tag->title );
									} elseif ( (int) $tag->id === (int) $trigger_tag ) {
										// a specific tag selected
										$tag_names[] = esc_html( $tag->title );
									}
								}
							}

							return implode( ', ', $tag_names );
						}
					}
				}

				return '';
			}
		}

		return $value;
	}
}

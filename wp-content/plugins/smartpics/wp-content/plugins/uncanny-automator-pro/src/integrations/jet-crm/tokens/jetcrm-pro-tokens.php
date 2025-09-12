<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Jetcrm_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Jetcrm_Pro_Tokens {

	/**
	 * Pro tokens __construct
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_trigger_jetcrm_tokens',
			array(
				$this,
				'jetcrm_deleted_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_jetcrm_tokens',
			array(
				$this,
				'jetcrm_quotes_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_trigger_jetcrm_tokens',
			array(
				$this,
				'jetcrm_invoice_possible_tokens',
			),
			20,
			2
		);
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_jetcrm_pro_tokens' ), 20, 6 );

		add_filter(
			'automator_jet_crm_validate_common_triggers_tokens_parse',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'JETCRM_CONTACT_DELETED',
					'JETCRM_CONTACT',
					'JETCRM_COMPANY_DELETED',
					'JETCRM_INVOICE_DELETED',
					'JETCRM_TRANSACTION_DELETED',
					'JETCRM_TAG_TO_CONTACT',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_jet_crm_validate_common_companies_triggers_tokens_parse',
			function ( $codes, $data ) {
				$codes[] = 'JETCRM_TAG_TO_COMPANY';

				return $codes;
			},
			20,
			2
		);

		add_filter(
			'automator_jet_crm_validate_common_possible_triggers_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'JETCRM_TAG_TO_CONTACT',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);
		add_filter(
			'automator_jet_crm_validate_common_companies_possible_triggers_tokens',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'JETCRM_TAG_TO_COMPANY',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);
		add_filter(
			'automator_jet_crm_validate_common_triggers_tokens_save',
			function ( $codes, $data ) {
				$trigger_codes = array(
					'JETCRM_CONTACT_DELETED',
					'JETCRM_CONTACT',
					'JETCRM_QUOTE_CREATED',
					'JETCRM_QUOTE_ACCEPTED',
					'JETCRM_COMPANY_DELETED',
					'JETCRM_INVOICE_CREATED',
					'JETCRM_INVOICE_DELETED',
					'JETCRM_TRANSACTION_DELETED',
				);
				foreach ( $trigger_codes as $code ) {
					$codes[] = $code;
				}

				return $codes;
			},
			20,
			2
		);
		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );
	}

	/**
	 * save_token_data
	 *
	 * @param mixed $args
	 * @param mixed $trigger
	 *
	 * @return void
	 */
	public function save_token_data( $args, $trigger ) {
		if ( ! isset( $args['trigger_args'] ) || ! isset( $args['entry_args']['code'] ) ) {
			return;
		}

		$trigger_meta_validations = apply_filters(
			'automator_jet_crm_validate_common_triggers_tokens_save_pro',
			array( 'JETCRM_TAG_TO_COMPANY', 'JETCRM_TAG_TO_CONTACT' ),
			$args
		);

		if ( in_array( $args['entry_args']['code'], $trigger_meta_validations ) ) {
			list( $tag_id, $object_type, $object_id ) = $args['trigger_args'];
			$trigger_log_entry                        = $args['trigger_entry'];
			if ( ! empty( $object_id ) && ! empty( $tag_id ) ) {
				Automator()->db->token->save( 'object_id', $object_id, $trigger_log_entry );
				Automator()->db->token->save( 'tag_id', $tag_id, $trigger_log_entry );
			}
		}
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array|array[]|mixed
	 */
	public function jetcrm_deleted_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$trigger_meta_validations = apply_filters(
			'automator_jet_crm_validate_pro_common_possible_triggers_tokens',
			array(
				'JETCRM_CONTACT_DELETED',
				'JETCRM_COMPANY_DELETED',
				'JETCRM_INVOICE_DELETED',
				'JETCRM_TRANSACTION_DELETED',
			),
			$args
		);

		if ( in_array( $trigger_code, $trigger_meta_validations, true ) ) {
			$token_name = __( 'Customer ID', 'uncanny-automator-pro' );
			if ( 'JETCRM_COMPANY_DELETED' === $trigger_code ) {
				$token_name = __( 'Company ID', 'uncanny-automator-pro' );
			} elseif ( 'JETCRM_INVOICE_DELETED' === $trigger_code ) {
				$token_name = __( 'Invoice ID', 'uncanny-automator-pro' );
			} elseif ( 'JETCRM_TRANSACTION_DELETED' === $trigger_code ) {
				$token_name = __( 'Transaction ID', 'uncanny-automator-pro' );
			}

			$fields = array(
				array(
					'tokenId'         => 'object_id',
					'tokenName'       => $token_name,
					'tokenType'       => 'int',
					'tokenIdentifier' => $trigger_code,
				),
			);

			$tokens = array_merge( $tokens, $fields );
		}

		return $tokens;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]|mixed
	 */
	public function jetcrm_quotes_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$trigger_meta_validations = apply_filters(
			'automator_jet_crm_validate_common_quotes_possible_triggers_tokens',
			array( 'JETCRM_QUOTE_CREATED', 'JETCRM_QUOTE_ACCEPTED' ),
			$args
		);

		if ( in_array( $trigger_code, $trigger_meta_validations, true ) ) {

			$fields = array(
				array(
					'tokenId'         => 'object_id',
					'tokenName'       => __( 'Quote ID', 'uncanny-automator-pro' ),
					'tokenType'       => 'int',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsq_contact',
					'tokenName'       => __( 'Quote contact', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsq_status',
					'tokenName'       => __( 'Quote status', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsq_title',
					'tokenName'       => __( 'Quote title', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsq_value',
					'tokenName'       => __( 'Quote value', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsq_content',
					'tokenName'       => __( 'Quote content', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsq_notes',
					'tokenName'       => __( 'Quote notes', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
			);

			$tokens = array_merge( $tokens, $fields );
		}

		return $tokens;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]|mixed
	 */
	public function jetcrm_invoice_possible_tokens( $tokens = array(), $args = array() ) {
		$trigger_code = $args['triggers_meta']['code'];

		$trigger_meta_validations = apply_filters(
			'automator_jet_crm_validate_common_quotes_possible_triggers_tokens',
			array( 'JETCRM_INVOICE_CREATED' ),
			$args
		);

		if ( in_array( $trigger_code, $trigger_meta_validations, true ) ) {

			$fields = array(
				array(
					'tokenId'         => 'object_id',
					'tokenName'       => __( 'Invoice ID', 'uncanny-automator-pro' ),
					'tokenType'       => 'int',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_contact_ID',
					'tokenName'       => __( 'Invoice owner ID', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_contact',
					'tokenName'       => __( 'Invoice owner', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_id_override',
					'tokenName'       => __( 'Invoice reference', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_status',
					'tokenName'       => __( 'Invoice status', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_net',
					'tokenName'       => __( 'Invoice net', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_discount',
					'tokenName'       => __( 'Invoice discount', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_discount_type',
					'tokenName'       => __( 'Invoice discount type', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_shipping',
					'tokenName'       => __( 'Invoice shipping', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_shipping_tax',
					'tokenName'       => __( 'Invoice shipping tax', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_tax',
					'tokenName'       => __( 'Invoice tax', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_addressed_to',
					'tokenName'       => __( 'Invoice address to', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_addressed_from',
					'tokenName'       => __( 'Invoice address from', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_hours_or_quantity',
					'tokenName'       => __( 'Invoice hours or quantity', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_title',
					'tokenName'       => __( 'Invoice items title', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_description',
					'tokenName'       => __( 'Invoice items description', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_quantity',
					'tokenName'       => __( 'Invoice items quantity', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_price',
					'tokenName'       => __( 'Invoice items price', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_tax',
					'tokenName'       => __( 'Invoice items tax', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_net',
					'tokenName'       => __( 'Invoice items net', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_items_total',
					'tokenName'       => __( 'Invoice items total', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_due_date',
					'tokenName'       => __( 'Invoice due date', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
				array(
					'tokenId'         => 'zbsi_created_date',
					'tokenName'       => __( 'Invoice created date', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				),
			);

			$tokens = array_merge( $tokens, $fields );
		}

		return $tokens;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function parse_jetcrm_pro_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( ! is_array( $pieces ) || ! isset( $pieces[1] ) || ! isset( $pieces[2] ) ) {
			return $value;
		}

		$trigger_meta_validations = apply_filters(
			'automator_jet_crm_validate_common_pro_triggers_tokens_parse',
			array( 'JETCRM_QUOTE_CREATED', 'JETCRM_QUOTE_ACCEPTED', 'JETCRM_INVOICE_CREATED', 'JETCRM_TAGS' ),
			array(
				'pieces'       => $pieces,
				'recipe_id'    => $recipe_id,
				'trigger_data' => $trigger_data,
				'user_id'      => $user_id,
				'replace_args' => $replace_args,
			)
		);

		if ( ! array_intersect( $trigger_meta_validations, $pieces ) ) {
			return $value;
		}

		global $wpdb;
		$to_replace     = $pieces[2];
		$object_id      = Automator()->db->token->get( 'object_id', $replace_args );
		$object_details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}zbs_quotes WHERE ID = %d", $object_id ) );
		if ( 'JETCRM_INVOICE_CREATED' === $pieces[1] ) {
			$object_details = zeroBS_getInvoice( $object_id );
		}

		switch ( $to_replace ) {
			case 'zbsq_contact':
				$contact = $wpdb->get_row( $wpdb->prepare( "SELECT contact.zbsc_fname,contact.zbsc_lname FROM {$wpdb->prefix}zbs_object_links as obj_links INNER JOIN {$wpdb->prefix}zbs_contacts as contact ON obj_links.zbsol_objid_to=contact.ID WHERE obj_links.zbsol_objtype_from=%d AND obj_links.zbsol_objtype_to=%d AND obj_links.zbsol_objid_from=%d", ZBS_TYPE_QUOTE, ZBS_TYPE_CONTACT, $object_id ) );
				$value   = $contact->zbsc_fname . ' ' . $contact->zbsc_lname;
				break;
			case 'zbsi_contact':
				$contact = $wpdb->get_row( $wpdb->prepare( "SELECT contact.zbsc_fname,contact.zbsc_lname FROM {$wpdb->prefix}zbs_object_links as obj_links INNER JOIN {$wpdb->prefix}zbs_contacts as contact ON obj_links.zbsol_objid_to=contact.ID WHERE obj_links.zbsol_objtype_from=%d AND obj_links.zbsol_objtype_to=%d AND obj_links.zbsol_objid_from=%d", ZBS_TYPE_INVOICE, ZBS_TYPE_CONTACT, $object_id ) );
				$value   = $contact->zbsc_fname . ' ' . $contact->zbsc_lname;
				break;
			case 'zbsi_contact_ID':
				$value = $wpdb->get_var( $wpdb->prepare( "SELECT zbsol_objid_to FROM {$wpdb->prefix}zbs_object_links WHERE zbsol_objtype_from=%d AND zbsol_objtype_to=%d AND zbsol_objid_from=%d", ZBS_TYPE_INVOICE, ZBS_TYPE_CONTACT, $object_id ) );
				break;
			case 'zbsq_status':
				$value = 'Accepted';
				if ( $object_details->zbsq_accepted <= 0 && $object_details->zbsq_template <= 0 ) {
					$value = 'Draft';
				} elseif ( $object_details->zbsq_accepted <= 0 && $object_details->zbsq_template > 0 ) {
					$value = 'Published, Unaccepted';
				}
				break;
			case 'zbsq_notes':
				$value = $object_details->zbsq_notes;
				break;
			case 'zbsq_content':
				$value = $object_details->zbsq_content;
				break;
			case 'zbsq_value':
				$value = $object_details->zbsq_value;
				break;
			case 'zbsq_title':
				$value = $object_details->zbsq_title;
				break;
			case 'zbsi_id_override':
				$value = $object_details['id_override'];
				break;
			case 'zbsi_status':
				$value = $object_details['status'];
				break;
			case 'zbsi_due_date':
				$value = $object_details['due_date_date'];
				break;
			case 'zbsi_net':
				$value = $object_details['net'];
				break;
			case 'zbsi_discount':
				$value = $object_details['discount'];
				break;
			case 'zbsi_discount_type':
				$value = $object_details['discount_type'];
				break;
			case 'zbsi_shipping':
				$value = $object_details['shipping'];
				break;
			case 'zbsi_shipping_tax':
				$value = $object_details['shipping_tax'];
				break;
			case 'zbsi_tax':
				$value = $object_details['tax'];
				break;
			case 'zbsi_addressed_to':
				$value = $object_details['addressed_to'];
				break;
			case 'zbsi_addressed_from':
				$value = $object_details['addressed_from'];
				break;
			case 'zbsi_hours_or_quantity':
				$value = ( $object_details['hours_or_quantity'] > 0 ) ? 'Quantity' : 'Hours';
				break;
			case 'zbsi_created_date':
				$value = mysql2date( sprintf( __( '%1$s @ %2$s', 'uncanny-automator-pro' ), get_option( 'date_format' ), get_option( 'time_format' ) ), gmdate( 'Y-m-d H:i:s', $object_details['created'] ) );
				break;
			case 'zbsi_items_title':
				$lineitems = $object_details['lineitems'];
				$titles    = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$titles[] = $lineitem['title'];
					}
				}
				$value = join( ', ', $titles );
				break;
			case 'zbsi_items_description':
				$lineitems    = $object_details['lineitems'];
				$descriptions = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$descriptions[] = $lineitem['desc'];
					}
				}
				$value = join( ', ', $descriptions );
				break;
			case 'zbsi_items_quantity':
				$lineitems = $object_details['lineitems'];
				$quantity  = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$quantity[] = $lineitem['quantity'];
					}
				}
				$value = join( ', ', $quantity );
				break;
			case 'zbsi_items_price':
				$lineitems = $object_details['lineitems'];
				$price     = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$price[] = $lineitem['price'];
					}
				}
				$value = join( ', ', $price );
				break;
			case 'zbsi_items_tax':
				$lineitems = $object_details['lineitems'];
				$tax       = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$tax[] = $lineitem['tax'];
					}
				}
				$value = join( ', ', $tax );
				break;
			case 'zbsi_items_net':
				$lineitems = $object_details['lineitems'];
				$net       = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$net[] = $lineitem['net'];
					}
				}
				$value = join( ', ', $net );
				break;
			case 'zbsi_items_total':
				$lineitems = $object_details['lineitems'];
				$total     = array();
				if ( count( $lineitems ) > 0 ) {
					foreach ( $lineitems as $lineitem ) {
						$total[] = $lineitem['total'];
					}
				}
				$value = join( ', ', $total );
				break;
			case 'object_id';
				$value = $object_id;
				break;
			case 'JETCRM_TAGS';
				$tag_id = Automator()->db->token->get( 'tag_id', $replace_args );
				$value  = $wpdb->get_var( $wpdb->prepare( "SELECT `zbstag_name` FROM `{$wpdb->prefix}zbs_tags` WHERE ID = %d", $tag_id ) );
				break;
		}

		return $value;
	}

}

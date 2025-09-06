<?php

namespace Uncanny_Automator_Pro;

/**
 * Class Wpjm_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class Wpjm_Pro_Tokens {

	/**
	 * Integration code
	 *
	 * @var string
	 */
	public static $integration = 'WPJM';

	/**
	 *
	 */
	public function __construct() {
		add_filter(
			'automator_maybe_trigger_wpjm_wpjmappstatus_tokens',
			array(
				$this,
				'wpjm_jobapplication_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_parse_token',
			array(
				$this,
				'wpjm_token',
			),
			20,
			6
		);
		add_filter(
			'automator_maybe_parse_token',
			array(
				$this,
				'wpjm_job_id_token',
			),
			99999,
			6
		);
		add_filter(
			'automator_maybe_parse_token',
			array(
				$this,
				'wpjm_parse_jobs_token',
			),
			20,
			6
		);
		add_filter(
			'automator_maybe_trigger_wpjm_wpjmappjobtype_tokens',
			array(
				$this,
				'wpjm_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_trigger_wpjm_wpjmuserupdatesajob_tokens',
			array(
				$this,
				'wpjm_jobs_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_trigger_wpjm_wpjmjobisfilled_tokens',
			array(
				$this,
				'wpjm_jobs_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_trigger_wpjm_wpjmsetstatus_tokens',
			array(
				$this,
				'wpjm_jobappstatus_possible_tokens',
			),
			20,
			2
		);
		add_filter(
			'automator_maybe_trigger_wpjm_wpjmspecificjob_tokens',
			array(
				$this,
				'wpjm_jobappreceived_possible_tokens',
			),
			20,
			2
		);
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wpjm_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'WPJMJOBCATEGORIES',
				'tokenName'       => __( 'Job categories', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERNAME',
				'tokenName'       => __( 'Job owner username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBOWNEREMAIL',
				'tokenName'       => __( 'Job owner email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERFIRSTNAME',
				'tokenName'       => __( 'Job owner first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERLASTNAME',
				'tokenName'       => __( 'Job owner last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBTITLE',
				'tokenName'       => __( 'Job title', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBID',
				'tokenName'       => __( 'Job ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBLOCATION',
				'tokenName'       => __( 'Location', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBDESCRIPTION',
				'tokenName'       => __( 'Job description', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBAPPURL',
				'tokenName'       => __( 'Application email/URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBCOMPANYNAME',
				'tokenName'       => __( 'Company name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBWEBSITE',
				'tokenName'       => __( 'Website', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBTAGLINE',
				'tokenName'       => __( 'Tagline', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBVIDEO',
				'tokenName'       => __( 'Video', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBTWITTER',
				'tokenName'       => __( 'Twitter username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMJOBLOGOURL',
				'tokenName'       => __( 'Logo URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMSUBMITJOB',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONNAME',
				'tokenName'       => __( 'Candidate name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONEMAIL',
				'tokenName'       => __( 'Candidate email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONMESSAGE',
				'tokenName'       => __( 'Message', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONCV',
				'tokenName'       => __( 'CV', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wpjm_jobs_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_meta = $args['meta'];
		$fields       = array(
			array(
				'tokenId'         => 'WPJMJOBCATEGORIES',
				'tokenName'       => __( 'Job categories', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERNAME',
				'tokenName'       => __( 'Job owner username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNEREMAIL',
				'tokenName'       => __( 'Job owner email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERFIRSTNAME',
				'tokenName'       => __( 'Job owner first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERLASTNAME',
				'tokenName'       => __( 'Job owner last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTITLE',
				'tokenName'       => __( 'Job title', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBID',
				'tokenName'       => __( 'Job ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTYPE',
				'tokenName'       => __( 'Job type', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBLOCATION',
				'tokenName'       => __( 'Location', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBDESCRIPTION',
				'tokenName'       => __( 'Job description', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBAPPURL',
				'tokenName'       => __( 'Application email/URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBCOMPANYNAME',
				'tokenName'       => __( 'Company name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBWEBSITE',
				'tokenName'       => __( 'Website', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTAGLINE',
				'tokenName'       => __( 'Tagline', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBVIDEO',
				'tokenName'       => __( 'Video', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTWITTER',
				'tokenName'       => __( 'Twitter username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBLOGOURL',
				'tokenName'       => __( 'Logo URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wpjm_jobappstatus_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'WPJMJOBCATEGORIES',
				'tokenName'       => __( 'Job categories', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERNAME',
				'tokenName'       => __( 'Job owner username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNEREMAIL',
				'tokenName'       => __( 'Job owner email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERFIRSTNAME',
				'tokenName'       => __( 'Job owner first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERLASTNAME',
				'tokenName'       => __( 'Job owner last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTITLE',
				'tokenName'       => __( 'Job title', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTYPE',
				'tokenName'       => __( 'Job type', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBLOCATION',
				'tokenName'       => __( 'Location', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBDESCRIPTION',
				'tokenName'       => __( 'Job description', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBAPPURL',
				'tokenName'       => __( 'Application email/URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBCOMPANYNAME',
				'tokenName'       => __( 'Company name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBWEBSITE',
				'tokenName'       => __( 'Website', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTAGLINE',
				'tokenName'       => __( 'Tagline', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBVIDEO',
				'tokenName'       => __( 'Video', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTWITTER',
				'tokenName'       => __( 'Twitter username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBLOGOURL',
				'tokenName'       => __( 'Logo URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONNAME',
				'tokenName'       => __( 'Candidate name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONEMAIL',
				'tokenName'       => __( 'Candidate email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONMESSAGE',
				'tokenName'       => __( 'Message', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONCV',
				'tokenName'       => __( 'CV', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wpjm_jobappreceived_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'WPJMJOBCATEGORIES',
				'tokenName'       => __( 'Job categories', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERNAME',
				'tokenName'       => __( 'Job owner username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNEREMAIL',
				'tokenName'       => __( 'Job owner email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERFIRSTNAME',
				'tokenName'       => __( 'Job owner first name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBOWNERLASTNAME',
				'tokenName'       => __( 'Job owner last name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBLOCATION',
				'tokenName'       => __( 'Location', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBDESCRIPTION',
				'tokenName'       => __( 'Job description', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBAPPURL',
				'tokenName'       => __( 'Application email/URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBCOMPANYNAME',
				'tokenName'       => __( 'Company name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBWEBSITE',
				'tokenName'       => __( 'Website', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTAGLINE',
				'tokenName'       => __( 'Tagline', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBVIDEO',
				'tokenName'       => __( 'Video', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBTWITTER',
				'tokenName'       => __( 'Twitter username', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMJOBLOGOURL',
				'tokenName'       => __( 'Logo URL', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONNAME',
				'tokenName'       => __( 'Candidate name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONEMAIL',
				'tokenName'       => __( 'Candidate email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONMESSAGE',
				'tokenName'       => __( 'Message', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONCV',
				'tokenName'       => __( 'CV', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONOWNERID',
				'tokenName'       => __( 'Job owner ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONCANDIDATEID',
				'tokenName'       => __( 'Candidate ID', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wpjm_jobapplication_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_integration = $args['integration'];
		$trigger_meta        = $args['meta'];

		$fields = array(
			array(
				'tokenId'         => 'WPJMJOBCATEGORIES',
				'tokenName'       => __( 'Job categories', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONNAME',
				'tokenName'       => __( 'Candidate name', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONEMAIL',
				'tokenName'       => __( 'Candidate email', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONMESSAGE',
				'tokenName'       => __( 'Message', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
			array(
				'tokenId'         => 'WPJMAPPLICATIONCV',
				'tokenName'       => __( 'CV', 'uncanny-automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => 'WPJMJOBAPPLICATIONID',
			),
		);

		$tokens = array_merge( $tokens, $fields );

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
	 * @return null|string
	 */
	public function wpjm_job_id_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( $pieces ) {
			if ( 'WPJMSUBMITJOB' === $pieces[1] && 'WPJMJOBID' === $pieces[2] ) {
				$trigger_meta = $pieces[1];
				$value        = $this->return_token_value( $trigger_meta, $replace_args );
			}
		}

		return $value;
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
	public function wpjm_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( $pieces ) {
			if (
				in_array( 'WPJMJOBAPPLICATIONSTATUS', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				in_array( 'WPJMJOBAPPLICATIONRECEIVED', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				in_array( 'WPJMJOBMARKNOTFILLED', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				in_array( 'WPJMJOBMARKFILLED', $pieces ) //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			) {
				$trigger_meta = $pieces[1];
				$field        = $pieces[2];
				if ( 'WPJMAPPSTATUS' === $field || 'WPJMJOBTYPE' === $field || 'WPJMAPPJOBTYPE' === $field ) {
					$trigger_meta = $pieces[1] . ':' . $pieces[2];
				}
				$entry = $this->return_token_value( $trigger_meta, $replace_args );
				if ( 'WPJMAPPSTATUS' === $pieces[2] || 'WPJMJOBTYPE' === $pieces[2] || 'WPJMAPPJOBTYPE' === $pieces[2] ) {
					$value = $entry;
				}
			}
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function wpjm_parse_jobs_token( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $pieces ) ) {
			return $value;
		}
		if ( empty( $trigger_data ) ) {
			return $value;
		}
		if (
			in_array( 'WPJMUSERUPDATESAJOB', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPJMSETSTATUS', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPJMJOBID', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPMJOLDSTATUS', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPJMAPPLICATIONRECEIVED', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPJMSPECIFICJOB', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPJMJOBISFILLED', $pieces ) || //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			in_array( 'WPJMJOBCATEGORIES', $pieces ) //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		) {
			$trigger_meta = $pieces[2];
			$value        = $this->return_token_value( $trigger_meta, $replace_args );
		}

		return $value;
	}

	/**
	 * @param $trigger_meta
	 * @param $replace_args
	 *
	 * @return mixed|string
	 */
	public function return_token_value( $trigger_meta, $replace_args ) {
		$value = Automator()->db->token->get( $trigger_meta, $replace_args );
		if ( empty( $value ) || ( is_array( $value ) ) && empty( array_filter( $value ) ) ) {
			$value = '';
		}
		if ( is_array( $value ) && ! empty( array_filter( $value ) ) ) {
			if ( is_array( $value[0] ) ) {
				$value = array_shift( $value );
			}
			$value = join( ',', $value );
		}

		return $value;
	}

}

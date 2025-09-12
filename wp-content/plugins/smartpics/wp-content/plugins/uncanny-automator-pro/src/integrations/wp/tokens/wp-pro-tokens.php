<?php

namespace Uncanny_Automator_Pro;

use Uncanny_Automator\Wp_Tokens;
use WP_Comment;
use WP_Post;

/**
 * Class WP_Pro_Tokens
 *
 * @package Uncanny_Automator_Pro
 */
class WP_Pro_Tokens extends Wp_Tokens {

	/**
	 *
	 */
	public function __construct() {

		add_action( 'uap_wp_comment_approve', array( $this, 'uap_wp_comment_approve' ), 10, 4 );
		add_filter(
			'automator_maybe_trigger_wp_wpcommentonpost_tokens',
			array(
				$this,
				'wp_wpcommentonpost_possible_tokens',
			),
			20,
			2
		);
		$trigger_codes = array(
			'wpposttrashed',
			'wpreplyoncomment',
			'wpuserspostupdated',
			'wppostinstatus',
			'wppostintaxonomy',
			'wppostnotinstatus',
			'wppoststatus',
			'wpusersposttrashed',
			'wpuserspoststatus',
			'anon_wppoststatus',
			'wpposttaxonomy',
			//'wp_user_views_archive_post',
			//'wp_anon_views_archive_post',
		);
		foreach ( $trigger_codes as $code ) {
			add_filter(
				'automator_maybe_trigger_wp_' . $code . '_tokens',
				array(
					$this,
					'wp_posts_possible_tokens',
				),
				20,
				2
			);
		}
		add_filter(
			'automator_maybe_trigger_wp_wp_user_views_archive_post_tokens',
			array(
				$this,
				'wp_post_archive_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_wp_wp_anon_views_archive_post_tokens',
			array(
				$this,
				'wp_post_archive_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_wp_poststatusupdated_tokens',
			array(
				$this,
				'wp_fix_post_token_id_possible_tokens',
			),
			200,
			2
		);

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_tokens' ), 300, 6 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_comments_tokens' ), 220, 6 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_status_tokens' ), 200, 6 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_user_fields_tokens' ), 210, 6 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_token_roles' ), 21, 6 );
		add_filter(
			'automator_maybe_trigger_wp_umetakey_tokens',
			array(
				$this,
				'wp_usermeta_possible_tokens',
			),
			20,
			2
		);

		add_filter(
			'automator_maybe_trigger_wp_wpdeleteuser_tokens',
			array(
				$this,
				'wp_users_possible_tokens',
			),
			20,
			2
		);

		add_action( 'automator_before_trigger_completed', array( $this, 'save_token_data' ), 20, 2 );
		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_users_tokens' ), 21, 6 );

		add_filter( 'automator_maybe_parse_token', array( $this, 'parse_wp_post_meta_tokens' ), 21, 6 );
	}

	/**
	 * Comment approve.
	 *
	 * @param \WP_Comment $comment
	 * @param             $recipe_id
	 * @param             $trigger_id
	 * @param             $args
	 */
	public function uap_wp_comment_approve( WP_Comment $comment, $recipe_id, $trigger_id, $args ) {
		if ( empty( $comment ) || empty( $recipe_id ) || empty( $trigger_id ) || ! is_array( $args ) ) {
			return;
		}

		foreach ( $args as $trigger_result ) {
			if ( true === $trigger_result['result'] ) {
				$user_id        = (int) $comment->user_id;
				$trigger_log_id = (int) $trigger_result['args']['trigger_log_id'];
				$run_number     = (int) $trigger_result['args']['run_number'];

				$args = array(
					'user_id'        => $user_id,
					'trigger_id'     => $trigger_id,
					'meta_key'       => 'comment_id',
					'meta_value'     => $comment->comment_ID,
					'run_number'     => $run_number, //get run number
					'trigger_log_id' => $trigger_log_id,
				);

				Automator()->insert_trigger_meta( $args );
			}
		}
	}

	/**
	 * Comment possible tokens.
	 *
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wp_wpcommentonpost_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_meta = $args['meta'];
		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'WPPOSTTYPES',
				'tokenName'       => __( 'Post title', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'WPPOSTTYPES_ID',
				'tokenName'       => __( 'Post ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'WPPOSTTYPES_URL',
				'tokenName'       => __( 'Post URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'WPPOSTTYPES_TYPE',
				'tokenName'       => __( 'Post type', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTEXCERPT',
				'tokenName'       => __( 'Post excerpt', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTCONTENT',
				'tokenName'       => __( 'Post content (raw)', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTCONTENT_BEAUTIFIED',
				'tokenName'       => __( 'Post content (formatted)', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'WPPOSTTYPES_THUMB_ID',
				'tokenName'       => __( 'Post featured image ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'WPPOSTTYPES_THUMB_URL',
				'tokenName'       => __( 'Post featured image URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTSTATUS',
				'tokenName'       => __( 'Post status', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORFN',
				'tokenName'       => __( 'Post author first name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORLN',
				'tokenName'       => __( 'Post author last name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORDN',
				'tokenName'       => __( 'Post author display name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHOREMAIL',
				'tokenName'       => __( 'Post author email', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORURL',
				'tokenName'       => __( 'Post author URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTCOMMENTERNAME',
				'tokenName'       => __( 'Commenter name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'POSTCOMMENTEREMAIL',
				'tokenName'       => __( 'Commenter email', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'POSTCOMMENTERWEBSITE',
				'tokenName'       => __( 'Commenter website', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'comment',
				'tokenName'       => __( 'Comment content', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'POSTCOMMENTURL',
				'tokenName'       => __( 'Comment URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'POSTCOMMENTDATE',
				'tokenName'       => __( 'Comment submitted date', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
			array(
				'tokenId'         => 'POSTCOMMENTSTATUS',
				'tokenName'       => __( 'Comment status', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_meta,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function wp_posts_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'POSTTITLE',
				'tokenName'       => __( 'Post title', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTID',
				'tokenName'       => __( 'Post ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTURL',
				'tokenName'       => __( 'Post URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTNAME',
				'tokenName'       => __( 'Post slug', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'WPPOSTTYPES',
				'tokenName'       => __( 'Post type', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTEXCERPT',
				'tokenName'       => __( 'Post excerpt', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTCONTENT',
				'tokenName'       => __( 'Post content (raw)', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTCONTENT_BEAUTIFIED',
				'tokenName'       => __( 'Post content (formatted)', 'uncanny_automator' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTIMAGEID',
				'tokenName'       => __( 'Post featured image ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTIMAGEURL',
				'tokenName'       => __( 'Post featured image URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORID',
				'tokenName'       => __( 'Post author ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORFN',
				'tokenName'       => __( 'Post author first name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORLN',
				'tokenName'       => __( 'Post author last name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORDN',
				'tokenName'       => __( 'Post author display name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHOREMAIL',
				'tokenName'       => __( 'Post author email', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'POSTAUTHORURL',
				'tokenName'       => __( 'Post author URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]|mixed
	 */
	public function wp_post_archive_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'TERMID',
				'tokenName'       => __( 'Term ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'ARCHIVEURL',
				'tokenName'       => __( 'Archive URL', 'uncanny-automator-pro' ),
				'tokenType'       => 'url',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'TERMPOSTCOUNT',
				'tokenName'       => __( 'Term post count', 'uncanny-automator-pro' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_code,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|void
	 */
	public function wp_fix_post_token_id_possible_tokens( $tokens = array(), $args = array() ) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}
		$trigger_code = $args['triggers_meta']['code'];
		foreach ( $tokens as $k => $token ) {
			if ( $token['tokenId'] === 'WPPOSTTYPES' && 'WPPOSTNOTINSTATUS' === $trigger_code ) {
				array_splice( $tokens, $k, 1 );
				$tokens[] = array(
					'tokenId'         => 'POSTTYPE',
					'tokenName'       => __( 'Post type', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				);

			}
			if ( $token['tokenId'] === 'POSTTITLE' && 'WPPOSTSTATUS' === $trigger_code ) {
				array_splice( $tokens, $k, 1 );
				$tokens[] = array(
					'tokenId'         => 'WPPOST',
					'tokenName'       => __( 'Post title', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_code,
				);
			}
		}

		return $tokens;
	}

	/**
	 * Parse WP Tokens.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function parse_wp_post_meta_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( in_array( 'WPPOSTMETASPECIFCVAL', $pieces, true ) || in_array( 'ANONWPPOSTMETASPECIFCVAL', $pieces, true ) ) {
			if ( $trigger_data ) {
				foreach ( $trigger_data as $trigger ) {
					$trigger_id     = $trigger['ID'];
					$trigger_log_id = $replace_args['trigger_log_id'];
					$meta_key       = $pieces[2];
					$meta_value     = Automator()->helpers->recipe->get_form_data_from_trigger_meta( $meta_key, $trigger_id, $trigger_log_id, $user_id );
					if ( ! empty( $meta_value ) ) {
						$value = maybe_unserialize( $meta_value );
					}
				}
			}
		}

		return $value;
	}

	/**
	 * Parse WP Tokens.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function parse_wp_tokens(
		$value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args
	) {

		if ( empty( $pieces ) ) {
			return $value;
		}

		// Parse POSTURL token.
		if ( isset( $pieces[1] ) && 'WPPOSTINTAXONOMY' === $pieces[1] && isset( $pieces[2] ) && 'POSTURL' === $pieces[2] ) {
			return get_permalink(
				absint(
					Automator()->db->token->get(
						$pieces[0] . ':WPPOSTINTAXONOMY:POSTID',
						$replace_args
					)
				)
			);
		}

		if ( ! in_array( 'WPUSERSPOSTUPDATED', $pieces, true ) && ! in_array( 'WPPOSTTRASHED', $pieces, true )
			 && ! in_array( 'WPPOSTINSTATUS', $pieces, true ) && ! in_array( 'WPPOSTINTAXONOMY', $pieces, true ) &&
			 ! in_array( 'WPPOSTNOTINSTATUS', $pieces, true ) && ! in_array( 'WPPOSTMETASPECIFCVAL', $pieces, true ) &&
			 ! in_array( 'ANONWPPOSTMETASPECIFCVAL', $pieces, true ) && ! in_array( 'WPPOSTSTATUS', $pieces, true ) &&
			 ! in_array( 'WPUSERSPOSTTRASHED', $pieces, true ) && ! in_array( 'WPUSERSPOSTSTATUS', $pieces, true ) &&
			 ! in_array( 'WPPOSTUPDATED', $pieces, true ) && ! in_array( 'WPPOSTTAXONOMY', $pieces, true ) &&
			 ! in_array( 'WP_USER_VIEWS_ARCHIVE_POST', $pieces, true ) && ! in_array( 'WP_ANON_VIEWS_ARCHIVE_POST', $pieces, true ) &&
			 ! in_array( 'ANON_WPPOSTSTATUS', $pieces, true ) ) {
			return $value;
		}

		$to_replace = $pieces[2];
		$post_id    = Automator()->db->token->get( 'post_id', $replace_args );
		$post       = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			return $value;
		}

		switch ( $to_replace ) {
			case 'POSTTITLE':
			case 'WPPOST':
				$value = $post->post_title;
				break;
			case 'WPPOSTTYPES':
			case 'WPPOSTTYPES_TYPE':
			case 'POSTTYPE':
				$value = $post->post_type;
				if ( ( 'WPPOSTMETASPECIFCVAL' === $pieces[1] || 'ANONWPPOSTMETASPECIFCVAL' === $pieces[1] || 'WPPOSTUPDATED' === $pieces[1] ) && 'WPPOSTTYPES' === $pieces[2] ) {
					$value = $post->post_title;
				}
				break;
			case 'WPPOSTTYPES_URL':
			case 'POSTURL':
				$value = get_permalink( $post->ID );
				break;
			case 'WPPOSTTYPES_POSTNAME':
			case 'POSTNAME':
				$value = $post->post_name;
				break;
			case 'POSTEXCERPT':
			case 'WPPOSTTYPES_EXCERPT':
				$value = $post->post_excerpt;
				break;
			case 'POSTSTATUSUPDATED':
			case 'SPECIFICPOSTTYPESTATUSUPDATED':
				$value = $post->post_status;
				break;
			case 'POSTCONTENT':
			case 'WPPOSTTYPES_CONTENT':
				$value = $post->post_content;
				break;
			case 'POSTCONTENT_BEAUTIFIED':
			case 'WPPOSTTYPES_CONTENT_BEAUTIFIED':
				$content = get_the_content( null, false, $post->ID );
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content ); //phpcs:ignore Generic.PHP.Syntax.PHPSyntax
				$value   = $content;
				break;
			case 'WPPOSTTYPES_THUMB_ID':
			case 'POSTIMAGEID':
				$value = get_post_thumbnail_id( $post->ID );
				break;
			case 'WPPOSTTYPES_THUMB_URL':
			case 'POSTIMAGEURL':
				$value = get_the_post_thumbnail_url( $post->ID );
				break;
			case 'POSTSTATUS':
			case 'WPPOSTTYPES_STATUS':
				$value = $post->post_status;
				break;
			case 'POSTAUTHORID':
				$value = $post->post_author;
				break;
			case 'POSTAUTHORFN':
				$value = get_the_author_meta( 'user_firstname', $post->post_author );
				break;
			case 'POSTAUTHORLN':
				$value = get_the_author_meta( 'user_lastname', $post->post_author );
				break;
			case 'POSTAUTHORDN':
				$value = get_the_author_meta( 'display_name', $post->post_author );
				break;
			case 'POSTAUTHOREMAIL':
				$value = get_the_author_meta( 'user_email', $post->post_author );
				break;
			case 'POSTAUTHORURL':
				$value = get_the_author_meta( 'url', $post->post_author );
				break;
			case 'POSTID':
			case 'WPPOSTTYPES_ID':
				$value = $post->ID;
				break;
			case 'ARCHIVEURL':
				$value = Automator()->db->token->get( 'archive_url', $replace_args );
				break;
			case 'TERMPOSTCOUNT':
				$value = Automator()->db->token->get( 'term_post_count', $replace_args );
				break;
			case 'TERMID':
				$value = Automator()->db->token->get( 'term_id', $replace_args );
				break;
			case 'WPTAXONOMIES':
			case 'SPECIFICTAXONOMY':
			default:
				global $wpdb;
				$trigger_id = absint( $pieces[0] );
				$entry      = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}uap_trigger_log_meta WHERE meta_key LIKE %s AND automator_trigger_id = %d ORDER BY ID DESC LIMIT 0,1", "%%$to_replace", $trigger_id ) );
				$value      = maybe_unserialize( $entry );
				break;
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
	 * @return false|int|string|\WP_Error
	 */
	public function parse_wp_comments_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {
		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( ! in_array( 'WPCOMMENTONPOST', $pieces, true ) && ! in_array( 'COMMENTAPPROVED', $pieces, true ) &&
			 ! in_array( 'COMMENTPARENT', $pieces, true ) && ! in_array( 'WPREPLYONCOMMENT', $pieces, true ) &&
			 ! in_array( 'WPCOMMENTSUBMITTED', $pieces, true ) && ! in_array( 'WPCOMMENTAPPROVED', $pieces, true ) ) {
			return $value;
		}

		$to_replace = $pieces[2];
		$comment_id = Automator()->db->token->get( 'comment_id', $replace_args );
		$comment    = get_comment( $comment_id );

		if ( ! $comment instanceof WP_Comment ) {
			return $value;
		}

		switch ( $to_replace ) {
			case 'COMMENTAPPROVED':
			case 'WPCOMMENTONPOST':
			case 'POSTTITLE':
			case 'WPPOSTTYPES':
				$value = get_the_title( $comment->comment_post_ID );
				if ( 'WPREPLYONCOMMENT' === $pieces[1] && 'WPPOSTTYPES' === $pieces[2] ) {
					$value = get_post_type( $comment->comment_post_ID );
				}
				break;
			case 'WPPOSTTYPES_TYPE':
			case 'COMMENTAPPROVED_TYPE':
				$value = get_post_type( $comment->comment_post_ID );
				break;
			case 'WPPOSTTYPES_URL':
			case 'COMMENTAPPROVED_URL':
			case 'POSTURL':
				$value = get_permalink( $comment->comment_post_ID );
				break;
			case 'COMMENTAPPROVED_EXCERPT':
			case 'POSTEXCERPT':
				$value = get_the_excerpt( $comment->comment_post_ID );
				break;
			case 'COMMENTAPPROVED_CONTENT':
			case 'POSTCONTENT':
				$post = get_post( $comment->comment_post_ID );
				if ( $post instanceof WP_Post ) {
					$value = wpautop( $post->post_content );
				}
				break;
			case 'COMMENTAPPROVED_CONTENT_BEAUTIFIED':
			case 'POSTCONTENT_BEAUTIFIED':
				$content = get_the_content( null, false, $comment->comment_post_ID );
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content ); //phpcs:ignore Generic.PHP.Syntax.PHPSyntax
				$value   = $content;

				break;
			case 'COMMENTAPPROVED_THUMB_ID':
			case 'WPPOSTTYPES_THUMB_ID':
			case 'POSTIMAGEID':
				$value = get_post_thumbnail_id( $comment->comment_post_ID );
				break;
			case 'COMMENTAPPROVED_THUMB_URL':
			case 'WPPOSTTYPES_THUMB_URL':
			case 'POSTIMAGEURL':
				$value = get_the_post_thumbnail_url( $comment->comment_post_ID );
				break;
			case 'POSTAUTHORFN':
				$author_id = get_post_field( 'post_author', $comment->comment_post_ID );
				$value     = get_the_author_meta( 'user_firstname', $author_id );
				break;
			case 'POSTAUTHORLN':
				$author_id = get_post_field( 'post_author', $comment->comment_post_ID );
				$value     = get_the_author_meta( 'user_lastname', $author_id );
				break;
			case 'POSTAUTHORDN':
				$author_id = get_post_field( 'post_author', $comment->comment_post_ID );
				$value     = get_the_author_meta( 'display_name', $author_id );
				break;
			case 'POSTAUTHOREMAIL':
				$author_id = get_post_field( 'post_author', $comment->comment_post_ID );
				$value     = get_the_author_meta( 'user_email', $author_id );
				break;
			case 'POSTAUTHORURL':
				$author_id = get_post_field( 'post_author', $comment->comment_post_ID );
				$value     = get_the_author_meta( 'url', $author_id );
				break;
			case 'comment':
			case 'COMMENTCONTENT':
			case 'COMMENTAPPROVED_COMMENT':
				$value = $comment->comment_content;
				break;
			case 'POSTCOMMENTERNAME':
			case 'COMMENTAUTHOR':
			case 'COMMENTAPPROVED_COMMENTERNAME':
				$value = $comment->comment_author;
				break;
			case 'POSTCOMMENTEREMAIL':
			case 'COMMENTAUTHOREMAIL':
			case 'COMMENTAPPROVED_COMMENTEREMAIL':
				$value = $comment->comment_author_email;
				break;
			case 'POSTCOMMENTERWEBSITE':
			case 'COMMENTAUTHORWEB':
			case 'COMMENTAPPROVED_COMMENTERWEBSITE':
				$value = $comment->comment_author_url;
				break;
			case 'COMMENTPARENT':
				$value = get_comment_link( $comment->comment_parent );
				break;
			case 'POSTCOMMENTDATE':
				$value = sprintf(
				/* translators: 1: Comment date, 2: Comment time. */
					__( '%1$s at %2$s' ),
					/* translators: Publish box date format, see https://www.php.net/manual/datetime.format.php */
					date_i18n( _x( 'M j, Y', 'publish box date format' ), strtotime( $comment->comment_date ) ),
					/* translators: Publish box time format, see https://www.php.net/manual/datetime.format.php */
					date_i18n( _x( 'H:i', 'publish box time format' ), strtotime( $comment->comment_date ) )
				);
				break;
			case 'POSTCOMMENTURL':
				$value = get_comment_link( $comment );
				break;
			case 'POSTCOMMENTSTATUS':
				$value = ( $comment->comment_approved === 1 ) ? 'approved' : 'pending';
				break;
			case 'COMMENTID':
				$value = $comment->comment_ID;
				break;
			case 'COMMENTAPPROVED_ID':
			case 'POSTID':
			case 'WPPOSTTYPES_ID':
			default:
				$value = $comment->comment_post_ID;
				break;
		}

		return $value;
	}

	/**
	 * Parse WP token roles.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function parse_wp_token_roles(
		$value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args
	) {

		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( ! in_array( 'WPROLEOLD', $pieces, true ) && ! in_array( 'WPROLENEW', $pieces, true ) && ! in_array( 'USERCREATEDWITHROLE', $pieces, true ) ) {
			return $value;
		}

		$meta_key       = join( ':', $pieces );
		$trigger_id     = absint( $pieces[0] );
		$trigger_log_id = $replace_args['trigger_log_id'];
		$user_id        = $replace_args['user_id'];
		$value          = $this->get_trigger_log_meta_value( $meta_key, $trigger_id, $trigger_log_id, $user_id );

		return $value;
	}

	/**
	 * Get the trigger log meta value.
	 *
	 * @param      $meta_key
	 * @param      $trigger_id
	 * @param      $trigger_log_id
	 * @param null $user_id
	 *
	 * @return mixed|string
	 */
	public function get_trigger_log_meta_value(
		$meta_key, $trigger_id, $trigger_log_id, $user_id = null
	) {

		if ( empty( $meta_key ) || empty( $trigger_id ) || empty( $trigger_log_id ) ) {
			return '';
		}

		global $wpdb;

		$qry = $wpdb->prepare(
			"SELECT meta_value
			FROM {$wpdb->prefix}uap_trigger_log_meta
			WHERE 1 = 1
			AND user_id = %d
			AND meta_key = %s
			AND automator_trigger_id = %d
			AND automator_trigger_log_id = %d
			LIMIT 0,1",
			$user_id,
			$meta_key,
			$trigger_id,
			$trigger_log_id
		);

		$meta_value = $wpdb->get_var( $qry );

		if ( '0' === $meta_value ) {
			return $meta_value;
		}

		if ( ! empty( $meta_value ) ) {
			return maybe_unserialize( $meta_value );
		}

		return '';

	}

	/**
	 * Parse WP user fields tokens.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function parse_wp_user_fields_tokens(
		$value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args
	) {

		if ( empty( $pieces ) ) {
			return $value;
		}
		if ( in_array( 'WPUSERPROFILEUPDATED', $pieces, true ) || in_array( 'WPUSERUPDATEDMETA', $pieces, true ) || in_array( 'WPUSERMETASPECIFCVAL', $pieces, true ) ) {

			$trigger_id     = absint( $pieces[0] );
			$to_replace     = $pieces[2];
			$trigger_log_id = $replace_args['trigger_log_id'];
			$user_id        = $replace_args['user_id'];
			$meta_key       = $trigger_id . ':' . $pieces[1] . ':' . $pieces[2];
			$meta_value     = $this->get_trigger_log_meta_value( $meta_key, $trigger_id, $trigger_log_id, $user_id );

			if ( '0' === $meta_value ) {
				return $meta_value;
			}

			if ( ! empty( $meta_value ) ) {
				$value = maybe_unserialize( $meta_value );
				if ( is_array( $value ) ) {
					$value = join( ',', $value );
				}
			}
		}

		return $value;
	}

	/**
	 * WP user meta possible tokens.
	 *
	 * @param array $tokens
	 * @param array $args
	 *
	 * @return array
	 */
	public function wp_usermeta_possible_tokens(
		$tokens = array(), $args = array()
	) {
		if ( ! automator_pro_do_identify_tokens() ) {
			return $tokens;
		}

		$trigger_meta      = $args['meta'];
		$trigger_meta_code = $args['triggers_meta']['code'];

		if ( 'WPUSERUPDATEDMETA' === $trigger_meta_code ) {
			$fields = array(
				array(
					'tokenId'         => 'UMETAVALUE',
					'tokenName'       => __( 'Meta value', 'uncanny-automator-pro' ),
					'tokenType'       => 'text',
					'tokenIdentifier' => $trigger_meta_code,
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
	public function parse_wp_status_tokens(
		$value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args
	) {
		$tokens = array(
			'POSTSTATUSUPDATED',
			'SPECIFICPOSTTYPESTATUSUPDATED',
			'POSTTYPE',
		);
		if ( empty( $pieces ) ) {
			return $value;
		}
		if ( empty( $trigger_data ) ) {
			return $value;
		}
		if ( ! isset( $pieces[2] ) ) {
			return $value;
		}
		$token = (string) $pieces[2];
		if ( empty( $token ) ) {
			return $value;
		}

		if ( ! in_array( $token, $tokens, false ) ) {
			return $value;
		}

		foreach ( $trigger_data as $trigger ) {
			$trigger_id     = absint( $trigger['ID'] );
			$trigger_log_id = absint( $replace_args['trigger_log_id'] );
			$run_number     = absint( $replace_args['run_number'] );
			$parse_tokens   = array(
				'trigger_id'     => $trigger_id,
				'trigger_log_id' => $trigger_log_id,
				'user_id'        => $user_id,
				'run_number'     => $run_number,
			);
			$entry          = '';
			switch ( $token ) {
				default:
					$meta_key = join( ':', $pieces );
					$entry    = Automator()->db->trigger->get_token_meta( $meta_key, $parse_tokens );
					break;
			}

			if ( ! empty( $entry ) && is_array( $entry ) ) {
				$value = join( ', ', $entry );
			} elseif ( ! empty( $entry ) ) {
				$value = $entry;
			}
		}

		return $value;
	}

	/**
	 * @param $tokens
	 * @param $args
	 *
	 * @return array|array[]
	 */
	public function wp_users_possible_tokens( $tokens = array(), $args = array() ) {

		$trigger_code = $args['triggers_meta']['code'];

		$fields = array(
			array(
				'tokenId'         => 'USER_ID',
				'tokenName'       => __( 'User ID', 'uncanny-automator-pro' ),
				'tokenType'       => 'int',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'USER_LOGIN',
				'tokenName'       => __( 'User username', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'USER_DISPLAY_NAME',
				'tokenName'       => __( 'User display name', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'USER_EMAIL',
				'tokenName'       => __( 'User email', 'uncanny-automator-pro' ),
				'tokenType'       => 'email',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'USER_REGISTERED_DATE',
				'tokenName'       => __( 'User registered', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
			array(
				'tokenId'         => 'USER_ROLES',
				'tokenName'       => __( 'User role', 'uncanny-automator-pro' ),
				'tokenType'       => 'text',
				'tokenIdentifier' => $trigger_code,
			),
		);

		$tokens = array_merge( $tokens, $fields );

		return $tokens;

	}

	/**
	 * Parse WP USERS Tokens.
	 *
	 * @param $value
	 * @param $pieces
	 * @param $recipe_id
	 * @param $trigger_data
	 * @param $user_id
	 * @param $replace_args
	 *
	 * @return mixed
	 */
	public function parse_wp_users_tokens( $value, $pieces, $recipe_id, $trigger_data, $user_id, $replace_args ) {

		if ( empty( $pieces ) ) {
			return $value;
		}

		if ( ! in_array( 'WPDELETEUSER', $pieces, true ) ) {
			return $value;
		}

		$to_replace = $pieces[2];
		$user_obj   = maybe_unserialize( Automator()->db->token->get( 'user_obj', $replace_args ) );

		switch ( $to_replace ) {
			case 'USER_LOGIN':
				$value = $user_obj->user_login;
				break;
			case 'USER_DISPLAY_NAME':
				$value = $user_obj->display_name;
				break;
			case 'USER_EMAIL':
				$value = $user_obj->user_email;
				break;
			case 'USER_REGISTERED_DATE':
				$value = $user_obj->user_registered;
				break;
			case 'USER_ROLES':
				$roles = maybe_unserialize( Automator()->db->token->get( 'USER_ROLES', $replace_args ) );
				$value = join( ', ', $roles );
				break;
			case 'USER_ID':
				$value = $user_obj->ID;
				break;
		}

		return $value;
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

		if ( 'WPDELETEUSER' === $args['entry_args']['code'] ) {
			$user_obj          = $args['trigger_args'][2];
			$trigger_log_entry = $args['trigger_entry'];
			if ( ! empty( $user_obj ) ) {
				Automator()->db->token->save( 'user_obj', maybe_serialize( $user_obj->data ), $trigger_log_entry );
				Automator()->db->token->save( 'USER_ROLES', maybe_serialize( $user_obj->roles ), $trigger_log_entry );
			}
		}

		if ( 'ANON_WPPOSTSTATUS' === $args['entry_args']['code'] ) {
			list( $new_status, $old_status, $post ) = $args['trigger_args'];
			$trigger_log_entry                      = $args['trigger_entry'];
			if ( ! empty( $post ) ) {
				Automator()->db->token->save( 'post_id', $post->ID, $trigger_log_entry );
			}
		}

		if ( 'WP_USER_VIEWS_ARCHIVE_POST' === $args['entry_args']['code'] || 'WP_ANON_VIEWS_ARCHIVE_POST' === $args['entry_args']['code'] ) {
			$trigger_log_entry = $args['trigger_entry'];
			global $post;
			$taxonomy_object = get_queried_object();
			if ( ! empty( $post ) && isset( $taxonomy_object ) ) {
				Automator()->db->token->save( 'post_id', $post->ID, $trigger_log_entry );
				Automator()->db->token->save( 'WPTAXONOMIES', $taxonomy_object->taxonomy, $trigger_log_entry );
				Automator()->db->token->save( 'SPECIFICTAXONOMY', $taxonomy_object->name, $trigger_log_entry );
				Automator()->db->token->save( 'archive_url', get_term_link( $taxonomy_object->term_id, $taxonomy_object->taxonomy ), $trigger_log_entry );
				Automator()->db->token->save( 'term_post_count', $taxonomy_object->count, $trigger_log_entry );
				Automator()->db->token->save( 'term_id', $taxonomy_object->term_id, $trigger_log_entry );
			}
		}

	}

}

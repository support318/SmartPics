<?php

namespace Uncanny_Automator_Pro\Loops\Token\Common;

use Uncanny_Automator_Pro\Loops\Token\Text_Parseable;
use Uncanny_Automator_Pro\Loops_Process_Registry;

/**
 * Posts tokens
 *
 * @since 5.3
 *
 * @package Uncanny_Automator_Pro\Loops\Token
 */
final class Parser extends Text_Parseable {

	/**
	 * The regexp pattern.
	 *
	 * @var string $pattern
	 */
	protected $pattern = '/{{TOKEN_EXTENDED:LOOP_TOKEN:\d+:COMMON:[^}]+}}/';

	/**
	 * @param $entity_id
	 * @param $extracted_token
	 *
	 * @return int|string|null
	 */
	public function parse( $entity_id, $extracted_token, $args ) {

		$process_id = $args['loop']['loop_item']['filter_id'];

		$process_registry = Loops_Process_Registry::get_instance();
		$process_instance = $process_registry->get_object( $process_id );

		return $process_instance->get_current_batch_index();
	}
}

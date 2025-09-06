<?php
namespace Uncanny_Automator_Pro\Loops\Loop;

use Exception;
use Uncanny_Automator_Pro\Loops\Loop\Exception\Loops_Exception;

/**
 * Factory class for creating loop entities.
 *
 * @since 6.0
 */
class Entity_Factory {

	/**
	 * @var string
	 */
	const TYPE_POSTS = 'posts';

	/**
	 * @var string
	 */
	const TYPE_USERS = 'users';

	/**
	 * @var string
	 */
	const TYPE_TOKEN = 'token';

	/**
	 * Creates an instance of the specified loop type.
	 *
	 * @param string $loop_type Required. The type of loop entity to create. Valid values are 'posts', 'users', and 'data'.
	 * @param int[]|array{array{mixed}} $entities Required. The data to be used by the loop entity.
	 *
	 * @return Entity_Loopable An instance of the specified loop entity.
	 *
	 * @throws Loops_Exception If the loop type is invalid.
	 */
	public function make( $loop_type, $entities ) {

		// Populate default class map.
		$class_map = array(
			self::TYPE_POSTS => Posts::class,
			self::TYPE_USERS => Users::class,
			self::TYPE_TOKEN => Data::class,
		);

		// Allow plugins to modify the class map dynamically.
		$class_map = apply_filters( 'uncanny_automator_pro_entity_factory_items', $class_map, array( $loop_type, $entities ) );

		// Ensure the loop type exists in the class map.
		if ( ! array_key_exists( $loop_type, $class_map ) ) {
			throw new Loops_Exception( 'Invalid loop type detected', 400 );
		}

		// Get the class name from the class map.
		$class = $class_map[ $loop_type ];

		$entities = new $class( $entities );

		if ( ! ( $entities instanceof Entity_Loopable ) ) {
			throw new Loops_Exception( 'Invalid loop entities. Loop entities must be loopable token.', 400 );
		}

		// Return a new instance of the specified loop type.
		return $entities;
	}
}


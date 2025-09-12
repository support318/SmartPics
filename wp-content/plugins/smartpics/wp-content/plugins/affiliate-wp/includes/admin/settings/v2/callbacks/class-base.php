<?php
/**
 * Abstract class to register callbacks.
 *
 * @package    AffiliateWP
 * @subpackage AffiliateWP\Admin\Settings\V2\Callbacks
 * @copyright  Copyright (c) 2024, Awesome Motive, Inc
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.26.1
 * @author     Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

namespace AffiliateWP\Admin\Settings\V2\Callbacks;

/**
 * Callback abstract class.
 *
 * @since 2.26.1
 */
abstract class Base {
	/**
	 * Instance of the class
	 *
	 * @since 2.26.1
	 * @var static|null
	 */
	private static ?self $instance = null;

	/**
	 * All classes must return an unique name.
	 *
	 * @since 2.26.1
	 * @return string The unique name.
	 */
	abstract public function get_name() : string;

	/**
	 * All classes must register a callback method to render the setting.
	 *
	 * @since 2.26.1
	 * @param array $args The arguments passed to the callback on settings register.
	 * @see \Affiliate_WP_Settings::register_settings
	 * @return void
	 */
	abstract public function render( array $args ) : void;

	/**
	 * Gets the instance of the class
	 *
	 * @since 2.26.1
	 *
	 * @return static The instance of this class.
	 */
	public static function get_instance() : self {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}

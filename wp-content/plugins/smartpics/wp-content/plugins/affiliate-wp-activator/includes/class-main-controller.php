<?php

namespace Tomi\AffiliateWpActivator;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/traits/trait-affiliate-wp-activator.php';

class Main_Controller {

	/* here optional traits */
	use Affiliate_Wp_Activator_Traits;

	public static $slug;
	public static $plugin;

	protected static $instance;

	public function __construct() {

		self::$plugin = plugin_basename( PN_B40560_PLUGIN );
		self::$slug   = dirname( plugin_basename( PN_B40560_PLUGIN ) );

		$this->init_hooks();
	}

	public static function init(): Main_Controller {

		is_null( self::$instance ) and self::$instance = new self;

		return self::$instance;
	}
}


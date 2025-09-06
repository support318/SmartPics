<?php
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator_Pro\Integrations\M4IS\M4IS_Integration' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator\Integrations\M4IS\M4IS_HELPERS' ) ) {
	return;
}

new Uncanny_Automator_Pro\Integrations\M4IS\M4IS_Integration();

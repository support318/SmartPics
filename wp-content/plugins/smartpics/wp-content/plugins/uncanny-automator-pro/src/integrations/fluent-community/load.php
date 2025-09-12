<?php

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator_Pro\Integrations\Fluent_Community\Fluent_Community_Integration' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator\Integrations\Fluent_Community\Fluent_Community_Helpers' ) ) {
	return;
}

new Uncanny_Automator_Pro\Integrations\Fluent_Community\Fluent_Community_Integration();

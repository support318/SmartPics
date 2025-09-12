<?php

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator_Pro\Integrations\Thrive_Apprentice\Thrive_Apprentice_Integration' ) ) {
	return;
}

if ( ! class_exists( 'Uncanny_Automator\Integrations\Thrive_Apprentice\Thrive_Apprentice_Helpers' ) ) {
	return;
}

new Uncanny_Automator_Pro\Integrations\Thrive_Apprentice\Thrive_Apprentice_Integration();

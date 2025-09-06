<?php

namespace Uncanny_Automator_Pro\Integrations\Formatter;

class Formatter_Integration extends \Uncanny_Automator\Integration {

	protected function setup() {

		$this->set_integration( 'FORMATTER' );
		$this->set_name( 'Formatter' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/formatter-icon.svg' );

		$this->load();
	}

	protected function load() {
		new Date_Formatter();
		new Text_Formatter();
		new Number_Formatter();
		new Replace();
		new Extract_First_Word();
	}

}

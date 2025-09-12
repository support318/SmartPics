<?php

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class Test_Phone_Number extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		// Add any setup code here
	}

	public function test_us_number_formatting() {
		$this->assertEquals( '+12125551234', wpf_phone_number_to_e164( '(212) 555-1234', 'US' ) );
		$this->assertEquals( '+12125551234', wpf_phone_number_to_e164( '212-555-1234', 'US' ) );
		$this->assertEquals( '+12125551234', wpf_phone_number_to_e164( '2125551234', 'US' ) );
	}

	public function test_uk_number_formatting() {
		$this->assertEquals( '+442071234567', wpf_phone_number_to_e164( '020 7123 4567', 'GB' ) );
		$this->assertEquals( '+442071234567', wpf_phone_number_to_e164( '(020) 7123 4567', 'GB' ) );
	}

	public function test_australian_number_formatting() {
		$this->assertEquals( '+61412345678', wpf_phone_number_to_e164( '0412 345 678', 'AU' ) );
		$this->assertEquals( '+61405420037', wpf_phone_number_to_e164( '0405420037', 'AU' ) );
	}

	public function test_already_formatted_number() {
		$this->assertEquals( '+12125551234', wpf_phone_number_to_e164( '+12125551234', 'US' ) );
	}

	public function test_invalid_country() {
		$this->assertEquals( '+2125551234', wpf_phone_number_to_e164( '2125551234', 'XX' ) );
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Add any cleanup code here
	}
}

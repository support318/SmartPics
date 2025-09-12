<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

namespace Uncanny_Automator_Pro;

/**
 * Class DateTime_Helpers
 *
 * @package Uncanny_Automator
 */
class DateTime_Helpers {

	public static function date_object( $input ) {

		$timezone = wp_timezone();

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		if ( is_numeric( $input ) ) {
			$date = \DateTime::createFromFormat( 'U', $input );
			$date->setTimezone( $timezone );
		} else {

			$date = \DateTime::createFromFormat( $date_format . ' ' . $time_format, $input, $timezone );

			if ( false === $date ) {
				$date = new \DateTime( $input, $timezone );
			}
		}

		if ( ! $date instanceof \DateTime ) {
			throw new \Exception( __( 'Unable to parse the date/time', 'uncanny-automator-pro' ) );
		}

		return $date;
	}

	public static function get_date( $input ) {
		$date = self::date_object( $input );
		$date->setTime( 12, 0, 0 );
		return $date;
	}

	public static function get_time( $input ) {
		$date = self::date_object( $input );
		$date->setDate( 0, 0, 0 );
		return $date;
	}

	public static function get_weekday( $input ) {

		$format = 'w'; // 0 (for Sunday) through 6 (for Saturday)

		$date = self::date_object( $input );
		$day  = (int) $date->format( $format );

		return self::shift_weekday( $day );
	}

	public static function shift_weekday( $day ) {

		$start_of_week = (int) get_option( 'start_of_week', '1' );

		if ( $day < $start_of_week ) {
			$day += 7;
		}

		$day -= $start_of_week;

		return $day;
	}

	public static function get_month( $input ) {

		if ( is_numeric( $input ) ) {
			$intval = intval( $input );
			if ( $intval > 0 && $intval < 12 ) {
				return $intval;
			}
		}

		$date  = self::date_object( $input );
		$month = (int) $date->format( 'n' );
		return $month;
	}

	public static function get_month_day( $input ) {
		$date = self::date_object( $input );
		$day  = (int) $date->format( 'j' );
		return $day;
	}
}

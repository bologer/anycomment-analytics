<?php

/**
 * Class BaseHelper
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class BaseHelper {
	/**
	 * Normalize provided date to time.
	 *
	 * When provided date is already UNIX timestamp, it will be returned as it is.
	 *
	 * @param mixed $date
	 *
	 * @return false|int
	 */
	public static function normalize_to_time ( $date ) {
		if ( is_numeric( $date ) ) {
			return $date;
		}

		return strtotime( $date );
	}

	/**
	 * Normalizes passed date into proper MySQL datetime format, e.g. 2018-01-29 00:00:00.
	 *
	 * @param mixed $date UNIX timestamp or date.
	 * @param string $format Date format.
	 *
	 * @return int|string Properly formatted date or what was passed in case it cannot be parsed properly.
	 */
	public static function normalize_to_datetime ( $date, $format = 'Y-m-d' ) {
		$timestamp = $date;

		if ( ! is_numeric( $timestamp ) ) {
			$timestamp = strtotime( $date );

			if ( - 1 === $timestamp ) {
				return $date;
			}
		}

		return date( $format, $timestamp );
	}
}

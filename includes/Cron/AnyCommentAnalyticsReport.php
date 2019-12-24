<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Class AnyCommentAnalyticsReport is used to schedule sending the report to specified emails.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class AnyCommentAnalyticsReport {

	const INTERVAL_FORMAT = 'anycomment_analytics_interval_%s';

	/**
	 * AnyCommentEmailCron constructor.
	 */
	public function __construct () {
		$this->init();
	}

	/**
	 * Init class.
	 */
	private function init () {

		add_filter( 'cron_schedules', [ $this, 'add_intervals' ] );

		add_action( 'init', [ $this, 'init_schedule' ] );

		add_action( 'anycomment_analytics_queue_report', [ $this, 'create_report' ] );
	}

	/**
	 * Init schedule event.l
	 */
	public function init_schedule () {
		if ( ! wp_next_scheduled( 'anycomment_analytics_queue_report' ) ) {
			wp_schedule_event( time(), sprintf( self::INTERVAL_FORMAT, ReportSettings::getPeriod() ), 'anycomment_analytics_queue_report' );
		}
	}

	/**
	 * Add new intervals.
	 *
	 * @param array $schedules List of available schedules.
	 *
	 * @return mixed
	 */
	public function add_intervals ( $schedules ) {

		$one_day = 24 * 60 * 60;

		$intervals = [
			\ReportSettings::REPORT_PERIOD_DAILY   => [
				'interval' => $one_day,
				'display'  => esc_html__( 'Every day', 'anycomment-analytics' ),
			],
			\ReportSettings::REPORT_PERIOD_WEEKLY  => [
				'interval' => 7 * $one_day,
				'display'  => esc_html__( 'Every week', 'anycomment-analytics' ),
			],
			\ReportSettings::REPORT_PERIOD_MONTHLY => [
				'interval' => 30 * $one_day,
				'display'  => esc_html__( 'Every month', 'anycomment-analytics' ),
			],
		];

		$definedPeriod = \ReportSettings::getPeriod();

		if ( isset( $intervals[ $definedPeriod ] ) ) {
			$schedules[ sprintf( self::INTERVAL_FORMAT, $definedPeriod ) ] = $intervals[ $definedPeriod ];
		}

		return $schedules;
	}

	/**
	 * Cron tab to create report and add to email queue.
	 *
	 * @return bool
	 */
	public function create_report () {

		$emails = \ReportSettings::getRecipientEmails( true );

		$period = \ReportSettings::getPeriod();
		$format = 'd.m.Y';

		if ( $period === \ReportSettings::REPORT_PERIOD_DAILY ) {
			$from_date = date( $format, strtotime( '-1 day' ) );
		} elseif ( $period === \ReportSettings::REPORT_PERIOD_WEEKLY ) {
			$from_date = date( $format, strtotime( '-7 days' ) );
		} elseif ( $period === \ReportSettings::REPORT_PERIOD_MONTHLY ) {
			$from_date = date( $format, strtotime( '-1 month' ) );
		} else {
			return false;
		}

		$to_date = date( $format, time() );

		foreach ( $emails as $email ) {
			$model = new \AdminNotification( $email, $from_date, $to_date );

			$model->add_to_queue();
		}

		return true;
	}
}

<?php

/**
 * Class AnyCommentAnalyticsAjaxReport used to attach AJAX hooks for sending report by AJAX.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class AnyCommentAnalyticsAjaxReport {

	/**
	 * AnyCommentAnalyticsAjaxReport constructor.
	 */
	public function __construct () {
		$this->init();
	}

	/**
	 * Object init.
	 */
	public function init () {
		add_action( 'wp_ajax_anycomment_analytics_send_report', [ $this, 'send_report' ] );
	}

	/**
	 * Create report to be send.
	 *
	 * @see AnyCommentAnalyticsReport::create_report() for further information.
	 */
	public function send_report () {

		try {
			$report_created = ( new AnyCommentAnalyticsReport() )->create_report();

			if ( $report_created ) {
				wp_send_json_success( [
					'message' => __( 'Report added to queue, will be send soon.', 'anycomment-analytics' ),
				] );
			}
		} catch ( \Exception $exception ) {
		}

		wp_send_json_error( [
			'message' => __( 'Failed to send report. Try again later', 'anycomment-analytics' ),
		] );
	}
}
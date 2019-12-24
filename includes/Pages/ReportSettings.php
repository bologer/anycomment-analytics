<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use AnyComment\Options\AnyCommentOptionManager;

/**
 * AC_AdminSettingPage helps to process generic plugin settings.
 */
class ReportSettings extends AnyCommentOptionManager {

	const REPORT_EMAIL_RECIPIENTS = 'report_email_to';

	const REPORT_PERIOD = 'report_period';
	const REPORT_PERIOD_DAILY = 'daily';
	const REPORT_PERIOD_WEEKLY = 'weekly';
	const REPORT_PERIOD_MONTHLY = 'monthly';

	/**
	 * @inheritdoc
	 */
	protected $option_group = 'anycomment-analytics-report-group';
	/**
	 * @inheritdoc
	 */
	protected $option_name = 'anycomment-analytics-report';

	/**
	 * @inheritdoc
	 */
	protected $field_options = [
		'wrapper' => '<div class="cell anycomment-form-wrapper__field">{content}</div>',
	];

	/**
	 * @inheritdoc
	 */
	protected $section_options = [
		'wrapper' => '<div class="grid-x anycomment-form-wrapper anycomment-tabs__container__tab" id="{id}">{content}</div>',
	];

	/**
	 * AnyCommentAdminPages constructor.
	 *
	 * @param bool $init if required to init the modle.
	 */
	public function __construct ( $init = true ) {
		parent::__construct();
		if ( $init ) {
			$this->init_settings();
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_settings () {

		$form = $this->form();

		$form->add_section(
			$this->section_builder()
			     ->set_id( 'report' )
			     ->set_title( __( 'Report', "anycomment-analytics" ) )
			     ->set_wrapper( '<div class="grid-x anycomment-form-wrapper anycomment-tabs__container__tab current" id="{id}">{content}</div>' )
			     ->set_fields( [

				     $this->field_builder()
				          ->text()
				          ->set_id( self::REPORT_EMAIL_RECIPIENTS )
				          ->set_title( __( 'Email recipients', 'anycomment-analytics' ) )
				          ->set_description( sprintf( __( 'By default we send report to "Email Address" listed in "<a href="%s">General</a>" settings. You may specify different email or list of them separated by comma. e.g. "johndoe@gmail.com,alisa@gmail.com" to send email to two recipients.', "anycomment-analytics" ), '/wp-admin/options-general.php' ) ),


				     $this->field_builder()
				          ->set_id( self::REPORT_PERIOD )
				          ->select()
				          ->set_title( __( 'Report period', "anycomment-analytics" ) )
				          ->set_args( [
					          'options' => [
						          self::REPORT_PERIOD_DAILY   => __( 'Daily', 'anycomment-analytics' ),
						          self::REPORT_PERIOD_WEEKLY  => __( 'Weekly', 'anycomment-analytics' ),
						          self::REPORT_PERIOD_MONTHLY => __( 'Monthly', 'anycomment-analytics' ),
					          ],
				          ] )
				          ->set_description( esc_html( __( 'Report interval. Define what is good period to receive a report.', "anycomment-analytics" ) ) ),


			     ] )
		);
	}

	/**
	 * Get recipient email(s) as string or array list.
	 *
	 * @param bool $asArray If required to return list as array.
	 *
	 * @return string|array
	 */
	public static function getRecipientEmails ( $asArray = false ) {
		$value = static::instance()->get_db_option( self::REPORT_EMAIL_RECIPIENTS );

		if ( empty( $value ) ) {
			return null;
		}

		if ( $asArray ) {
			return array_map( 'trim', explode( ',', $value ) );
		}

		return $value;
	}

	/**
	 * Get specified period.
	 *
	 * @return string If string, one of the constant values defined as REPORT_PERIOD_*.
	 */
	public static function getPeriod () {
		$value = static::instance()->get_db_option( self::REPORT_PERIOD );

		if ( $value === self::REPORT_PERIOD_DAILY ||
		     $value === self::REPORT_PERIOD_WEEKLY ||
		     $value === self::REPORT_PERIOD_MONTHLY ) {
			return $value;
		}

		return self::REPORT_PERIOD_DAILY;
	}
}

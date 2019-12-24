<?php

/**
 * Class AdminNotification is used generate report and add it to the AnyComment's email queue, so it can be sent
 * to an administrator.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class AdminNotification implements EmailNotificationInterface {

	/**
	 * @var null|string|array Email where to send report.
	 */
	private $email;

	/**
	 * @var string|int UNIX epoch or datetime.
	 */
	private $date_from;

	/**
	 * @var string|int UNIX epoch or datetime.
	 */
	private $date_to;

	/**
	 * @var string|null
	 */
	private $prepared_content;


	public function __construct ( $email, $from_date, $to_date ) {
		$this->email     = $email;
		$this->date_from = $from_date;
		$this->date_to   = $to_date;
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare () {

		if ( $this->prepared_content !== null ) {
			return $this->prepared_content;
		}

		$blog_name     = get_option( 'blogname' );
		$blog_url      = get_option( 'siteurl' );
		$blog_url_html = sprintf( '<a href="%s">%s</a>', $blog_url, $blog_name );

		$date_format = get_option( 'date_format' );
		$time_format = get_option( 'time_format' );

		$datetime_format = $date_format . ", " . $time_format;

		$search = [
			'{logoSrc}',
			'{helloParagraph}',
			'{afterHelloParagraph}',

			'{usersSummaryIcon}',
			'{usersSummaryParagraph}',

			'{popularUserIcon}',
			'{popularUserParagraph}',

			'{commentsSummaryIcon}',
			'{commentsSummaryParagraph}',

			'{activeModeratorIcon}',
			'{activeModeratorParagraph}',

			'{popularPostIcon}',
			'{popularPostParagraph}',

			'{otherSummaryParagraph}',
		];

		$ds      = DIRECTORY_SEPARATOR;
		$img_url = AnyCommentAnalytics::instance()->plugin_url() . $ds . 'assets' . $ds . 'img' . $ds . 'emails' . $ds;

		$popular_user = $this->get_popular_user();

		$replacement = [

			$img_url . 'logo.jpg',

			__( 'Hello,', 'anycomment-analytics' ),
			sprintf(
				__( 'This email contains analytical summary for %s about comments, active users, including moderators and administrators for period between %s and %s.', 'anycomment-analytics' ),
				$blog_url_html,
				date( $datetime_format, BaseHelper::normalize_to_time( $this->date_from ) ),
				date( $datetime_format, BaseHelper::normalize_to_time( $this->date_to ) )
			),

			$img_url . 'person.png',
			$this->get_users_summary_paragraph(),

			$popular_user['avatar'],
			$popular_user['message'],

			$img_url . 'comment.png',
			$this->get_comments_summary(),

			$img_url . 'write.png',
			$this->get_active_moderator_summary(),

			$img_url . 'star.png',
			$this->get_popular_post(),

			$this->get_other_summary(),
		];

		$template = \AnyComment\Helpers\AnyCommentTemplate::render( ANYCOMMENT_ANALYTICS_ABSPATH . '/templates/emails/report.html' );

		$this->prepared_content = \AnyComment\Models\AnyCommentEmailQueue::prepare_email_template( $template, $search, $replacement );

		return $this->prepared_content;
	}


	/**
	 * Get other summary, such as number of emails sent, likes, post ratings, subscriptions, files, etc.
	 *
	 * @return string
	 */
	public function get_other_summary () {

		global $wpdb;

		$items = [];

		$datetime_from = BaseHelper::normalize_to_datetime( $this->date_from );
		$datetime_to   = BaseHelper::normalize_to_datetime( $this->date_to );

		$dateunix_from = BaseHelper::normalize_to_time( $this->date_from );
		$dateunix_to   = BaseHelper::normalize_to_time( $this->date_to );

		// Emails
		$email_queue_table = \AnyComment\Models\AnyCommentEmailQueue::get_table_name();
		$emails_sql        = "SELECT COUNT(*) FROM `{$email_queue_table}` WHERE created_at BETWEEN %s AND %s";
		$emails_count      = $wpdb->get_var( $wpdb->prepare( $emails_sql, $datetime_from, $datetime_to ) );
		$items[]           = sprintf( __( '%s emails sent', 'anycomment-analytics' ), $emails_count );

		// Likes
		$likes_table = \AnyComment\Models\AnyCommentLikes::get_table_name();
		$likes_sql   = "SELECT COUNT(*) FROM `{$likes_table}` WHERE liked_at BETWEEN %s AND %s";
		$likes_count = $wpdb->get_var( $wpdb->prepare( $likes_sql, $datetime_from, $datetime_to ) );
		$items[]     = sprintf( __( '%s new likes', 'anycomment-analytics' ), $likes_count );

		// Post ratings
		$post_rating_table = \AnyComment\Models\AnyCommentRating::get_table_name();
		$post_rating_sql   = "SELECT COUNT(*) FROM `{$post_rating_table}` WHERE created_at BETWEEN %s AND %s";
		$post_rating_count = $wpdb->get_var( $wpdb->prepare( $post_rating_sql, $dateunix_from, $dateunix_to ) );
		$items[]           = sprintf( __( '%s new post ratings', 'anycomment-analytics' ), $post_rating_count );

		// Subscriptions
		$subscriptions_table = \AnyComment\Models\AnyCommentSubscriptions::get_table_name();
		$subscriptions_sql   = "SELECT COUNT(*) FROM `{$subscriptions_table}` WHERE created_at BETWEEN %s AND %s";
		$subscriptions_count = $wpdb->get_var( $wpdb->prepare( $subscriptions_sql, $dateunix_from, $dateunix_to ) );
		$items[]             = sprintf( __( '%s new subscriptions', 'anycomment-analytics' ), $subscriptions_count );

		// Files
		$files_table = \AnyComment\Models\AnyCommentUploadedFiles::get_table_name();
		$files_sql   = "SELECT COUNT(*) FROM `{$files_table}` WHERE created_at BETWEEN %s AND %s";
		$files_count = $wpdb->get_var( $wpdb->prepare( $files_sql, $dateunix_from, $dateunix_to ) );
		$items[]     = sprintf( __( '%s new files uploaded', 'anycomment-analytics' ), $files_count );

		$result = __( 'Also, there were:', 'anycomment-analytics' );

		foreach ( $items as $item ) {
			$result .= '<br>- ' . $item;
		}

		return $result;
	}

	/**
	 * Get popular post by number of comments.
	 *
	 * @return string
	 */
	public function get_popular_post () {
		global $wpdb;

		$anycomment_rating_table = \AnyComment\Models\AnyCommentRating::get_table_name();

		$sql = "SELECT p.post_title AS title, ar.average_rating FROM `{$wpdb->posts}` p
LEFT JOIN (SELECT post_ID, AVG(rating) as average_rating FROM `{$anycomment_rating_table}` GROUP BY post_ID) ar ON ar.post_ID = p.ID
WHERE ar.average_rating IS NOT NULL AND p.post_date BETWEEN %s AND %s
LIMIT 1";

		$date_from = BaseHelper::normalize_to_datetime( $this->date_from );
		$date_to   = BaseHelper::normalize_to_datetime( $this->date_to );

		$popular_post = $wpdb->get_row( $wpdb->prepare( $sql, $date_from, $date_to ), ARRAY_A );


		if ( empty( $popular_post ) ) {
			return __( 'No popular post found for such period.', 'anycomment-analytics' );
		}

		return sprintf( __( '%s is the most popular post based on number of comments.', 'anycomment-analytics' ),
			wptexturize( '"' . $popular_post['title'] . '"' )
		);
	}

	/**
	 * Get most active moderator information.
	 *
	 * @return string
	 */
	public function get_active_moderator_summary () {
		global $wpdb;

		$sql = "SELECT 
	u.display_name, 
    cm.approves AS approve_count  
FROM `{$wpdb->comments}` c
LEFT JOIN `{$wpdb->users}` u ON c.user_id = u.ID 
LEFT JOIN (SELECT meta_value, COUNT(*) AS approves FROM `{$wpdb->commentmeta}` WHERE meta_key = %s GROUP BY meta_value) cm ON cm.meta_value = u.ID
WHERE c.comment_date BETWEEN %s AND %s
GROUP BY c.user_id, approve_count
HAVING cm.approves > 0
LIMIT 1";

		$date_from = BaseHelper::normalize_to_datetime( $this->date_from );
		$date_to   = BaseHelper::normalize_to_datetime( $this->date_to );

		$active_moderator_query = $wpdb->prepare(
			$sql,
			AnyCommentAnalyticComment::MODERATED_META_KEY,
			$date_from,
			$date_to
		);

		$active_moderator = $wpdb->get_row( $active_moderator_query, ARRAY_A );

		if ( empty( $active_moderator ) ) {
			return __( 'No active moderator was found for such period.', 'anycomment-analytics' );
		}

		$comments_translation = sprintf( _nx(
			'%s comment',
			'%s comments',
			$active_moderator['approve_count'],
			'Number of approved comments',
			'anycomment-analytics'
		), number_format_i18n( $active_moderator['approve_count'] ) );

		return sprintf(
			__( '%s approved %s and considered as the most active.', 'anycomment-analytics' ),
			'<strong>' . $active_moderator['display_name'] . '</strong>',
			$comments_translation
		);
	}

	/**
	 * Get users summary such as number or users registered and what socials were used.
	 *
	 * @return string
	 */
	public function get_users_summary_paragraph () {
		global $wpdb;

		$date_from = BaseHelper::normalize_to_datetime( $this->date_from );
		$date_to   = BaseHelper::normalize_to_datetime( $this->date_to );

		$registered_users_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM `{$wpdb->users}` WHERE `user_registered` BETWEEN %s AND %s",
			$date_from,
			$date_to
		);

		$registered_users_count = $wpdb->get_var( $registered_users_query );

		$result = sprintf(
			_nx(
				'<strong>%s user</strong> was registered.',
				'<strong>%s users</strong> were registered.',
				$registered_users_count,
				'Number of registered user in email template',
				'anycomment-analytics'
			),
			number_format_i18n( $registered_users_count )
		);


		// No users, so should quit, as further queries would not help
		if ( empty( $result ) ) {
			return $result;
		}

		$social_usage_query = $wpdb->prepare(
			"SELECT 
	COUNT(um.meta_value) AS user_count, 
	um.meta_value AS social_name 
FROM `{$wpdb->users}` u 
LEFT JOIN `{$wpdb->usermeta}` um ON u.ID = um.user_id AND um.meta_key = 'anycomment_social'
WHERE u.`user_registered` BETWEEN %s AND %s 
GROUP BY social_name
HAVING user_count > 0",
			$date_from,
			$date_to
		);

		$social_usage_result = $wpdb->get_results( $social_usage_query, ARRAY_A );

		if ( ! empty( $social_usage_result ) ) {
			$result .= ' ' . __( 'From this:', 'anycomment-analytics' ) . '<br>';

			foreach ( $social_usage_result as $item ) {

				if ( isset( $item['user_count'] ) && isset( $item['social_name'] ) ) {
					$verbal_name = \AnyComment\Rest\AnyCommentSocialAuth::get_verbal_name( $item['social_name'] );

					if ( ! empty( $verbal_name ) ) {
						$result .= '- ' . sprintf(
								__( '%s from %s', 'anycomment-analytics' ),
								$item['user_count'],
								$verbal_name
							) . '<br>';
					}
				}
			}
		} else {
			$result .= '<br>' . __( 'Unable to get detailed user information.', 'anycomment-analytics' );
		}

		return $result;
	}

	/**
	 * Get the most popular user information.
	 *
	 * @return array has the following keys:
	 * - avatar: URL to user's avatar.
	 * - message: prepared message for email.
	 */
	public function get_popular_user () {

		global $wpdb;

		$response = [ 'avatar' => '', 'message' => '' ];

		if ( ! class_exists( '\AnyComment\Models\AnyCommentLikes' ) ) {
			return $response;
		}

		$anycomment_likes_table = \AnyComment\Models\AnyCommentLikes::get_table_name();

		$query = "SELECT 
	u.display_name AS display_name,
	c.user_id AS user_id,
    COUNT(al.likes_count) AS rating_count,
    COUNT(c.comment_ID) AS comments_count 
FROM `{$wpdb->comments}` c
LEFT JOIN (SELECT user_ID, COUNT(ID) as likes_count FROM `{$anycomment_likes_table}` GROUP BY user_ID) al ON al.user_ID = c.user_id
LEFT JOIN (SELECT ID, display_name FROM `{$wpdb->users}`) u ON c.user_id = u.ID
WHERE c.comment_date BETWEEN %s AND %s
GROUP BY c.user_id
ORDER BY rating_count DESC, comments_count DESC
LIMIT 10";

		$query_prepared = $wpdb->prepare(
			$query,
			BaseHelper::normalize_to_datetime( $this->date_from ),
			BaseHelper::normalize_to_datetime( $this->date_to )
		);

		$data = $wpdb->get_row( $query_prepared, ARRAY_A );

		if ( empty( $data ) ) {
			return $response;
		}

		$response['avatar']  = \AnyComment\Rest\AnyCommentSocialAuth::get_user_avatar_url( $data['user_id'] );
		$response['message'] = sprintf( __( '<strong>%s</strong> is the most active for <i>number of comments</i> and <i>rating</i>.', 'anycomment-analytics' ),
			! empty( $data['display_name'] ) ? trim( $data['display_name'] ) : __( 'No name', 'anycomment-analytics' )
		);

		return $response;
	}

	/**
	 * Get comments summary such as number of approved comments and posts affected,
	 * count of unapproved, spammed or trashed comments.
	 *
	 * @return string
	 */
	public function get_comments_summary () {

		global $wpdb;

		$query = "SELECT 
	SUM(c.comment_approved = 1) AS approved_count, 
	SUM(c.comment_approved = 0) AS unapproved_count,
	SUM(c.comment_approved = 'spam') AS spam_count,
	SUM(c.comment_approved = 'trash') AS trash_count,
    COUNT(DISTINCT c.comment_post_ID) AS post_count,
    SUM(c.comment_parent > 0) AS comment_reply_count,
    SUM(c.comment_parent = 0) AS comment_parent_count
FROM `{$wpdb->comments}` `c`
WHERE c.comment_date BETWEEN %s AND %s";


		$sql_prepared = $wpdb->prepare(
			$query,
			BaseHelper::normalize_to_datetime( $this->date_from ),
			BaseHelper::normalize_to_datetime( $this->date_to )
		);

		$data = $wpdb->get_row( $sql_prepared, ARRAY_A );


		if ( empty( $data ) ) {
			return '';
		}

		$result = sprintf(
			__( 'Published <strong>%s comments</strong> for <strong>%s posts</strong>.', 'anycomment-analytics' ),
			empty( $data['approved_count'] ) ? 0 : $data['approved_count'],
			empty( $data['post_count'] ) ? 0 : $data['post_count']
		);

		$result .= ' ' . __( 'From this:', 'anycomment-analytics' ) . '<br>';

		$items = [];

		$items[] = sprintf(
			_nx(
				'%s reply',
				'%s replies',
				$data['comment_reply_count'],
				'Number of comment replies in email template',
				'anycomment-analytics'
			),
			number_format_i18n( $data['comment_reply_count'] )
		);

		$items[] = sprintf( __( '%s awaiting moderation', 'anycomment-analytics' ), number_format_i18n( $data['unapproved_count'] ) );

		$items[] = sprintf( __( '%s spam', 'anycomment-analytics' ), number_format_i18n( $data['spam_count'] ) );

		$items[] = sprintf( __( '%s trashed', 'anycomment-analytics' ), number_format_i18n( $data['trash_count'] ) );

		foreach ( $items as $item ) {
			$result .= '<br>- ' . $item;
		}

		return $result;
	}

	/**
	 * {@inheritdoc}
	 */
	public function add_to_queue () {

		$received_emails = [];

		if ( empty( $this->email ) ) {
			$admin_email = get_option( 'admin_email' );

			if ( empty( $admin_email ) ) {
				return false;
			}

			$received_emails[] = $admin_email;
		} else {

			if ( is_string( $this->email ) ) {
				$received_emails = explode( ',', $this->email );
			} elseif ( is_array( $this->email ) ) {
				$received_emails = $this->email;
			}
		}

		$received_emails = array_map( 'trim', $received_emails );

		if ( empty( $received_emails ) ) {
			return false;
		}

		$added_count = 0;

		foreach ( $received_emails as $recipient_email ) {

			$email = new \AnyComment\Models\AnyCommentEmailQueue();

			$email->email      = $recipient_email;
			$email->subject    = sprintf( __( "Analytical Summary for %s and %s", 'anycomment-analytics' ),
				BaseHelper::normalize_to_datetime( $this->date_from, 'd.m.Y' ),
				BaseHelper::normalize_to_datetime( $this->date_to, 'd.m.Y' )
			);
			$email->post_ID    = 0;
			$email->comment_ID = 0;
			$email->content    = $this->prepare();

			if ( $email->save() ) {
				$added_count ++;
			}
		}

		return $added_count === count( $received_emails );
	}

	/**
	 * Get prepare email content.
	 *
	 * @return null|string
	 */
	public function getPreparedContent () {
		return $this->prepared_content;
	}

}

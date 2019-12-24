<?php

class AnyCommentAnalyticComment {

	const MODERATED_META_KEY = 'anycomment_analytics_moderator_approved';

	/**
	 * Comment constructor.
	 */
	public function __construct () {
		/**
		 * Fires immediately after a comment is inserted into the database.
		 *
		 * @since 2.8.0
		 *

		 */
		add_action( 'wp_set_comment_status', [ $this, 'follow_comment_status_change' ], 10, 2 );
	}

	/**
	 * Follows newly inserted comment via `wp_insert_comment` hook.
	 *
	 * @param int $id The comment ID.
	 * @param string $status Current comment status. Possible values include
	 *                                    'hold', 'approve', 'spam', 'trash', or false.
	 */
	public function follow_comment_status_change ( $id, $status ) {
		$user_id = get_current_user_id();

		if ( (int) $user_id !== 0 ) {
			if ( $status === 'approve' ) {
				add_comment_meta( $id, self::MODERATED_META_KEY, $user_id );
			} elseif ( $status === 'hold' || ! $status ) {
				delete_comment_meta( $id, self::MODERATED_META_KEY );
			}
		}
	}
}
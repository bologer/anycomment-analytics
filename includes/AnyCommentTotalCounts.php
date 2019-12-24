<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class AnyCommentTotalCounts is used to query total count related data.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class AnyCommentTotalCounts {

	/**
	 * Get total count of social users.
	 *
	 * @return int
	 */
	public static function get_socials () {
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->users} u LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id";

		$meta_key_value = \AnyComment\Rest\AnyCommentSocialAuth::META_SOCIAL_TYPE;

		$sql .= $wpdb->prepare( " WHERE um.meta_key = %s AND um.meta_key != ''", $meta_key_value );

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}


	/**
	 * Get total count of uploaded files.
	 *
	 * @return int
	 */
	public static function get_files () {
		global $wpdb;

		$table_name = \AnyComment\Models\AnyCommentUploadedFiles::get_table_name();

		$sql = "SELECT COUNT(*) FROM $table_name";

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Get total count of post subscribers.
	 *
	 * @return int
	 */
	public static function get_post_subscribers () {
		global $wpdb;

		$table_name = \AnyComment\Models\AnyCommentSubscriptions::get_table_name();

		$sql = "SELECT COUNT(*) FROM $table_name";

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Get total count of sent emails.
	 *
	 * @return int
	 */
	public static function get_emails () {
		global $wpdb;

		$table_name = \AnyComment\Models\AnyCommentEmailQueue::get_table_name();

		$sql = "SELECT COUNT(*) FROM $table_name WHERE `is_sent` = 1";

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Get total count of post ratings.
	 *
	 * @return int
	 */
	public static function get_post_ratings () {
		global $wpdb;

		$table_name = \AnyComment\Models\AnyCommentRating::get_table_name();

		$sql = "SELECT COUNT(*) FROM $table_name";

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Get total count of likes.
	 *
	 * @return int
	 */
	public static function get_likes () {
		global $wpdb;

		$table_name = \AnyComment\Models\AnyCommentLikes::get_table_name();


		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE `type` = %d", \AnyComment\Models\AnyCommentLikes::TYPE_LIKE );

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Get total count of dislikes.
	 *
	 * @return int
	 */
	public static function get_dislikes () {
		global $wpdb;

		$table_name = \AnyComment\Models\AnyCommentLikes::get_table_name();


		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE `type` = %d", \AnyComment\Models\AnyCommentLikes::TYPE_DISLIKE );

		$count = $wpdb->get_var( $sql );

		if ( empty( $count ) ) {
			return 0;
		}

		return (int) $count;
	}
}

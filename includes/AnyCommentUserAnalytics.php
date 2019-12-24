<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class AnyCommentUserAnalytics is a core class for analytical queries.
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class AnyCommentUserAnalytics
{
    /**
     * @var string|int UNIX timestamp or datetime since when get the data.
     */
    protected $since;

    /**
     * @var string|int UNIX timestamp or datetime until when get the data.
     */
    protected $until;

    /**
     * @var int Default period defined.
     */
    protected $period = null;

    /**
     * @var string Default period when undefined. Default value: 1 week.
     */
    protected $default_period = '-1 month';

    /**
     * @var int Default cache timeout.
     */
    protected $cache_time = 120;

    /**
     * @var null|string
     */
    protected $missingDataMessage = null;

    /**
     * AnyCommentUserAnalytics constructor.
     */
    public function __construct()
    {

        $date_format              = get_option('date_format');
        $from_date                = date_i18n($date_format, $this->get_since_timestamp());
        $to_date                  = date_i18n($date_format, $this->get_until_timestamp());
        $this->missingDataMessage = sprintf(esc_html__('No data available between %s and %s', 'anycomment-analytics'),
            $from_date, $to_date);
    }

    /**
     * @param int|string $since
     *
     * @return $this
     */
    public function set_since($since)
    {

        $since = trim($since);

        if (empty($since)) {
            $since = null;
        }

        $this->since = $since;

        return $this;
    }

    /**
     * @param int|string $until
     *
     * @return $this
     */
    public function set_until($until)
    {

        $until = trim($until);

        if (empty($until)) {
            $until = null;
        }


        $this->until = $until;

        return $this;
    }

    /**
     * @param int $period
     *
     * @return $this
     */
    public function set_period($period)
    {

        $period = trim($period);

        if (empty($period)) {
            $period = null;
        }

        switch ($period) {
            case 'today':
                $this->set_since(strtotime('today midnight'));
                break;
            case 'yesterday':
                $this->set_since(strtotime('yesterday midnight'));
                $this->set_until(strtotime('today midnight'));
                break;
            case 'week':
                $this->set_since(strtotime('-1 week'));
                break;
            case 'month':
                $this->set_since(strtotime('-1 month'));
                break;
            case 'quarter':
                $this->set_since(strtotime('-4 months'));
                break;
            case 'year':
                $this->set_since(strtotime('-1 year'));
                break;
        }

        return $this;
    }


    /**
     * @return int|string
     */
    public function get_since()
    {

        $since = $this->since;

        if ($since === null) {
            $since = strtotime($this->default_period);
        }

        return $since;
    }

    /**
     * @return int|string
     */
    public function get_until()
    {
        $until = $this->until;

        if ($until === null) {
            return time();
        }

        return $until;
    }

    /**
     * Get since value normalized as datetime.
     *
     * @return int|string
     */
    public function get_since_datetime()
    {
        return BaseHelper::normalize_to_datetime($this->get_since());
    }

    /**
     * Get until value normalized as datetime.
     *
     * @return int|string
     */
    public function get_until_datetime()
    {
        return BaseHelper::normalize_to_datetime($this->get_until());
    }

    /**
     * Get since value normalized as UNIX timestamp.
     *
     * @return int|string
     */
    public function get_since_timestamp()
    {
        return BaseHelper::normalize_to_time($this->get_since());
    }

    /**
     * Get until value normalized as UNIX timestamp.
     *
     * @return int|string
     */
    public function get_until_timestamp()
    {
        return BaseHelper::normalize_to_time($this->get_until());
    }

    /**
     * @return int
     */
    public function get_cache_time()
    {
        return $this->cache_time;
    }

    /**
     * Get registered users with ability to specify from and to period.
     *
     * @return array|null|object
     */
    public function get_registered_users()
    {

        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_registered_users';
        $since    = $this->get_since_datetime();
        $until    = $this->get_until_datetime();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);


        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $meta_key = \AnyComment\Rest\AnyCommentSocialAuth::META_SOCIAL_TYPE;
        $sql      = "SELECT 
COUNT(*) as count, 
DATE_FORMAT(u.user_registered, '%m') AS order_by, 
DATE_FORMAT(u.user_registered, '%M %d') as day_period, 
UNIX_TIMESTAMP(u.user_registered) as unix_timestamp FROM `{$wpdb->users}` u
LEFT JOIN `{$wpdb->usermeta}` um ON um.user_id = u.ID 
WHERE  um.meta_key = '{$meta_key}' AND um.meta_value IS NOT NULL";

        $sql .= $wpdb->prepare(" AND u.user_registered BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY day_period ORDER BY order_by ASC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label' => __('Users', 'anycomment-analytics'),
                        'data'  => [],
                    ],
                ],
            ],
            'message' => '',
        ];

        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]              = date_i18n(get_option('date_format'),
                    $result['unix_timestamp']);
                $prepared['chart']['datasets'][0]['data'][] = $result['count'];

                $color                                                      = static::generate_bar_color($result['count']);
                $prepared['chart']['datasets'][0]['backgroundColor'][]      = $color;
                $prepared['chart']['datasets'][0]['hoverBackgroundColor'][] = $color;
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get most uses socials.
     *
     * @return array
     */
    public function get_most_used_socials()
    {

        $cache = AnyCommentAnalytics::cache();

        $cache_key = 'anycomment/addons/rest/get_most_used_socials';
        $since     = $this->get_since_datetime();
        $until     = $this->get_until_datetime();

        if ($since !== null) {
            $cache_key .= '/' . $since;
        }

        if ($until !== null) {
            $cache_key .= '/' . $until;
        }

        $item = $cache->getItem($cache_key);

        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $meta_key = \AnyComment\Rest\AnyCommentSocialAuth::META_SOCIAL_TYPE;
        $sql      = "SELECT um.meta_value AS social_name, COUNT(*) AS usage_count
FROM `{$wpdb->users}` u 
LEFT JOIN `{$wpdb->usermeta}` um ON um.user_id = u.ID
WHERE um.meta_key = '{$meta_key}' AND um.meta_value IS NOT NULL";

        $sql .= $wpdb->prepare(" AND u.user_registered BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY social_name ORDER BY usage_count ASC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [],
            ],
            'message' => '',
        ];


        if ( ! empty($results)) {
            $socials = \AnyComment\AnyCommentSocials::get_all();

            $total_count = 0;

            foreach ($results as $count_result) {
                $total_count += $count_result['usage_count'];
            }

            foreach ($results as $key => $result) {
                $name                                                  = isset($socials[$result['social_name']]['label']) ? $socials[$result['social_name']]['label'] : $result['social_name'];
                $color                                                 = isset($socials[$result['social_name']]['color']) ? $socials[$result['social_name']]['color'] : '#eaeaea';
                $percent                                               = static::get_percentage($total_count,
                    $result['usage_count'], true);
                $prepared['chart']['labels'][]                         = sprintf('%s (%s)', $name, $percent);
                $prepared['chart']['datasets'][0]['data'][]            = $result['usage_count'];
                $prepared['chart']['datasets'][0]['backgroundColor'][] = $color;
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get comments per period.
     *
     * @return array
     */
    public function get_comments()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_comments';
        $since    = $this->get_since_datetime();
        $until    = $this->get_until_datetime();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);


        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $sql = "SELECT 
COUNT(*) as count, 
DATE_FORMAT(comment_date, '%m %d') AS group_by, 
DATE_FORMAT(comment_date, '%M %d') as day_period, 
UNIX_TIMESTAMP(comment_date) as unix_timestamp FROM {$wpdb->comments}";

        $sql .= $wpdb->prepare(" WHERE comment_date BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY group_by, day_period ORDER BY unix_timestamp ASC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label' => __('Comments', 'anycomment-analytics'),
                        'data'  => [],
                    ],
                ],
            ],
            'message' => '',
        ];

        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]              = date_i18n(get_option('date_format'),
                    $result['unix_timestamp']);
                $prepared['chart']['datasets'][0]['data'][] = $result['count'];

                $color                                                      = static::generate_bar_color($result['count']);
                $prepared['chart']['datasets'][0]['backgroundColor'][]      = $color;
                $prepared['chart']['datasets'][0]['hoverBackgroundColor'][] = $color;
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get comments common hour to leave it.
     *
     * @return array
     */
    public function get_comment_common_hours()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_comment_common_hour';
        $since    = $this->get_since_datetime();
        $until    = $this->get_until_datetime();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $sql = "SELECT COUNT(*) as common_count, DATE_FORMAT(comment_date, '%H:00') as common_hour FROM {$wpdb->comments}";

        $sql .= $wpdb->prepare(" WHERE comment_date BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY common_hour ORDER BY common_count DESC LIMIT 10";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label' => __('Hour', 'anycomment-analytics'),
                        'data'  => [],
                    ],
                ],
            ],
            'message' => '',
        ];

        $total_count = 0;

        if ( ! empty($results)) {

            foreach ($results as $count_result) {
                $total_count += $count_result['common_count'];
            }

            foreach ($results as $key => $result) {
                $percent                                               = static::get_percentage($total_count,
                    $result['common_count'], true);
                $prepared['chart']['labels'][]                         = sprintf('%s (%s)', $result['common_hour'],
                    $percent);
                $prepared['chart']['datasets'][0]['data'][]            = $result['common_count'];
                $prepared['chart']['datasets'][0]['backgroundColor'][] = static::generate_color([
                    __METHOD__,
                    $this->period,
                    $result['common_hour'],
                ]);
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get uploaded file by period.
     *
     * @return array
     */
    public function get_uploaded_files()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_uploaded_files';
        $since    = $this->get_since_timestamp();
        $until    = $this->get_until_timestamp();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);


        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $table = \AnyComment\Models\AnyCommentUploadedFiles::get_table_name();

        $sql = "SELECT 
COUNT(*) as count, 
DATE_FORMAT(FROM_UNIXTIME(created_at), '%d') AS order_by, 
DATE_FORMAT(FROM_UNIXTIME(created_at), '%m') as month_period,
UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME(created_at + 86400), '%Y-%m-%d')) AS unix_timestamp
FROM `$table`";

        $sql .= $wpdb->prepare(" WHERE created_at BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY order_by, month_period, unix_timestamp ORDER BY month_period, order_by ASC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label' => __('Files', 'anycomment-analytics'),
                        'data'  => [],
                    ],
                ],
            ],
            'message' => '',
        ];

        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]              = date_i18n(get_option('date_format'),
                    $result['unix_timestamp']);
                $prepared['chart']['datasets'][0]['data'][] = $result['count'];

                $color                                                      = static::generate_bar_color($result['count']);
                $prepared['chart']['datasets'][0]['backgroundColor'][]      = $color;
                $prepared['chart']['datasets'][0]['hoverBackgroundColor'][] = $color;
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get subscriptions by period.
     *
     * @return array
     */
    public function get_subscriptions()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_subscriptions';
        $since    = $this->get_since_timestamp();
        $until    = $this->get_until_timestamp();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);


        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $table = \AnyComment\Models\AnyCommentSubscriptions::get_table_name();

        $sql = "SELECT 
COUNT(*) as count, 
DATE_FORMAT(FROM_UNIXTIME(created_at), '%d') AS order_by, 
DATE_FORMAT(FROM_UNIXTIME(created_at), '%m') as month_period,
UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME(created_at + 86400), '%Y-%m-%d')) AS unix_timestamp
FROM `$table`";


        $sql .= $wpdb->prepare(" WHERE created_at BETWEEN %d AND %d", $since, $until);

        $sql .= " GROUP BY order_by, month_period, unix_timestamp ORDER BY month_period, order_by ASC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label' => __('Subscriptions', 'anycomment-analytics'),
                        'data'  => [],
                    ],
                ],
            ],
            'message' => '',
        ];

        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]              = date_i18n(get_option('date_format'),
                    $result['unix_timestamp']);
                $prepared['chart']['datasets'][0]['data'][] = $result['count'];

                $color                                                      = static::generate_bar_color($result['count']);
                $prepared['chart']['datasets'][0]['backgroundColor'][]      = $color;
                $prepared['chart']['datasets'][0]['hoverBackgroundColor'][] = $color;
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get emails by period.
     *
     * @return array
     */
    public function get_emails()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_emails';
        $since    = $this->get_since_datetime();
        $until    = $this->get_until_datetime();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);


        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $table = \AnyComment\Models\AnyCommentEmailQueue::get_table_name();

        // todo: change logic to UNIX time once created at will be UNIX timestamp instead of DATETIME
        $sql = "SELECT 
COUNT(*) as count, 
DATE_FORMAT(created_at, '%d') AS order_by, 
DATE_FORMAT(created_at, '%m') as month_period,
UNIX_TIMESTAMP(DATE_FORMAT(created_at, '%Y-%m-%d')) AS unix_timestamp
FROM `$table`";

        $sql .= $wpdb->prepare(" WHERE created_at BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY order_by, month_period, unix_timestamp ORDER BY month_period, order_by ASC";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [
                    [
                        'label' => __('Emails', 'anycomment-analytics'),
                        'data'  => [],
                    ],
                ],
            ],
            'message' => '',
        ];

        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]              = date_i18n(get_option('date_format'),
                    $result['unix_timestamp']);
                $prepared['chart']['datasets'][0]['data'][] = $result['count'];

                $color                                                      = static::generate_bar_color($result['count']);
                $prepared['chart']['datasets'][0]['backgroundColor'][]      = $color;
                $prepared['chart']['datasets'][0]['hoverBackgroundColor'][] = $color;
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get emails by period.
     *
     * @return array
     */
    public function get_most_subscribed_posts()
    {
        $cache = AnyCommentAnalytics::cache();
        $since = $this->get_since_timestamp();
        $until = $this->get_until_timestamp();

        $cacheKey = 'anycomment/addons/rest/get_most_subscribed_posts';

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);


        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $subsriptions_table = \AnyComment\Models\AnyCommentSubscriptions::get_table_name();
        $sql                = "SELECT p.post_title as post_title, COUNT(p.ID) AS sub_count, anys.post_ID 
FROM `$subsriptions_table` anys 
LEFT JOIN `{$wpdb->posts}` p ON anys.post_ID = p.ID";

        $sql .= $wpdb->prepare(' AND anys.created_at BETWEEN %s AND %s', $since, $until);

        $sql .= ' GROUP BY post_title, anys.post_ID';
        $sql .= ' HAVING sub_count > 0';
        $sql .= ' ORDER BY sub_count DESC LIMIT 10';

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'label'    => [],
            'datasets' => [],
        ];


        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]                         = wp_trim_words($result['post_title'], 5);
                $prepared['chart']['datasets'][0]['data'][]            = $result['sub_count'];
                $prepared['chart']['datasets'][0]['backgroundColor'][] = static::generate_color();
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get most popular posts by rating.
     *
     * @return array
     */
    public function get_popular_posts_by_rating()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_popular_posts_by_rating';
        $since    = $this->get_since_timestamp();
        $until    = $this->get_until_timestamp();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $rating_table = \AnyComment\Models\AnyCommentRating::get_table_name();
        $sql          = "SELECT AVG(ar.rating) as rating, p.post_title FROM {$rating_table} ar 
LEFT JOIN {$wpdb->posts} p ON ar.post_ID = p.ID";

        $sql .= $wpdb->prepare(" AND ar.created_at BETWEEN %s AND %s", $since, $until);

        $sql .= " GROUP BY post_title ORDER BY rating DESC LIMIT 10";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'label'    => [],
                'datasets' => [],
            ],
            'message' => '',
        ];


        if ( ! empty($results)) {
            foreach ($results as $key => $result) {
                $prepared['chart']['labels'][]                         = wp_trim_words($result['post_title'], 5);
                $prepared['chart']['datasets'][0]['data'][]            = round($result['rating'], 2);
                $prepared['chart']['datasets'][0]['backgroundColor'][] = static::generate_color();
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Get most popular uploaded file types.
     *
     * @return array
     */
    public function get_popular_uploaded_file_types()
    {
        $cache = AnyCommentAnalytics::cache();

        $cacheKey = 'anycomment/addons/rest/get_popular_uploaded_file_types';
        $since    = $this->get_since_timestamp();
        $until    = $this->get_until_timestamp();

        if ($since !== null) {
            $cacheKey .= '/' . $since;
        }

        if ($until !== null) {
            $cacheKey .= '/' . $until;
        }

        $item = $cache->getItem($cacheKey);

        if ($item->isHit()) {
            return $item->get();
        }

        global $wpdb;

        $files_table = \AnyComment\Models\AnyCommentUploadedFiles::get_table_name();
        $sql         = "SELECT COUNT(*) as total, `type` AS file_type FROM $files_table af";

        $sql .= $wpdb->prepare(" WHERE af.created_at BETWEEN %d AND %d", $since, $until);

        $sql .= " GROUP BY file_type ORDER BY total DESC LIMIT 10";

        $results = $wpdb->get_results($sql, ARRAY_A);

        $prepared = [
            'status'  => 'ok',
            'chart'   => [
                'labels'   => [],
                'datasets' => [],
            ],
            'message' => '',
        ];

        if ( ! empty($results)) {
            $total_count = 0;

            foreach ($results as $count_result) {
                $total_count += $count_result['total'];
            }

            foreach ($results as $key => $result) {
                $percent                                               = static::get_percentage($total_count,
                    $result['total'], true);
                $prepared['chart']['labels'][]                         = sprintf('%s (%s)', $result['file_type'],
                    $percent);
                $prepared['chart']['datasets'][0]['data'][]            = $result['total'];
                $prepared['chart']['datasets'][0]['backgroundColor'][] = static::generate_color();
            }
        } else {
            $prepared['message'] = $this->missingDataMessage;
        }

        $item->set($prepared)
             ->expiresAfter(60 * 60)
             ->save();

        return $prepared;
    }

    /**
     * Generate color for bar.
     *
     * @param int $count Count of items to investigate color range.
     *
     * @return string
     */
    public static function generate_bar_color($count)
    {
        $color = '#CCECFF';

        if ($count > 5) {
            $color = '#85C9F2';
        }

        if ($count > 10) {
            $color = '#1DA1F2';
        }

        return $color;
    }

    /**
     * Generate eye pretty HEX color.
     *
     * @param mixed $data Data to be used to generate hash based on.
     *
     * @return string
     */
    public static function generate_color($data = null)
    {

//		if ( ! is_string( $data ) ) {
//			$data = serialize( $data );
//		}

        return AnyCommentAnalyticsColor::one();
        /*
         * $data_hash = hash( 'sha256', $data );
         *  [
            'prng' => function ( $min, $max ) use ( $data_hash ) {
                $counter = ++ AnyCommentAnalyticsColor::$colorCounter;
                $seed    = crc32( $counter . $data_hash );
                mt_srand( $seed );

                return mt_rand( $min, $max );
            },
        ]
         */
    }

    /**
     * @param int $total Total item count.
     * @param int $count Intermediate count.
     * @param bool $format Whether return formatted with "%s" or not.
     *
     * @return string|int|null string when $format is true, int otherwise. NULL on failure.
     */
    public static function get_percentage($total, $count, $format = false)
    {
        $percent = ($count / $total) * 100;
        $percent = round($percent, 2);

        if ($format) {
            return sprintf('%s%%', $percent);
        }

        return $percent;
    }
}

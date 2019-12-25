<?php
/**
 * Plugin Name: AnyComment Analytics
 * Plugin URI: https://anycomment.io
 * Description: Advanced Analytics for AnyComment.
 * Version: 0.2
 * Author: AnyComment.io
 * Author URI: https://anycomment.io
 * Requires at least: 4.4
 * Requires PHP: 5.4
 * Tested up to: 5.0
 * Text Domain: anycomment-analytics
 * Domain Path: /languages
 *
 * @package AnyComment
 * @author bologer
 */

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

defined('ANYCOMMENT_ANALYTICS_PLUGIN_FILE') or define('ANYCOMMENT_ANALYTICS_PLUGIN_FILE', __FILE__);
defined('ANYCOMMENT_ANALYTICS_LANG') or define('ANYCOMMENT_ANALYTICS_LANG', __FILE__);
defined('ANYCOMMENT_ANALYTICS_ABSPATH') or define('ANYCOMMENT_ANALYTICS_ABSPATH', dirname(__FILE__));
defined(
    'ANYCOMMENT_ANALYTICS_PLUGIN_BASENAME') or define('ANYCOMMENT_ANALYTICS_PLUGIN_BASENAME',
    plugin_basename(__FILE__)
);
defined('ANYCOMMENT_ANALYTICS_DEBUG') or define('ANYCOMMENT_ANALYTICS_DEBUG', false);
defined('ANYCOMMENT_ANALYTICS_VERSION') or define('ANYCOMMENT_ANALYTICS_VERSION', 0.2);

/**
 * Class AnyCommentAnalytics is core class for premium Add-on for "AnyComment Analytics".
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class AnyCommentAnalytics
{

    /**
     * @var float Plugin version.
     */
    public $version = 0.2;

    /**
     * @var null|AnyCommentAnalytics
     */
    public static $instance = null;

    /**
     * @var \Stash\Pool
     */
    protected static $cache;

    /**
     * AnyCommentAnalytics constructor.
     */
    public function __construct()
    {
        add_action('anycomment/loaded', [$this, 'init'], 11);

        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Initiate object dependencies.
     */
    public function init()
    {

        if (class_exists('\AnyComment\AnyCommentCore')) {
            static::$cache = \AnyComment\AnyCommentCore::cache();
        }

        $this->init_includes();
        $this->init_tab();
    }

    /**
     * Initiate core scripts.
     */
    public function init_includes()
    {
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Helpers/BaseHelper.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Hooks/AnyCommentAnalyticComment.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/AnyCommentAnalyticsColor.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/AnyCommentUserAnalytics.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/AnyCommentTotalCounts.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Emails/EmailNotificationInterface.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Emails/AdminNotification.php';
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Pages/ReportSettings.php';

        // Crons
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Cron/AnyCommentAnalyticsReport.php';

        // Ajax
        require_once ANYCOMMENT_ANALYTICS_ABSPATH . '/includes/Ajax/AnyCommentAnalyticsAjaxReport.php';

        new AnyCommentAnalyticComment();
        new AnyCommentAnalyticsAjaxReport();
        new AnyCommentAnalyticsAjaxReport();

        // Pages
        new ReportSettings();
    }

    /**
     * Initiate assets.
     */
    public function enqueue_assets()
    {
        wp_enqueue_script('anycomment-analytics-core', $this->plugin_url() . '/assets/js/charts.js', [],
            ANYCOMMENT_ANALYTICS_VERSION);
        wp_enqueue_script('anycomment-analytics-common', $this->plugin_url() . '/assets/js/common.js', [],
            ANYCOMMENT_ANALYTICS_VERSION);
        wp_enqueue_script('jquery-ui-datepicker');

        wp_enqueue_style('anycomment-analytics-core', $this->plugin_url() . '/assets/scss/core.css',
            ['anycomment-admin-styles'], ANYCOMMENT_ANALYTICS_VERSION);
        wp_register_style('jquery-ui', $this->plugin_url() . '/assets/css/jquery-ui.css');
        wp_enqueue_style('jquery-ui');

        wp_localize_script('anycomment-analytics-core', 'anycommentAnalytics', [
            'fromDate' => esc_attr__('From date', 'anycomment-analytics'),
            'toDate'   => esc_attr__('To date', 'anycomment-analytics'),
            'loading'  => esc_html__('Loading...', 'anycomment-analytics'),

            'periodToday'     => esc_html__('Today', 'anycomment-analytics'),
            'periodYesterday' => esc_html__('Yesterday', 'anycomment-analytics'),
            'periodWeek'      => esc_html__('Week', 'anycomment-analytics'),
            'periodMonth'     => esc_html__('Month', 'anycomment-analytics'),
            'periodQuarter'   => esc_html__('Quarter', 'anycomment-analytics'),
            'periodYear'      => esc_html__('Year', 'anycomment-analytics'),

            'customPeriod' => esc_html__('Custom Interval:', 'anycomment-analytics'),
        ]);
    }


    /**
     * Initiate new tab for AnyComment.
     */
    public function init_tab()
    {
        add_filter('anycomment/admin/tabs', function ($tabs, $active_tab) {
            $tabs['analytics'] = [
                'url'      => menu_page_url($_GET['page'], false) . '&tab=analytics',
                'text'     => __('Analytics', 'anycomment-analytics'),
                'callback' => ANYCOMMENT_ANALYTICS_ABSPATH . '/templates/graphs',
            ];

            return $tabs;
        }, 11, 2);

        add_action('rest_api_init', function () {
            register_rest_route('anycomment-analytics/v1', "/chart", array(
                'methods'  => 'GET',
                'callback' => [$this, 'graph_data'],
            ));
        });
    }

    /**
     * Process WP REST API requests to get charts data.
     *
     * @param $request
     *
     * @return mixed|WP_Error|WP_REST_Response
     */
    public function graph_data($request)
    {
        $response = new \WP_REST_Response();

        if ( ! isset($request['for'])) {
            return new \WP_Error(403, __('Missing "for" param', 'anycomment'), ['status' => 403]);
        }

        $since  = isset($request['since']) && ! empty($request['since']) ? $request['since'] : null;
        $until  = isset($request['until']) && ! empty($request['until']) ? $request['until'] : null;
        $period = isset($request['period']) && ! empty($request['period']) ? $request['period'] : null;

        $model = new AnyCommentUserAnalytics();

        if ($since !== null) {
            $model->set_since($since);
        }

        if ($until !== null) {
            $model->set_until($until);
        }

        if ($period !== null) {
            $model->set_period($period);
        }

        switch ($request['for']) {
            case 'users':
                $data = $model->get_registered_users();
                break;
            case 'socials':
                $data = $model->get_most_used_socials();
                break;
            case 'comments':
                $data = $model->get_comments();
                break;
            case 'comment_common_hours':
                $data = $model->get_comment_common_hours();
                break;
            case 'files':
                $data = $model->get_uploaded_files();
                break;
            case 'files_by_extension':
                $data = $model->get_popular_uploaded_file_types();
                break;
            case 'subscriptions':
                $data = $model->get_subscriptions();
                break;
            case 'emails':
                $data = $model->get_emails();
                break;
            case 'most_subscribed_posts':
                $data = $model->get_most_subscribed_posts();
                break;
            case 'popular_posts_by_rating':
                $data = $model->get_popular_posts_by_rating();
                break;
            default:
                $data = [];
                break;
        }

        $response->set_data($data);

        return rest_ensure_response($response);
    }

    /**
     * Get the plugin url.
     * @return string
     */
    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', ANYCOMMENT_ANALYTICS_PLUGIN_FILE));
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(ANYCOMMENT_ANALYTICS_PLUGIN_FILE));
    }

    /**
     * Get cache manager.
     *
     * @return \Stash\Pool
     */
    public static function cache()
    {
        return static::$cache;
    }

    /**
     * Create instance of the core model.
     *
     * @return AnyCommentAnalytics|null
     */
    public static function instance()
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        static::$instance = new self();

        return static::$instance;
    }
}

/**
 * Get instance of analytics add-on.
 *
 * @return AnyCommentAnalytics|null
 */
function anycomment_analytics()
{
    return AnyCommentAnalytics::instance();
}

anycomment_analytics();

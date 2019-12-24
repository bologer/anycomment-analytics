<?php
$active_tab = isset( $_GET['category'] ) ? sanitize_text_field( $_GET['category'] ) : 'summary';

$url  = menu_page_url( $_GET['page'], false ) . '&tab=analytics';
$tabs = [
	'summary'  => [
		'url'  => $url . '&category=summary',
		'text' => __( 'Summary', 'anycomment-analytics' ),
	],
	'reports'  => [
		'url'  => $url . '&category=reports',
		'text' => __( 'Reports', 'anycomment-analytics' ),
	],
	'comments' => [
		'url'  => $url . '&category=comments',
		'text' => __( 'Comments', 'anycomment-analytics' ),
	],
	'users'    => [
		'url'  => $url . '&category=users',
		'text' => __( 'Users', 'anycomment-analytics' ),
	],
	'posts'    => [
		'url'  => $url . '&category=posts',
		'text' => __( 'Posts', 'anycomment-analytics' ),
	],
	'files'    => [
		'url'  => $url . '&category=files',
		'text' => __( 'Files', 'anycomment-analytics' ),
	],
	'emails'   => [
		'url'  => $url . '&category=emails',
		'text' => __( 'Emails', 'anycomment-analytics' ),
	],
];

/**
 * Filters list of available tabs.
 *
 * @since 0.0.76
 *
 * @param array $tabs An array of available tabs.
 *
 * @package string $active_tab Active tab.
 */
$tabs = apply_filters( 'anycomment/addons/analytics/admin/tabs', $tabs, $active_tab );
?>

<div class="anycomment-tab">
    <div class="anycomment-tabs grid-x grid-margin-x">
        <aside class="cell large-3 medium-4 small-12 anycomment-tabs__menu">

			<?php if ( ! empty( $tabs ) ): ?>
                <ul class="cell">
					<?php foreach ( $tabs as $key => $tab ): ?>
                        <li<?php echo $active_tab === $key ? ' class="active"' : '' ?>><a
                                    href="<?php echo $tab['url'] ?>"><?php echo $tab['text'] ?></a>
                        </li>
					<?php endforeach; ?>
                </ul>
			<?php endif; ?>
        </aside>
        <div class="cell auto anycomment-tabs__container">
            <div class="grid-x anycomment-tabs__container__tab current">
				<?php

				$callback = isset( $tabs[ $active_tab ]['callback'] ) ? $tabs[ $active_tab ]['callback'] : null;

				if ( $callback !== null ) {
					echo \AnyComment\Helpers\AnyCommentTemplate::render( $callback );
				} else {
					echo \AnyComment\Helpers\AnyCommentTemplate::render( ANYCOMMENT_ANALYTICS_ABSPATH . '/templates/tab-' . $active_tab );
				}
				?>
            </div>
        </div>
    </div>
</div>

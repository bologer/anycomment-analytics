<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Comments', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'All comments per specified period of time.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="comments"
             data-type="bar"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=comments' ) ?>"></div>
    </div>
</div>

<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Common Hours', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Common hours for users to comment. Displayed in 24 hours format.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="comments-popular-hours"
             data-type="pie"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=comment_common_hours' ) ?>"></div>
    </div>
</div>
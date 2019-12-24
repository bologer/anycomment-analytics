<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Popular by Rating', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Most popular posts by rating.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="posts-by-rating"
             data-type="pie"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=popular_posts_by_rating' ) ?>">
        </div>
    </div>
</div>

<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Popular by Subscriptions', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Popular posts where users subscribed the most.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="post-subscriptions"
             data-type="pie"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=most_subscribed_posts' ) ?>">
        </div>
    </div>
</div>

<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Subscriptions', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Subscriptions per specified period of time.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="subscriptions"
             data-type="bar"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=subscriptions' ) ?>">
        </div>
    </div>
</div>

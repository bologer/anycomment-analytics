<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Users', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Number of registered users per specified period of time.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="users"
             data-type="bar"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=users' ) ?>"
        ></div>
    </div>
</div>

<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Social Networks', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'List of most used social networks.', 'anycomment-analytics' ) ?></p>
        <div data-chart-root="user-socials"
             data-type="pie"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=socials' ) ?>">
        </div>
    </div>
</div>
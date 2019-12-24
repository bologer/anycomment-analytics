<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Emails', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Emails per specified period of time.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="emails"
             data-type="bar"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=emails' ) ?>">
        </div>
    </div>
</div>

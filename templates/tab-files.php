<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Files', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Uploaded files per specified period of time.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="files"
             data-type="bar"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=files' ) ?>">
        </div>
    </div>
</div>

<div class="grid-x">
    <div class="small-12 cell">
        <h2><?= __( 'Popular File Types', 'anycomment-analytics' ) ?></h2>
        <p><?= __( 'Popular file types uploaded.', 'anycomment-analytics' ) ?></p>

        <div data-chart-root="files-by-extension"
             data-type="pie"
             data-url="<?php echo rest_url( 'anycomment-analytics/v1/chart?for=files_by_extension' ) ?>">
        </div>
    </div>
</div>

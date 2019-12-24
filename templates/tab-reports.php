<div class="grid-x">
    <div class="small-12 cell">
		<?php echo ( new ReportSettings() )->run() ?>

        <hr>

        <h2><?php _e( 'Send Report Now', 'anycomment-analytics' ) ?></h2>
        <p><?php _e( 'You may use button below to send report now. It would add emails to the queue and they should be send within a minute.', 'anycomment-analytics' ) ?></p>
        <a href="<?php echo admin_url( 'admin.php?page=anycomment-dashboard&tab=analytics&category=reports' ) ?>"
           class="button"
           onclick="sendReport(); return false;">
			<?php esc_html_e( 'Send now', 'anycomment-analytics' ) ?>
        </a>

        <script type="text/javascript">

            /**
             * Send report via AJAX.
             *
             * @returns {boolean}
             */
            function sendReport() {
                var data = {
                    'action': 'anycomment_analytics_send_report',
                };

                jQuery.post(ajaxurl, data, function (response) {
                    alert(response.data.message);
                }).error(function (data) {
                    console.log('Failed to send report as of error: ', data);
                });

                return false;
            }
        </script>
    </div>
</div>

<?php if ( $alert_type == 'plugin_activation' ) :?>
    <div id="trafficguard-plugin-notification-holder" class="updated">
        <form name="trafficguard-plugin-activation" action="<?php echo esc_url( TrafficGuard::get_settings_page_url() ); ?>" method="POST">
            <div class="trafficguard-plugin-activation">
                <div class="tg-box-logo">
                    <img src="<?php echo esc_url( TrafficGuard::get_settings_page_logo() ); ?>" alt="TrafficGuard Logo">
                </div>
                <div class="tg-button-container">
                    <input type="submit" class="tg-box-button" value="<?php esc_attr_e( 'GET STARTED', 'trafficguard' ); ?>" />
                </div>
                <div class="tg-box-description"><?php _e('Setup your TrafficGuard plugin to get started', 'trafficguard');?></div>
            </div>
        </form>
    </div>
<?php endif;?>
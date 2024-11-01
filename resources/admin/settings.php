<div class="wrap">

    <?php
    $active_tab = isset( $_REQUEST[ 'tab' ] ) ? $_REQUEST[ 'tab' ] : "main_settings";
    ?>

    <?php
        $plugin_version = "1.9";
    ?>

    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo TRAFFICGUARD_PLUGIN_SLUG__ . "settings";?>" class="nav-tab">
            <?php _e("TrafficGuard", "trafficguard");?>
        </a>
    </h2>
    <div class="trafficguard-notification-container">
        <div class="trafficguard-notification trafficguard-notification-box">
            <img src="<?php echo TRAFFICGUARD_RESOURCES__ . "images/tg_properties_hi.png"?>" width="100" height="100">
            <div class="trafficguard-notification trafficguard-notification-content">
                <h3>Setup your TrafficGuard plugin</h3>
                <p>The TrafficGuard Plugin is comprised of two parts - a client plugin that is installed on site tag and the TrafficGuard API. The first performs analysis of requests coming to your website and the second allows this data to be sent to TrafficGuard.</p>
                <p>To finish plugin setup please go to <a href="https://trafficguard.ai" target="_blank">TrafficGuard.ai</a>, register and setup your property. You will be provided with a property_id which should be entered on this page.</p>
            </div>
        </div>
    </div>

    <form method="post" action="">
        <table class="form-table">
            <?php
            if( $active_tab == 'main_settings' ) {
                wp_nonce_field('trafficguard_settings_instance_action','trafficguard_settings_instance' );
                ?>
                <tr valign="top">
                    <th scope="row">
                        <?php _e("TrafficGuard Property ID", "trafficguard");?>
                    </th>
                    <td>
                        <input type="text" name="property_id" id="property_id" value="<?php echo self::getOption("property_id", TrafficGuard_Constants::TRAFFICGUARD_DEFAULT_PROPERTY_ID);?>" class="regular-text">
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <?php _e("Custom Integration parameters", "trafficguard");?>
                    </th>
                    <td>
                        <input type="text" name="custom_integration" id="custom_integration" value="<?php echo self::getOption("custom_integration", TrafficGuard_Constants::TRAFFICGUARD_DEFAULT_CUSTOM_INTEGRATION, true);?>" class="regular-text">
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
        <input type="hidden" name="tab" value="<?php echo esc_html($active_tab);?>">
        <input type="hidden" name="plugin_version" value="<?php echo $plugin_version;?>">

        <?php submit_button(__("Save Changes", "trafficguard"), "primary", "trafficguard-settings"); ?>
    </form>

</div>

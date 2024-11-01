<?php
/*
	Plugin Name: TrafficGuard
	Plugin URI: https://help.trafficguard.ai/knowledge/measurement-wordpress
	Text Domain: trafficguard
	Description: TrafficGuard Service plugin for Wordpress
	Version: 1.9
	Author: TrafficGuard Pty Ltd
	Author URI: https://trafficguard.ai
	License: GPLv2 or later
	License URI: https://www.gnu.org/licenses/gpl-2.0.html

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * Kill plugin if in process of installing
 * */
if (defined("WP_INSTALLING") && WP_INSTALLING) return;

require_once 'trafficguard-runtime-config.php';

/**
 * Plugin constants setup
 */
if (!defined("TRAFFICGUARD_PLUGIN_NAME__")) {
    define ("TRAFFICGUARD_PLUGIN_NAME__", "TrafficGuard");
}
if (!defined("TRAFFICGUARD_PLUGIN_SLUG__")) {
    define("TRAFFICGUARD_PLUGIN_SLUG__", "__trafficguard_");
}
if (!defined("TRAFFICGUARD_VERSION__")) {
    define("TRAFFICGUARD_VERSION__", 1.9);
}
if (!defined("TRAFFICGUARD_DIR__")) {
    define("TRAFFICGUARD_DIR__", trailingslashit(plugin_dir_path(__FILE__)));
}
if (!defined("TRAFFICGUARD_URL")) {
    define("TRAFFICGUARD_URL", plugin_dir_url(__FILE__));
}
if (!defined("TRAFFICGUARD_ROOT__")) {
    define("TRAFFICGUARD_ROOT__", trailingslashit(plugins_url("", __FILE__)));
}
if (!defined("TRAFFICGUARD_LOG_FILE__")) {
    define("TRAFFICGUARD_LOG_FILE__", TRAFFICGUARD_DIR__ . "log/trafficguard_wordpress.log");
}
if (!defined("TRAFFICGUARD_RESOURCES__")) {
    define("TRAFFICGUARD_RESOURCES__", TRAFFICGUARD_ROOT__ . "resources/");
}
if (!defined("TRAFFICGUARD_INCLUDE__")) {
    define("TRAFFICGUARD_INCLUDE__", TRAFFICGUARD_DIR__. "include/");
}
if (!defined("TRAFFICGUARD_IMAGES__")) {
    define("TRAFFICGUARD_IMAGES__", TRAFFICGUARD_RESOURCES__ . "images/");
}
if (!defined("TRAFFICGUARD_DEBUG__")) {
    define("TRAFFICGUARD_DEBUG__", false);
}

/* debug enabled settings - more details in app logs*/
if (TRAFFICGUARD_DEBUG__) {
    @error_reporting(E_ALL);
    @ini_set("display_errors", "1");
}

/*
 * TrafficGuard Wordpress Plugin
 */
class TrafficGuard {
    /**/
    public function __construct() {
        // folder for log files. just to be sure.
        if (!file_exists(TRAFFICGUARD_DIR__. "log")) {
            @mkdir(TRAFFICGUARD_DIR__. "log");
        }

        register_activation_hook(__FILE__, array($this, 'trafficguard_activate'));
        register_deactivation_hook(__FILE__, array($this, 'trafficguard_deactivate'));

        $this->loadHooks();
        $this->loadClasses();
    }

    /*
     * Test register
     * */
    function trafficguard_register() {
        // do nothing -> ok, initialize cookies after init. otherwise later initalization
        // triggers some headers issues
        $global_cookie_id = TrafficGuard_GeneralUtils::get_global_cookie_id();
        $wp_session_id = TrafficGuard_GeneralUtils::get_current_user_id();
    }


    /**/
    private function loadHooks() {
        //tell wp what to do when plugin is activated and uninstalled
        add_action("init", array($this, "trafficguard_register"));
        add_action("plugins_loaded", array($this, "trafficguard_i18n"));
        add_action("admin_menu", array($this, "trafficguard_admin_menu"));
        add_action("admin_notices", array( 'TrafficGuard', 'display_plugin_notification' ) );

        add_action("wp_footer", array($this, "trafficguard_wp_footer"));

        add_action("admin_enqueue_scripts", array($this, "trafficguard_settings_styles"));

        add_filter("admin_footer_text", array($this, "trafficguard_system_alert_footer"));
        add_action("wp_ajax_" . TRAFFICGUARD_PLUGIN_SLUG__, array($this, "ajax"));
    }


    /**/
    private function loadClasses() {
        require_once TRAFFICGUARD_INCLUDE__ . "TrafficGuardAutoLoader.php";
        new TrafficGuardAutoLoader();
        TrafficGuard_GeneralUtils::$log_file = TRAFFICGUARD_LOG_FILE__;
    }

    // load custom styles
    function trafficguard_settings_styles() {
        wp_register_style( "trafficguard_wordpress_plugin", TRAFFICGUARD_RESOURCES__ . "css/trafficguard_wordpress_plugin.css", array(), TRAFFICGUARD_VERSION__ );
        wp_enqueue_style( "trafficguard_wordpress_plugin");
    }

    // backup option - update every page with source code
    function trafficguard_wp_footer() {
        if (!is_admin() && !is_feed() && !is_trackback()) {
            $property_id = self::getOption("property_id");
            $custom_integration = self::getOption("custom_integration",'',true);
            $custom_integration_final = "";


            $wp_session_id = TrafficGuard_GeneralUtils::get_current_user_id();

            if (!self::is_custom_integration_still_default()) {
                $custom_integration_final = self::clean_custom_integration_code($custom_integration) . ",";
            }

            $integration_code = "";

            if (substr( $property_id, 0, 2 ) === "tg") {
                $integration_code = "<script>var dataTrafficGuard = dataTrafficGuard || [];";

                //add support for property group id's
                if (strpos($property_id, 'tg-g') !== false) {
                    $integration_code = $integration_code . "dataTrafficGuard.push(['property_group_id', '" . $property_id . "']);";
                } else {
                    $integration_code = $integration_code . "dataTrafficGuard.push(['property', '" . $property_id . "']);";
                }

                $integration_code = $integration_code . "dataTrafficGuard.push(['event','pageview','{" . $custom_integration_final . "\"wordpress_integration\":true,\"wordpress_integration_session_id\":\"" . $wp_session_id . "\"}']);";
                $integration_code = $integration_code . "(function() {";
                $integration_code = $integration_code . "var tg = document.createElement('script'); tg.type = 'text/javascript'; tg.async = true;tg.src = '//tgtag.io/tg.js?pid=" . $property_id . "';";
                $integration_code = $integration_code . "var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(tg, s);";
                $integration_code = $integration_code . "})();</script>";
                if (strpos($property_id, 'tg-g') !== false) {
                    $integration_code = $integration_code . "<noscript><img src=\"//p.trafficguard.ai/event?property_group_id=" . $property_id . "&event_name=pageview&wordpress_integration=true&wordpress_integration_session_id=" . $wp_session_id . "&noscript=1\" style=\"display:none;visibility:hidden\"/></noscript>";
                } else {
                    $integration_code = $integration_code . "<noscript><img src=\"//p.trafficguard.ai/event?property_id=" . $property_id . "&event_name=pageview&wordpress_integration=true&wordpress_integration_session_id=" . $wp_session_id . "&noscript=1\" style=\"display:none;visibility:hidden\"/></noscript>";
                }
            }

            if ( $integration_code!= "" ) {
                echo $integration_code, "\n";
            }
        }
    }

    /**/
    function trafficguard_system_alert_footer() {
        // TODO: Make this nicer with boxes and everything!!!
        if (is_admin()) {
            if (!TrafficGuard_GeneralUtils::curl_exists()) {
                return "<font color = 'red'> TrafficGuard: Please check your CURL instalation to make sure system is fully operational.</font>";
            }
        }
        return "";
    }

    /**/
    function trafficguard_i18n() {
        $pluginDirName  = dirname(plugin_basename(__FILE__));
        $domain         = TRAFFICGUARD_PLUGIN_SLUG__;
        $locale         = apply_filters("plugin_locale", get_locale(), $domain);
        load_textdomain($domain, WP_LANG_DIR . "/" . $pluginDirName . "/" . $domain . "-" . $locale . ".mo");
        load_plugin_textdomain($domain, "", $pluginDirName . "/resources/lang/");
    }


    /**/
    function trafficguard_admin_menu() {
        add_menu_page(TRAFFICGUARD_PLUGIN_NAME__, TRAFFICGUARD_PLUGIN_NAME__, "manage_options", TRAFFICGUARD_PLUGIN_SLUG__ . "settings", array($this, "trafficguard_settings"), TRAFFICGUARD_IMAGES__. "favicon.png");
    }

    /*
     * TrafficGuard Plugin Settings
     * */
    function trafficguard_settings() {
        $retrieved_nonce = $_POST['trafficguard_settings_instance'];
        if (isset($_POST["trafficguard_settings_instance"]) && $_POST["tab"] == "main_settings") {
            if (current_user_can('administrator') // nonce / user access check!
                && wp_verify_nonce($retrieved_nonce, 'trafficguard_settings_instance_action')) {
                self::setOption("property_id", sanitize_text_field($_POST["property_id"]));
                self::setOption("custom_integration", sanitize_text_field($_POST["custom_integration"]));
                self::setOption("plugin_version", sanitize_text_field($_POST["plugin_version"]));
            }
        }

        include_once TRAFFICGUARD_DIR__ . "resources/admin/settings.php";
    }

    /*
     * Settings notification display helpers
     **/
    public static function display_plugin_notification() {
        global $hook_suffix;
        if ( $hook_suffix == 'plugins.php' && self::is_property_id_still_default() ) {
            TrafficGuard_GeneralUtils::initiate_notification_view( 'system_notification', array( 'alert_type' => 'plugin_activation' ) );
        }
    }

    /***/
    public static function is_property_id_still_default() {
        if (self::getOption("property_id") == TrafficGuard_Constants::TRAFFICGUARD_DEFAULT_PROPERTY_ID) {
            return true;
        }
        return false;
    }


    /***/
    public static function is_custom_integration_still_default() {
        if (self::getOption("custom_integration") == TrafficGuard_Constants::TRAFFICGUARD_DEFAULT_CUSTOM_INTEGRATION) {
            return true;
        }
        return false;
    }

    /**
     * Test integrated code for {} and clean them up
     * could be in integration due to messed up copy / pass
    */
    public static function clean_custom_integration_code($input) {
        $result = str_replace("{","", $input);
        $result = str_replace("}","", $result);
        $result = str_replace("&quot;","\"", $result);
        return $result;
    }

    /*
     * Build WP admin page url for TrafficGuard settings
     * */
    public static function get_settings_page_url() {
        $args = array( 'page' => '__trafficguard_settings' );
        $url = add_query_arg($args, admin_url('admin.php' ));
        return $url;
    }


    /**/
    public static function 	get_settings_page_logo() {
        return TRAFFICGUARD_RESOURCES__ . "images/tg_settings_logo.png";
    }

    /**
     * Custom wrapper for the get_option function
     *
     * @return string
     */
    public static function getOption($field, $default=false, $clean=false) {
        $val = get_option(TRAFFICGUARD_PLUGIN_SLUG__ . $field, $default);
        if ($clean) {
            // cleanup for UI only!
            $cval = htmlspecialchars($val);
            return str_replace("\\", "", $cval);
        } 

        return $val;
    }

    /**
     * Custom wrapper for the update_option function
     *
     * @return mixed
     */
    public static function setOption($field, $value) {
        return update_option(TRAFFICGUARD_PLUGIN_SLUG__ . $field, $value);
    }

    /*
     * TrafficGuard Plugin Activation Hook
     * */
    function trafficguard_activate() {
        TrafficGuard_GeneralUtils::write_log("TrafficGuard Plugin Activate", "INFO");
        register_uninstall_hook(__FILE__, array($this, 'trafficguard_uninstall'));
    }

    /*
     * TrafficGuard Plugin Deactivation Hook
     * */
    function trafficguard_deactivate() {
        TrafficGuard_GeneralUtils::write_log("TrafficGuard Plugin Deactivate", "INFO");
    }

    /*
     * TrafficGuard Plugin Uninstall Hook
     * */
    function trafficguard_uninstall() {
        @unlink(TRAFFICGUARD_LOG_FILE__);
        TrafficGuard_GeneralUtils::write_log("TrafficGuard Plugin Uninstall", "INFO");
    }
}

// initiate TrafficGuard
$traffic_guard = new TrafficGuard();
<?php
	// Place your runtime settings here
	define("TRAFFICGUARD_DEBUG__", false);
	define("TRAFFICGUARD_PLUGIN_NAME__", "TrafficGuard");
	define("TRAFFICGUARD_PLUGIN_SLUG__", "__trafficguard_");
	define("TRAFFICGUARD_VERSION__", 1.9);
	define("TRAFFICGUARD_DIR__", trailingslashit(plugin_dir_path(__FILE__)));
	define("TRAFFICGUARD_URL", plugin_dir_url(__FILE__));
	define("TRAFFICGUARD_ROOT__", trailingslashit(plugins_url("", __FILE__)));
	define("TRAFFICGUARD_LOG_FILE__", TRAFFICGUARD_DIR__ . "log/trafficguard_wordpress.log");
	define("TRAFFICGUARD_RESOURCES__", TRAFFICGUARD_ROOT__ . "resources/");
	define("TRAFFICGUARD_INCLUDE__", TRAFFICGUARD_DIR__. "include/");
	define("TRAFFICGUARD_IMAGES__", TRAFFICGUARD_RESOURCES__ . "images/");
?>
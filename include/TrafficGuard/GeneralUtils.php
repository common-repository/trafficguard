<?php

/**
 * General Utils, bits n pieces
 */
class TrafficGuard_GeneralUtils {
	const UUID_URL_LENGTH = 22;
	const UUID_STRING_LENGTH = 36;
	
	const GLOBAL_USER_ID_VERSION = 0; // current version of the GUIV
	const SERVICE_START_EPOCH_SECOND = 1514764800; // 01-01-2018
	const SERVICE_STOP_EPOCH_SECOND = 2145916800; // 01-01-2038
	
	static $log_file = null;
	
	/**/
	public static function write_log($text, $type) {
		file_put_contents(self::$log_file, date("Y-m-d h:i:s") . " (" . $type . ") " . $text . "\n", FILE_APPEND);
	}
	
	/* Return TRUE if it exists... :) */
	public static function curl_exists() {
		return function_exists('curl_version');
	}
	
	/* Get current user id from PHPSESSIONID*/
	public static function get_current_user_id() {
		$wp_session_id = '';
		
		if (isset($_COOKIE['_tg_wpsid'])) {
			$wp_session_id = $_COOKIE['_tg_wpsid'];
		} else {
			$wp_session_id = self::generate_uuidv4();
			setcookie("_tg_wpsid", $wp_session_id, time() + (60*60*1), "/"); // 1h for session cookie (60*60*24*30 - 30 days)
		}
		return $wp_session_id;
	}
	
	
	/* Get current user id from PHPSESSIONID*/
	public static function get_global_cookie_id() {
		$global_cookie_id = '';
		
		if(isset($_COOKIE['_tg_wpgcid'])) {
			$global_cookie_id = $_COOKIE['_tg_wpgcid'];
		} else {
			$global_cookie_id = self::generate_global_cookie_id();
			setcookie("_tg_wpgcid", $global_cookie_id, time() + (365*24*60*60), "/"); // 1y for session cookie
		}
		return $global_cookie_id;
	}
	
	/* 
	 * Get current user id from PHPSESSIONID
	 * */
	public static function get_cookie_data() {
		if(isset($_COOKIE)) {
			return $_COOKIE;
		}
		return array();
	}
	
	/**
	 * Generate UUID v4 by standard
	 * */
	public static function generate_uuidv4() {
		$uuidv4 = array(
				'time_low'  => 0,
				'time_mid'  => 0,
				'time_hi'  => 0,
				'clock_seq_hi' => 0,
				'clock_seq_low' => 0,
				'node'   => array()
		);
		
		$uuidv4['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
		$uuidv4['time_mid'] = mt_rand(0, 0xffff);
		$uuidv4['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
		$uuidv4['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
		$uuidv4['clock_seq_low'] = mt_rand(0, 255);
		
		for ($i = 0; $i < 6; $i++) {
			$uuidv4['node'][$i] = mt_rand(0, 255);
		}
		
		$uuidv4= sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
				$uuidv4['time_low'],
				$uuidv4['time_mid'],
				$uuidv4['time_hi'],
				$uuidv4['clock_seq_hi'],
				$uuidv4['clock_seq_low'],
				$uuidv4['node'][0],
				$uuidv4['node'][1],
				$uuidv4['node'][2],
				$uuidv4['node'][3],
				$uuidv4['node'][4],
				$uuidv4['node'][5]
				);
		return $uuidv4;
	}
	
	/**
	 * Generate (WP) Global Cookie Id
	 * */
	public static function generate_global_cookie_id() {
		$uuid = null;
		
		$campaign_id = 0;
		$affiliate_id = 0;
		$cluster_id = 1;
		$created_at_seconds = time();

		$app_id = floor((self::random_float() * 10000) + 1);
		$partner_id = floor((self::random_float() * 20000) + 1);
		
		// get random bits the good way (like in 2014 :D)
		$uuid = openssl_random_pseudo_bytes(16);
		
		// version in bits 0 - 3
		$uuid[0] = chr(ord($uuid[0]) & 15 | (self::GLOBAL_USER_ID_VERSION << 4));
		
		// cluster_id in bits 8 - 15
		$uuid[1] = chr($cluster_id);
		
		//js_tag - version - partner_id in bits 16-39
		$uuid[2] = chr($partner_id>> 16);
		$uuid[3] = chr(($partner_id>> 8) & 255);
		$uuid[4] = chr($partner_id & 255);
		
		// toggle a couple of bits to make it a valid UUID4
		$uuid[6] = chr(ord($uuid[6]) & 15 | 64); // bits 48-52 = 4
		$uuid[8] = chr(ord($uuid[8]) & 63 | 128); // bit 64 = 1, bit 65 = 0
		
		// app_id in bits 72 - 95
		$uuid[9] = chr(($app_id>> 16) & 255);
		$uuid[10] = chr(($app_id>> 8) & 255);
		$uuid[11] = chr($app_id& 255);
		
		//js_tag - version - created at in bits 96 - 143
		$uuid[12] = chr(($created_at_seconds >> 24) & 255);
		$uuid[13] = chr(($created_at_seconds >> 16) & 255);
		$uuid[14] = chr(($created_at_seconds >> 8) & 255);
		$uuid[15] = chr($created_at_seconds & 255);

		// convert bit->string
		return self::byte_to_uuid4_string($uuid);
	}
	
	/*
	 * Format uuid in format: 0000-0000-0000000000-0000-0000
	 */
	private static function format_uuid($input) {
		return substr($input, 0, 4) . "-" . substr($input,8,4) . "-" . substr($input,8,10) . "-" . substr($input,18,4) . "-" . substr($input,22);
	}
	
	/**
	 * Canonical UUID4 format, eg. f47ac10b-58cc-4372-a567-0e02b2c3d479
	 *
	 * @return string
	 */
	private static function byte_to_uuid4_string($uuid){
		$string = '';
		foreach (range(0, 15) as $step) {
			$string .= sprintf('%02x', ord($uuid[$step]));
			if (in_array($step, array(3, 5, 7, 9))) {
				$string .= '-';
			}
		}
		
		return $string;
	}
	
	
	private static function random_float ($min = 0, $max = 1) {
		return ($min+lcg_value()*(abs($max-$min)));
	}
}

<?php
/*
Plugin Name: WP Cloudflare GeoIP Redirect
Plugin URI: https://webinvade.rs/wordpress-plugins/wp-cloudflare-geoip-redirect/
Description: Easily setup redirect for visitors/users from selected countries to specific URL utilizing Cloudflare IP Geolocation.
Version: 1.4
Author: Web Invaders
Author URI: https://webinvade.rs/
License: A "Slug" license name e.g. GPL2
Text Domain: wpcfr
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 *
 * Admin Page Framework
 *
 */
include dirname( __FILE__ ).'/options.php';
function wpcfr_settings_link( $links ) {
	$url = get_admin_url() . 'admin.php?page=wpcfr_plugin_options';
	$settings_link = '<a href="' . $url . '">' . __('Settings', 'wpcfr') . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

function wpcfr_after_setup_theme() {
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wpcfr_settings_link');
}
add_action ('after_setup_theme', 'wpcfr_after_setup_theme');

/**
 *
 * WI CloudFlare GeoIP redirect
 *
 */
function wp_cloudflareRedirect() {

	if(!empty($_SERVER["HTTP_CF_IPCOUNTRY"])) {
		$country_code = sanitize_text_field($_SERVER["HTTP_CF_IPCOUNTRY"]);

		$options = get_option( 'wpcfr_plugin_options', false );
		//print_r($options);

		//header('wpcfr-plugin-country: ' . $country_code);

		if ( in_array( 'administrator', wp_get_current_user()->roles ) || strpos( $_SERVER['REQUEST_URI'], 'wp-' ) !== false ) {

			//new AdminPageFramework_AdminNotice( 'Cloudflare GeoIP Detected: '.$country_code, array( 'class' => 'updated' ) );
			//print_r("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

		} else {

			if( ! empty( $options['debug_frontend'] ) && $options['debug_frontend'] ) {
				$current_url = home_url($_SERVER['REQUEST_URI']);
				$debug = $country_code.' | Options: '. json_encode($options) . ' | ' . $current_url;
				//echo '<div class="wpcfredirect">' . 'DEBUG / Cloudflare Redirect Plugin: ' . $debug.'</div>';
				header('wpcfr-plugin-debug: ' . $debug);
			}

			foreach ( $options['wpcfr_redirects'] as $s ) {

				$compare = in_array( $country_code, $s['country'] );
				//print_r($s);
				//print_r($country_code);

				//print_r($compare);

				if ( $compare ) {
					//print_r($s['country']);
					//echo $s['url'];
					$current_url = home_url($_SERVER['REQUEST_URI']);
					$redirect_type     = sanitize_text_field( $s['type'] );

					if ( !empty( $s['type'] ) ) {

						if ( $s['type'] == 100 ) {
							//echo $country_code. ' / redirection is set to none: '.$s['type']." / ".$s['url'];
						} else {
							if(  ! empty( $s['query_parameter_name'] ) && $s['query_parameter_name']) {

								$current_url = home_url($_SERVER['REQUEST_URI']);
								$parameter_name = sanitize_text_field($s['query_parameter_name']);

								if(! empty( $s['query_parameter_value'] ) && $s['query_parameter_value']) {
									$parameter_value = sanitize_text_field($s['query_parameter_value']);
								}else {
									$parameter_value = strtolower( $country_code );
								}

								$redirect_url = add_query_arg( $parameter_name, $parameter_value, $current_url );
								//print_r($generate_url);
								if( $current_url != $redirect_url) {
									wp_redirect( $redirect_url, $redirect_type, "WP Cloudflare GeoIP redirect plugin" );
									exit;
								}
							}else {

								if ( ! empty( $s['url'] ) && $s['url'] && $current_url != $s['url'] ) {
									$redirect_location = esc_url_raw( $s['url'] );
									//$redirect_type     = sanitize_text_field( $s['type'] );
									//echo $country_code ." redirect_location:".$redirect_location. " redirect_type:".$redirect_type;
									wp_redirect( $redirect_location, $redirect_type, "WP Cloudflare GeoIP redirect plugin" );
									exit;
								}
							}
						}

					}
				}
			}
		}
	}

}
add_action('init','wp_cloudflareRedirect');

function wpcfr_cache_control_handle_redirects( $status, $location ) {

	//$options = get_option( 'wpcfr_plugin_options', false );
	//if(! empty($options['cache_control']) && $options['cache_control']==0) {

	if ( $status == 301 || $status == 307 ) {
		header ( "Cache-Control: no-cache, no-store, must-revalidate" );
	}

	//}
	return $status;
}
add_filter( 'wp_redirect_status', 'wpcfr_cache_control_handle_redirects', 1, 2 );
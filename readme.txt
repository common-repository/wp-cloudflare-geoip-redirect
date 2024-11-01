=== WP Cloudflare GeoIP Redirect ===
Contributors: webinvaders
Donate link: https://webinvade.rs/donate/
Tags: redirect, geoip, geolocation, cloudflare
Requires at least: 4.6
Tested up to: 5.7
Stable tag: 1.1
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily setup redirect for visitors/users from selected countries to specific URL utilizing Cloudflare IP Geolocation.

== Description ==

WP Cloudflare GeoIP Redirect plugin enables you to setup redirect for users from selected countries to specific URL.

Geolocation is done using Cloudflare IP Geolocation data.

Redirection is done using the php header() function and you can choose Temporary Redirect (307) or Moved Permanently (301).

In order to use this plugin you need to setup Cloudflare for your website and enable Cloudflare IP Geolocation service. More info in FAQ.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-cloudflare-geoip-redirect` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the CF Redirect>Options screen to configure the plugin



== Frequently Asked Questions ==

= Do I need a Cloudflare account to use the plugin? =

You don't need CloudFlare account credentials for using plugin but you do need to have Cloudflare service active on your domain in order for plugin to get Geolocation user data and enabled *Cloudflare IP Geolocation*.

= How to enable Cloudflare IP Geolocation =

For info on how to configure Cloudflare IP Geolocation follow the link: https://support.cloudflare.com/hc/en-us/articles/200168236-Configuring-Cloudflare-IP-Geolocation

= Will this plugin work with other Caching plugins? =

Frankly we don't know yet. As of v1.3 new HTTP header is added when redirection is enabled (Cache-Control: no-cache, no-store, must-revalidate) but it's on cache plugin to honor this setting

= LiteSpeed Cache plugin workaround if redirection is not working =

This issue is fixed by adding new rule to your .htaccess file before ## LITESPEED WP CACHE PLUGIN section
'<IfModule LiteSpeed>
RewriteEngine On
RewriteRule .* - [E=Cache-Control:vary=%{HTTP:CF-IPCountry}]
</IfModule>'

You can read more about it here https://wordpress.org/support/topic/wp_redirect-not-working-with-litespeed-cache-on/#post-14280128

== Screenshots ==

1. Basic setup for one country redirect
2. Setup for redirecting users from multiple countries to same URL

== Changelog ==

= 1.4 =
* option to add custom Query string parameter name and value to current URL based on redirect settings
= 1.3 =
* fixed redirect loop issue when website url is same as redirect url
* added HTTP header "Cache-Control: no-cache, no-store, must-revalidate"
* after working with "LiteSpeed Cache" to fix the issues with redirect not working when caching is enabled workaround is added to FAQ
= 1.2 =
* changed redirect code to use "wp_redirect"
= 1.1 =
* Added CMB2 framework for plugin options
= 1.0 =
* First release

== Upgrade Notice ==

= 1.0 =
Enjoy using WP Cloudflare GeoIP Redirect
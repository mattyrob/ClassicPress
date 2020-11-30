<?php
/*
Plugin Name: REST API
Description: Notifies an email list when new entries are posted.
Version: 1.0
Author: ClassicPress
Author URI: https://www.classicpress.net/
*/

define( 'RESTPATH', trailingslashit( dirname( __FILE__ ) ) );

// Load the files
require( RESTPATH . 'rest-api.php' );
require( RESTPATH . 'rest-api/class-wp-rest-server.php' );
require( RESTPATH . 'rest-api/class-wp-rest-response.php' );
require( RESTPATH . 'rest-api/class-wp-rest-request.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-posts-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-attachments-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-post-types-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-post-statuses-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-revisions-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-taxonomies-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-terms-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-users-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-comments-controller.php' );
require( RESTPATH . 'rest-api/endpoints/class-wp-rest-settings-controller.php' );
require( RESTPATH . 'rest-api/fields/class-wp-rest-meta-fields.php' );
require( RESTPATH . 'rest-api/fields/class-wp-rest-comment-meta-fields.php' );
require( RESTPATH . 'rest-api/fields/class-wp-rest-post-meta-fields.php' );
require( RESTPATH . 'rest-api/fields/class-wp-rest-term-meta-fields.php' );
require( RESTPATH . 'rest-api/fields/class-wp-rest-user-meta-fields.php' );

// Hook the REST API actions.
add_action( 'init', 'rest_api_init' );
add_action( 'rest_api_init', 'rest_api_default_filters',   10, 1 );
add_action( 'rest_api_init', 'register_initial_settings',  10 );
add_action( 'rest_api_init', 'create_initial_rest_routes', 99 );
add_action( 'parse_request', 'rest_api_loaded' );

// Hook REST API filters.
add_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
add_action( 'wp_head', 'rest_output_link_wp_head', 10, 0 );
add_action( 'template_redirect', 'rest_output_link_header', 11, 0 );
add_action( 'auth_cookie_malformed', 'rest_cookie_collect_status' );
add_action( 'auth_cookie_expired', 'rest_cookie_collect_status' );
add_action( 'auth_cookie_bad_username', 'rest_cookie_collect_status' );
add_action( 'auth_cookie_bad_hash', 'rest_cookie_collect_status' );
add_action( 'auth_cookie_valid', 'rest_cookie_collect_status' );
add_filter( 'rest_authentication_errors', 'rest_cookie_check_errors', 100 );

// Hook other areas of REST API using code from core
add_action( 'wp_default_scripts', 'restapi_wpApiSettings' );

function restapi_wpApiSettings( &$scripts ) {
	$suffix = SCRIPT_DEBUG ? '' : '.min';
	$scripts->add( 'wp-api-request', "/wp-includes/js/api-request$suffix.js", array( 'jquery' ), false, 1 );
	// `wpApiSettings` is also used by `wp-api`, which depends on this script.
	$scripts->localize( 'wp-api-request', 'wpApiSettings', array(
		'root'          => esc_url_raw( get_rest_url() ),
		'nonce'         => ( wp_installing() && ! is_multisite() ) ? '' : wp_create_nonce( 'wp_rest' ),
		'versionString' => 'wp/v2/',
	) );
}

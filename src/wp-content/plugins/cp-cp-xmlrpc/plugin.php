<?php
/*
Plugin Name: XMLRPC API
Description: XMLRPC API used by Jetpack and Apps.
Version: 1.0
Author: ClassicPress
Author URI: https://www.classicpress.net/
*/

define( 'XMLRPCPATH', trailingslashit( dirname( __FILE__ ) ) );

require XMLRPCPATH . 'xmlrpc.php';
require XMLRPCPATH . 'xmlrpc-functions.php';

add_action( 'xmlrpc', 'cp_cp_xmlrpc');
add_filter( 'xmlrpc_pingback_error', 'xmlrpc_pingback_error' );
add_action( 'wp_head', 'rsd_link' );

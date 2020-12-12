<?php
/**
 * XML-RPC protocol support for ClassicPress
 *
 * @package ClassicPress
 */

/**
 * Whether this is an XML-RPC Request
 *
 * @var bool
 */
define( 'XMLRPC_REQUEST', true );

// Some browser-embedded clients send cookies. We don't want them.
$_COOKIE = array();

// fix for mozBlog and other cases where '<?xml' isn't on the very first line
if ( isset($HTTP_RAW_POST_DATA) )
	$HTTP_RAW_POST_DATA = trim($HTTP_RAW_POST_DATA);

/** Include the bootstrap for setting up ClassicPress environment */
include( dirname( __FILE__ ) . '/wp-load.php' );

if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'cp-cp-xmlrpc/plugin.php' ) ) {
	do_action( 'xmlrpc' );
} else {
	wp_safe_redirect( site_url() );
}

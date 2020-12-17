<?php
/*
Plugin Name: Emojis
Description: Enable Emojis across all content.
Version: 1.0
Author: ClassicPress
Author URI: https://www.classicpress.net/
*/

define( 'EMOJIPATH', trailingslashit( dirname( __FILE__ ) ) );

require EMOJIPATH . 'emoji.php';

add_filter( 'wp_resource_hints', 'prefetch_emojis', 10, 2 );

add_action( 'wp_head', 'print_emoji_detection_script', 7 );
add_action( 'wp_print_styles', 'print_emoji_styles' );
add_action( 'admin_print_scripts', 'print_emoji_detection_script' );
add_action( 'admin_print_styles', 'print_emoji_styles' );
add_action( 'embed_head', 'print_emoji_detection_script' );
add_filter( 'the_content_feed', 'wp_staticize_emoji' );
add_filter( 'comment_text_rss', 'wp_staticize_emoji' );

add_filter( 'mce_external_plugins', 'tinymce_emoji_plugin' );

// Email filters
add_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

function prefetch_emojis( $url, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$url[] = apply_filters( 'emoji_svg_url', 'https://twemoji.classicpress.net/12/svg/' );
	}
	return $url;
}

function tinymce_emoji_plugin( $plugins ){
	$suffix = SCRIPT_DEBUG ? '' : '.min';
	$plugins[ 'wpemoji' ] = plugin_dir_url( __FILE__ ) . "tinymce/plugin$suffix.js";
	return $plugins;
}

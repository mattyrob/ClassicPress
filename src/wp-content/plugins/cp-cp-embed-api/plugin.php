<?php
/*
Plugin Name: Embed API
Description: Enable external Embedding of content and WordPress Embed endpoints. REQUIRES REST API.
Version: 1.0
Author: ClassicPress
Author URI: https://www.classicpress.net/
*/

define( 'OEMBEDPATH', trailingslashit( dirname( __FILE__ ) ) );

require( OEMBEDPATH . 'class-oembed.php' );
require( OEMBEDPATH . 'embed.php' );
require( OEMBEDPATH . 'class-wp-embed.php' );
require( OEMBEDPATH . 'class-wp-oembed-controller.php' );
require( OEMBEDPATH . 'ajax-actions.php' );

$GLOBALS['wp_embed'] = new WP_Embed();

class Core_Plugin_Embed_API {
	function __construct() {
		add_action( 'plugins_loaded', 'wp_maybe_load_embeds', 0 );
		// Embeds
		add_action( 'rest_api_init', 'wp_oembed_register_route' );
		add_filter( 'rest_pre_serve_request', '_oembed_rest_pre_serve_request', 10, 4 );

		add_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		add_action( 'wp_head', 'wp_oembed_add_host_js' );

		add_action( 'embed_head', 'enqueue_embed_scripts', 1 );
		add_action( 'embed_head', 'print_embed_styles' );

		add_action( 'embed_content_meta', 'print_embed_comments_button' );
		add_action( 'embed_content_meta', 'print_embed_sharing_button' );

		add_action( 'embed_footer', 'print_embed_sharing_dialog' );
		add_action( 'embed_footer', 'print_embed_scripts' );

		add_filter( 'the_content_feed', '_oembed_filter_feed_content' );

		add_filter( 'excerpt_more', 'wp_embed_excerpt_more', 20 );
		add_filter( 'the_excerpt_embed', 'wptexturize' );
		add_filter( 'the_excerpt_embed', 'convert_chars' );
		add_filter( 'the_excerpt_embed', 'wpautop' );
		add_filter( 'the_excerpt_embed', 'shortcode_unautop' );
		add_filter( 'the_excerpt_embed', 'wp_embed_excerpt_attachment' );

		add_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10, 3 );
		add_filter( 'oembed_response_data', 'get_oembed_response_data_rich', 10, 4 );
		add_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10, 3 );

		add_filter( 'media_view_settings', array( $this, 'oembed_settings' ), 10, 1 );

		// Hook AJAX actions
		$core_ajax_actions = array( 'oembed-cache' );
		if ( ! empty( $_GET['action'] ) && in_array( $_GET['action'], $core_ajax_actions ) )
			add_action( 'wp_ajax_' . $_GET['action'], 'wp_ajax_' . str_replace( '-', '_', $_GET['action'] ), 1 );

		if ( ! empty( $_POST['action'] ) && in_array( $_POST['action'], $core_ajax_actions ) )
			add_action( 'wp_ajax_' . $_POST['action'], 'wp_ajax_' . str_replace( '-', '_', $_POST['action'] ), 1 );

		add_action( 'wp_enqueue_styles', array( $this, 'register_embed_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_embed_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_embed_scripts' ) );
		add_action( 'wp_enqueue_editor', array( $this, 'enqueue_embed_script' ) );

		register_post_type( 'oembed_cache', array(
			'labels' => array(
				'name'          => __( 'oEmbed Responses' ),
				'singular_name' => __( 'oEmbed Response' ),
			),
			'public'           => false,
			'hierarchical'     => false,
			'rewrite'          => false,
			'query_var'        => false,
			'delete_with_user' => false,
			'can_export'       => false,
			'_builtin'         => true, /* internal use only. don't use this when registering your own post type. */
			'supports'         => array(),
		) );
	}

	public function oembed_settings( $settings ) {
		$exts = array_merge( wp_get_audio_extensions(), wp_get_video_extensions() );
		$mimes = get_allowed_mime_types();
		$ext_mimes = array();
		foreach ( $exts as $ext ) {
			foreach ( $mimes as $ext_preg => $mime_match ) {
				if ( preg_match( '#' . $ext . '#i', $ext_preg ) ) {
					$ext_mimes[ $ext ] = $mime_match;
					break;
				}
			}
		}

		$oembed_settings = array(
			'oEmbedProxyUrl' => rest_url( 'oembed/1.0/proxy' ),
			'embedExts'    => $exts,
			'embedMimes'   => $ext_mimes,
		);

		return array_merge( $settings, $oembed_settings );
	}

	public function register_embed_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		wp_register_style( 'wp-embed-template-ie', '/' . trailingslashit( PLUGINDIR ) . dirname( plugin_basename( __FILE__ ) ) . "/css/wp-embed-template-ie$suffix.css" );
		wp_style_add_data( 'wp-embed-template-ie', 'conditional', 'lte IE 8' );
	}

	public function register_embed_scripts() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'wp-embed', '/' . trailingslashit( PLUGINDIR ) . dirname( plugin_basename( __FILE__ ) ) . "js/wp-embed$suffix.js" );
	}

	public function enqueue_embed_script() {
		wp_enqueue_script( 'wp-embed' );
	}
}

global $core_plugin_embed_api;
$core_plugin_embed_api = new Core_Plugin_Embed_API();

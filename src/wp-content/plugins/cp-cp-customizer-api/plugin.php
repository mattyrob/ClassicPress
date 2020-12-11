<?php
/*
Plugin Name: Customizer API
Description: Enable site Customizer. REQUIRES REST API.
Version: 1.0
Author: ClassicPress
Author URI: https://www.classicpress.net/
*/

define( 'CUSTOMIZEPATH', trailingslashit( dirname( __FILE__ ) ) );

require CUSTOMIZEPATH . 'customize.php';
require CUSTOMIZEPATH . 'theme.php';

class Core_Plugin_Customizer {
	function __construct() {
		add_action( 'plugins_loaded', '_wp_customize_include' );
		add_action( 'transition_post_status', '_wp_customize_publish_changeset', 10, 3 );
		add_action( 'admin_enqueue_scripts', '_wp_customize_loader_settings' );
		add_action( 'delete_attachment', '_delete_attachment_theme_mod' );
		add_action( 'transition_post_status', '_wp_keep_alive_customize_changeset_dependent_auto_drafts', 20, 3 );
		add_filter( 'wp_insert_post_data', '_wp_customize_changeset_filter_insert_post_data', 10, 2 );
		add_action( 'customize_controls_enqueue_scripts', 'wp_plupload_default_settings' );

		add_filter( 'map_meta_cap', array( $this, 'add_customize_capability' ), 10, 4 );

		add_action( 'init', array( $this, 'register_js_scripts' ) );
		add_action( 'init', array( $this, 'localize_js_scripts' ) );
		add_action( 'init', array( $this, 'register_css' ) );

		add_filter( 'customize_controls_print_styles', 'wp_resource_hints', 1 );

		if ( is_admin() ) {
			require CUSTOMIZEPATH . 'customize-admin.php';
			add_action( 'customize_admin', 'customize_admin_page' );
		}

		register_post_type( 'customize_changeset', array(
			'labels' => array(
				'name'               => _x( 'Changesets', 'post type general name' ),
				'singular_name'      => _x( 'Changeset', 'post type singular name' ),
				'menu_name'          => _x( 'Changesets', 'admin menu' ),
				'name_admin_bar'     => _x( 'Changeset', 'add new on admin bar' ),
				'add_new'            => _x( 'Add New', 'Customize Changeset' ),
				'add_new_item'       => __( 'Add New Changeset' ),
				'new_item'           => __( 'New Changeset' ),
				'edit_item'          => __( 'Edit Changeset' ),
				'view_item'          => __( 'View Changeset' ),
				'all_items'          => __( 'All Changesets' ),
				'search_items'       => __( 'Search Changesets' ),
				'not_found'          => __( 'No changesets found.' ),
				'not_found_in_trash' => __( 'No changesets found in Trash.' ),
			),
			'public' => false,
			'_builtin' => true, /* internal use only. don't use this when registering your own post type. */
			'map_meta_cap' => true,
			'hierarchical' => false,
			'rewrite' => false,
			'query_var' => false,
			'can_export' => false,
			'delete_with_user' => false,
			'supports' => array( 'title', 'author' ),
			'capability_type' => 'customize_changeset',
			'capabilities' => array(
				'create_posts' => 'customize',
				'delete_others_posts' => 'customize',
				'delete_post' => 'customize',
				'delete_posts' => 'customize',
				'delete_private_posts' => 'customize',
				'delete_published_posts' => 'customize',
				'edit_others_posts' => 'customize',
				'edit_post' => 'customize',
				'edit_posts' => 'customize',
				'edit_private_posts' => 'customize',
				'edit_published_posts' => 'do_not_allow',
				'publish_posts' => 'customize',
				'read' => 'read',
				'read_post' => 'customize',
				'read_private_posts' => 'customize',
			),
		) );
	}

	public function add_customize_capability( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {
		if ( 'customize' === $cap )
			$caps = array( 'edit_theme_options' );

		return $caps;
	}

	function register_js_scripts() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		wp_register_script( 'customize-base', plugin_dir_url( __FILE__ ) . "js/customize-base$suffix.js", array( 'jquery', 'json2', 'underscore' ), null, 1 );
		wp_register_script( 'customize-loader', plugin_dir_url( __FILE__ ) . "js/customize-loader$suffix.js", array( 'customize-base' ), null, 1 );
		wp_register_script( 'customize-preview', plugin_dir_url( __FILE__ ) . "js/customize-preview$suffix.js", array( 'wp-a11y', 'customize-base' ), null, 1 );
		wp_register_script( 'customize-models', plugin_dir_url( __FILE__ ) . "js/customize-models.js", array( 'underscore', 'backbone' ), null, 1 );
		wp_register_script( 'customize-views', plugin_dir_url( __FILE__ ) . "js/customize-views.js",  array( 'jquery', 'underscore', 'imgareaselect', 'customize-models', 'media-editor', 'media-views' ), null, 1 );
		wp_register_script( 'customize-controls', plugin_dir_url( __FILE__ ) . "js/customize-controls$suffix.js", array( 'customize-base', 'wp-a11y', 'wp-util', 'jquery-ui-core' ), null, 1 );

		wp_register_script( 'customize-selective-refresh', plugin_dir_url( __FILE__ ) . "js/customize-selective-refresh$suffix.js", array( 'jquery', 'wp-util', 'customize-preview' ), null, 1 );

		wp_register_script( 'customize-widgets', plugin_dir_url( __FILE__ ) . "js/customize-widgets$suffix.js", array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'wp-backbone', 'customize-controls' ), null, 1 );

		wp_register_script( 'customize-nav-menus', plugin_dir_url( __FILE__ ) . "js/customize-nav-menus$suffix.js", array( 'jquery', 'wp-backbone', 'customize-controls', 'accordion', 'nav-menu' ), null, 1 );
		wp_register_script( 'customize-preview-nav-menus', plugin_dir_url( __FILE__ ) . "js/customize-preview-nav-menus$suffix.js", array( 'jquery', 'wp-util', 'customize-preview', 'customize-selective-refresh' ), null, 1 );
		wp_register_script( 'customize-preview-widgets', plugin_dir_url( __FILE__ ) . "js/customize-preview-widgets$suffix.js", array( 'jquery', 'wp-util', 'customize-preview', 'customize-selective-refresh' ), null, 1 );
	}

	function localize_js_scripts() {
		wp_localize_script( 'customize-controls', '_wpCustomizeControlsL10n', array(
			'activate'           => __( 'Activate &amp; Publish' ),
			'save'               => __( 'Save &amp; Publish' ), // @todo Remove as not required.
			'publish'            => __( 'Publish' ),
			'published'          => __( 'Published' ),
			'saveDraft'          => __( 'Save Draft' ),
			'draftSaved'         => __( 'Draft Saved' ),
			'updating'           => __( 'Updating' ),
			'schedule'           => _x( 'Schedule', 'customizer changeset action/button label' ),
			'scheduled'          => _x( 'Scheduled', 'customizer changeset status' ),
			'invalid'            => __( 'Invalid' ),
			'saveBeforeShare'    => __( 'Please save your changes in order to share the preview.' ),
			'futureDateError'    => __( 'You must supply a future date to schedule.' ),
			'saveAlert'          => __( 'The changes you made will be lost if you navigate away from this page.' ),
			'saved'              => __( 'Saved' ),
			'cancel'             => __( 'Cancel' ),
			'close'              => __( 'Close' ),
			'action'             => __( 'Action' ),
			'discardChanges'     => __( 'Discard changes' ),
			'cheatin'            => __( 'Something went wrong.' ),
			'notAllowedHeading'  => __( 'You need a higher level of permission.' ),
			'notAllowed'         => __( 'Sorry, you are not allowed to customize this site.' ),
			'previewIframeTitle' => __( 'Site Preview' ),
			'loginIframeTitle'   => __( 'Session expired' ),
			'collapseSidebar'    => _x( 'Hide Controls', 'label for hide controls button without length constraints' ),
			'expandSidebar'      => _x( 'Show Controls', 'label for hide controls button without length constraints' ),
			'untitledBlogName'   => __( '(Untitled)' ),
			'unknownRequestFail' => __( 'Looks like something&#8217;s gone wrong. Wait a couple seconds, and then try again.' ),
			'themeDownloading'   => __( 'Downloading your new theme&hellip;' ),
			'themePreviewWait'   => __( 'Setting up your live preview. This may take a bit.' ),
			'revertingChanges'   => __( 'Reverting unpublished changes&hellip;' ),
			'trashConfirm'       => __( 'Are you sure you&#8217;d like to discard your unpublished changes?' ),
			/* translators: %s: Display name of the user who has taken over the changeset in customizer. */
			'takenOverMessage'   => __( '%s has taken over and is currently customizing.' ),
			/* translators: %s: URL to the Customizer to load the autosaved version */
			'autosaveNotice'     => __( 'There is a more recent autosave of your changes than the one you are previewing. <a href="%s">Restore the autosave</a>' ),
			'videoHeaderNotice'  => __( 'This theme doesn&#8217;t support video headers on this page. Navigate to the front page or another page that supports video headers.' ),
			// Used for overriding the file types allowed in plupload.
			'allowedFiles'       => __( 'Allowed Files' ),
			'customCssError'     => array(
				/* translators: %d: error count */
				'singular' => _n( 'There is %d error which must be fixed before you can save.', 'There are %d errors which must be fixed before you can save.', 1 ),
				/* translators: %d: error count */
				'plural'   => _n( 'There is %d error which must be fixed before you can save.', 'There are %d errors which must be fixed before you can save.', 2 ), // @todo This is lacking, as some languages have a dedicated dual form. For proper handling of plurals in JS, see https://core.trac.wordpress.org/ticket/20491.
			),
			'pageOnFrontError' => __( 'Homepage and posts page must be different.' ),
			'saveBlockedError' => array(
				/* translators: %s: number of invalid settings */
				'singular' => _n( 'Unable to save due to %s invalid setting.', 'Unable to save due to %s invalid settings.', 1 ),
				/* translators: %s: number of invalid settings */
				'plural'   => _n( 'Unable to save due to %s invalid setting.', 'Unable to save due to %s invalid settings.', 2 ), // @todo This is lacking, as some languages have a dedicated dual form. For proper handling of plurals in JS, see https://core.trac.wordpress.org/ticket/20491.
			),
			'scheduleDescription' => __( 'Schedule your customization changes to publish ("go live") at a future date.' ),
			'themePreviewUnavailable' => __( 'Sorry, you can&#8217;t preview new themes when you have changes scheduled or saved as a draft. Please publish your changes, or wait until they publish to preview new themes.' ),
			'themeInstallUnavailable' => sprintf(
				/* translators: %s: URL to Add Themes admin screen */
				__( 'You won&#8217;t be able to install new themes from here yet since your install requires SFTP credentials. For now, please <a href="%s">add themes in the admin</a>.' ),
				esc_url( admin_url( 'theme-install.php' ) )
			),
			'publishSettings' => __( 'Publish Settings' ),
			'invalidDate'     => __( 'Invalid date.' ),
			'invalidValue'    => __( 'Invalid value.' ),
		) );
	}

	function register_css() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'customize-preview', plugin_dir_url( __FILE__ ) . "css/customize-preview$suffix.css", array( 'dashicons' ) );
		if ( is_admin() ) {
			wp_register_style( 'customize-controls', plugin_dir_url( __FILE__ ) . "css/customize-controls$suffix.css", array( 'wp-admin', 'colors', 'imgareaselect' ) );
			wp_register_style( 'customize-widgets', plugin_dir_url( __FILE__ ) . "css/customize-widgets$suffix.css", array( 'wp-admin', 'colors' ) );
			wp_register_style( 'customize-nav-menus', plugin_dir_url( __FILE__ ) . "css/customize-nav-menus$suffix.css", array( 'wp-admin', 'colors' ) );

		}

		$rtl_styles = array(
			'customize-controls',
			'customize-widgets',
			'customize-nav-menus',
			'customize-preview'
		);

		foreach ( $rtl_styles as $rtl_style ) {
			wp_style_add_data( $rtl_style, 'rtl', 'replace' );
			if ( $suffix ) {
				wp_style_add_data( $rtl_style, 'suffix', $suffix );
			}
		}
	}
}

global $core_plugin_customizer;
$core_plugin_customizer = new Core_Plugin_Customizer();


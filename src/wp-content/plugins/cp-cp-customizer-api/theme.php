<?php
/*
 * Theme functions for Customizer
 */

/**
 * Publishes a snapshot's changes.
 *
 * @since WP-4.7.0
 * @access private
 *
 * @global wpdb                 $wpdb         ClassicPress database abstraction object.
 * @global WP_Customize_Manager $wp_customize Customizer instance.
 *
 * @param string  $new_status     New post status.
 * @param string  $old_status     Old post status.
 * @param WP_Post $changeset_post Changeset post object.
 */
function _wp_customize_publish_changeset( $new_status, $old_status, $changeset_post ) {
	global $wp_customize, $wpdb;

	$is_publishing_changeset = (
		'customize_changeset' === $changeset_post->post_type
		&&
		'publish' === $new_status
		&&
		'publish' !== $old_status
	);
	if ( ! $is_publishing_changeset ) {
		return;
	}

	if ( empty( $wp_customize ) ) {
		require_once CUSTOMIZEPATH . 'class-wp-customize-manager.php';
		$wp_customize = new WP_Customize_Manager( array(
			'changeset_uuid' => $changeset_post->post_name,
			'settings_previewed' => false,
		) );
	}

	if ( ! did_action( 'customize_register' ) ) {
		/*
		 * When running from CLI or Cron, the customize_register action will need
		 * to be triggered in order for core, themes, and plugins to register their
		 * settings. Normally core will add_action( 'customize_register' ) at
		 * priority 10 to register the core settings, and if any themes/plugins
		 * also add_action( 'customize_register' ) at the same priority, they
		 * will have a $wp_customize with those settings registered since they
		 * call add_action() afterward, normally. However, when manually doing
		 * the customize_register action after the setup_theme, then the order
		 * will be reversed for two actions added at priority 10, resulting in
		 * the core settings no longer being available as expected to themes/plugins.
		 * So the following manually calls the method that registers the core
		 * settings up front before doing the action.
		 */
		remove_action( 'customize_register', array( $wp_customize, 'register_controls' ) );
		$wp_customize->register_controls();

		/** This filter is documented in /wp-includes/class-wp-customize-manager.php */
		do_action( 'customize_register', $wp_customize );
	}
	$wp_customize->_publish_changeset_values( $changeset_post->ID ) ;

	/*
	 * Trash the changeset post if revisions are not enabled. Unpublished
	 * changesets by default get garbage collected due to the auto-draft status.
	 * When a changeset post is published, however, it would no longer get cleaned
	 * out. Ths is a problem when the changeset posts are never displayed anywhere,
	 * since they would just be endlessly piling up. So here we use the revisions
	 * feature to indicate whether or not a published changeset should get trashed
	 * and thus garbage collected.
	 */
	if ( ! wp_revisions_enabled( $changeset_post ) ) {
		$wp_customize->trash_changeset_post( $changeset_post->ID );
	}
}

/**
 * Filters changeset post data upon insert to ensure post_name is intact.
 *
 * This is needed to prevent the post_name from being dropped when the post is
 * transitioned into pending status by a contributor.
 *
 * @since WP-4.7.0
 * @see wp_insert_post()
 *
 * @param array $post_data          An array of slashed post data.
 * @param array $supplied_post_data An array of sanitized, but otherwise unmodified post data.
 * @returns array Filtered data.
 */
function _wp_customize_changeset_filter_insert_post_data( $post_data, $supplied_post_data ) {
	if ( isset( $post_data['post_type'] ) && 'customize_changeset' === $post_data['post_type'] ) {

		// Prevent post_name from being dropped, such as when contributor saves a changeset post as pending.
		if ( empty( $post_data['post_name'] ) && ! empty( $supplied_post_data['post_name'] ) ) {
			$post_data['post_name'] = $supplied_post_data['post_name'];
		}
	}
	return $post_data;
}

/**
 * Adds settings for the customize-loader script.
 *
 * @since WP-3.4.0
 */
function _wp_customize_loader_settings() {

	$admin_origin = parse_url( admin_url() );
	$home_origin  = parse_url( home_url() );
	$cross_domain = ( strtolower( $admin_origin[ 'host' ] ) != strtolower( $home_origin[ 'host' ] ) );

	$browser = array(
		'mobile' => wp_is_mobile(),
		'ios'    => wp_is_mobile() && preg_match( '/iPad|iPod|iPhone/', $_SERVER['HTTP_USER_AGENT'] ),
	);

	$settings = array(
		'url'           => esc_url( admin_url( 'customize.php' ) ),
		'isCrossDomain' => $cross_domain,
		'browser'       => $browser,
		'l10n'          => array(
			'saveAlert'       => __( 'The changes you made will be lost if you navigate away from this page.' ),
			'mainIframeTitle' => __( 'Customizer' ),
		),
	);

	$script = 'var _wpCustomizeLoaderSettings = ' . wp_json_encode( $settings ) . ';';

	$wp_scripts = wp_scripts();
	$data = $wp_scripts->get_data( 'customize-loader', 'data' );
	if ( $data )
		$script = "$data\n$script";

	$wp_scripts->add_data( 'customize-loader', 'data', $script );
}

/**
 * Returns a URL to load the Customizer.
 *
 * @since WP-3.4.0
 *
 * @param string $stylesheet Optional. Theme to customize. Defaults to current theme.
 * 	                         The theme's stylesheet will be urlencoded if necessary.
 * @return string
 */
function wp_customize_url( $stylesheet = null ) {
	$url = admin_url( 'customize.php' );
	if ( $stylesheet )
		$url .= '?theme=' . urlencode( $stylesheet );
	return esc_url( $url );
}

/**
 * Prints a script to check whether or not the Customizer is supported,
 * and apply either the no-customize-support or customize-support class
 * to the body.
 *
 * This function MUST be called inside the body tag.
 *
 * Ideally, call this function immediately after the body tag is opened.
 * This prevents a flash of unstyled content.
 *
 * It is also recommended that you add the "no-customize-support" class
 * to the body tag by default.
 *
 * @since WP-3.4.0
 * @since WP-4.7.0 Support for IE8 and below is explicitly removed via conditional comments.
 */
function wp_customize_support_script() {
	$admin_origin = parse_url( admin_url() );
	$home_origin  = parse_url( home_url() );
	$cross_domain = ( strtolower( $admin_origin[ 'host' ] ) != strtolower( $home_origin[ 'host' ] ) );

	?>
	<!--[if lte IE 8]>
		<script type="text/javascript">
			document.body.className = document.body.className.replace( /(^|\s)(no-)?customize-support(?=\s|$)/, '' ) + ' no-customize-support';
		</script>
	<![endif]-->
	<!--[if gte IE 9]><!-->
		<script type="text/javascript">
			(function() {
				var request, b = document.body, c = 'className', cs = 'customize-support', rcs = new RegExp('(^|\\s+)(no-)?'+cs+'(\\s+|$)');

		<?php	if ( $cross_domain ) : ?>
				request = (function(){ var xhr = new XMLHttpRequest(); return ('withCredentials' in xhr); })();
		<?php	else : ?>
				request = true;
		<?php	endif; ?>

				b[c] = b[c].replace( rcs, ' ' );
				// The customizer requires postMessage and CORS (if the site is cross domain)
				b[c] += ( window.postMessage && request ? ' ' : ' no-' ) + cs;
			}());
		</script>
	<!--<![endif]-->
	<?php
}

/**
 * Whether the site is being previewed in the Customizer.
 *
 * @since WP-4.0.0
 *
 * @global WP_Customize_Manager $wp_customize Customizer instance.
 *
 * @return bool True if the site is being previewed in the Customizer, false otherwise.
 */
if ( ! function_exists( 'is_customize_preview' ) ) :
function is_customize_preview() {
	global $wp_customize;

	return ( $wp_customize instanceof WP_Customize_Manager ) && $wp_customize->is_preview();
}
endif;
/**
 * Make sure that auto-draft posts get their post_date bumped or status changed to draft to prevent premature garbage-collection.
 *
 * When a changeset is updated but remains an auto-draft, ensure the post_date
 * for the auto-draft posts remains the same so that it will be
 * garbage-collected at the same time by `wp_delete_auto_drafts()`. Otherwise,
 * if the changeset is updated to be a draft then update the posts
 * to have a far-future post_date so that they will never be garbage collected
 * unless the changeset post itself is deleted.
 *
 * When a changeset is updated to be a persistent draft or to be scheduled for
 * publishing, then transition any dependent auto-drafts to a draft status so
 * that they likewise will not be garbage-collected but also so that they can
 * be edited in the admin before publishing since there is not yet a post/page
 * editing flow in the Customizer. See https://core.trac.wordpress.org/ticket/39752.
 *
 * @link https://core.trac.wordpress.org/ticket/39752
 *
 * @since WP-4.8.0
 * @access private
 * @see wp_delete_auto_drafts()
 *
 * @param string   $new_status Transition to this post status.
 * @param string   $old_status Previous post status.
 * @param \WP_Post $post       Post data.
 * @global wpdb $wpdb
 */
function _wp_keep_alive_customize_changeset_dependent_auto_drafts( $new_status, $old_status, $post ) {
	global $wpdb;
	unset( $old_status );

	// Short-circuit if not a changeset or if the changeset was published.
	if ( 'customize_changeset' !== $post->post_type || 'publish' === $new_status ) {
		return;
	}

	$data = json_decode( $post->post_content, true );
	if ( empty( $data['nav_menus_created_posts']['value'] ) ) {
		return;
	}

	/*
	 * Actually, in lieu of keeping alive, trash any customization drafts here if the changeset itself is
	 * getting trashed. This is needed because when a changeset transitions to a draft, then any of the
	 * dependent auto-draft post/page stubs will also get transitioned to customization drafts which
	 * are then visible in the WP Admin. We cannot wait for the deletion of the changeset in which
	 * _wp_delete_customize_changeset_dependent_auto_drafts() will be called, since they need to be
	 * trashed to remove from visibility immediately.
	 */
	if ( 'trash' === $new_status ) {
		foreach ( $data['nav_menus_created_posts']['value'] as $post_id ) {
			if ( ! empty( $post_id ) && 'draft' === get_post_status( $post_id ) ) {
				wp_trash_post( $post_id );
			}
		}
		return;
	}

	$post_args = array();
	if ( 'auto-draft' === $new_status ) {
		/*
		 * Keep the post date for the post matching the changeset
		 * so that it will not be garbage-collected before the changeset.
		 */
		$post_args['post_date'] = $post->post_date; // Note wp_delete_auto_drafts() only looks at this date.
	} else {
		/*
		 * Since the changeset no longer has an auto-draft (and it is not published)
		 * it is now a persistent changeset, a long-lived draft, and so any
		 * associated auto-draft posts should likewise transition into having a draft
		 * status. These drafts will be treated differently than regular drafts in
		 * that they will be tied to the given changeset. The publish metabox is
		 * replaced with a notice about how the post is part of a set of customized changes
		 * which will be published when the changeset is published.
		 */
		$post_args['post_status'] = 'draft';
	}

	foreach ( $data['nav_menus_created_posts']['value'] as $post_id ) {
		if ( empty( $post_id ) || 'auto-draft' !== get_post_status( $post_id ) ) {
			continue;
		}
		$wpdb->update(
			$wpdb->posts,
			$post_args,
			array( 'ID' => $post_id )
		);
		clean_post_cache( $post_id );
	}
}

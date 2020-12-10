<?php
/**
 * Theme Customize Screen.
 *
 * @package ClassicPress
 * @subpackage Customize
 * @since WP-3.4.0
 */

define( 'IFRAME_REQUEST', true );

/** Load ClassicPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

if ( ! current_user_can( 'customize' ) ) {
	wp_die(
		'<h1>' . __( 'You need a higher level of permission.' ) . '</h1>' .
		'<p>' . __( 'Sorry, you are not allowed to customize this site.' ) . '</p>',
		403
	);
}

do_action( 'customize_admin' );

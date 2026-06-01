<?php
/**
 * Plugin Name:       FluentFlow — Page Builder Bridge
 * Plugin URI:        https://fluentflow.io
 * Description:       Modular extension framework for Bricks Builder & Elementor with dynamic tags, shortcodes, glassmorphic admin UI, and Pro licensing.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:            FluentFlow
 * Author URI:        https://fluentflow.io
 * License:           GPL-2.0-or-later
 * Text Domain:       fluentflow-bricks-bridge
 */

defined( 'ABSPATH' ) || exit;

define( 'FFBB_VERSION', '1.0.0' );
define( 'FFBB_FILE', __FILE__ );
define( 'FFBB_DIR', __DIR__ );
define( 'FFBB_URL', plugin_dir_url( __FILE__ ) );
define( 'FFBB_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload classes using fully-qualified class names.
 */
spl_autoload_register( function ( $class ) {
	$prefix   = 'FluentFlow\\';
	$base_dir = FFBB_DIR . '/inc/';

	if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$relative_class = str_replace( '\\', '/', $relative_class );
	$slug           = strtolower( str_replace( '_', '-', $relative_class ) );

	$candidates = [
		$base_dir . "class-{$slug}.php",
		$base_dir . "interface-{$slug}.php",
		$base_dir . "trait-{$slug}.php",
		$base_dir . "{$slug}.php",
	];

	foreach ( $candidates as $file ) {
		if ( file_exists( $file ) ) {
			require $file;
			return;
		}
	}
} );

/**
 * Detect active page builders.
 */
function ffbb_is_bricks_active(): bool {
	if ( defined( 'BRICKS_VERSION' ) ) {
		return true;
	}
	$theme = wp_get_theme();
	if ( 'bricks' === strtolower( $theme->template ?? '' )
		|| 'bricks' === strtolower( $theme->stylesheet ?? '' )
	) {
		return true;
	}
	return false;
}

function ffbb_is_elementor_active(): bool {
	return defined( 'ELEMENTOR_VERSION' );
}

/**
 * Debug logging helper.
 *
 * Usage: add_action( 'ffbb_debug', function( $msg ) { error_log( "[FFBB] {$msg}" ); } );
 * Or define WP_DEBUG and Enable WP_DEBUG_LOG in wp-config.php, then watch debug.log.
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	add_action( 'ffbb_debug', function ( $msg ) {
		error_log( '[FFBB] ' . $msg );
	} );
}

/**
 * Bootstrap the plugin.
 *
 * Always runs so shortcodes are available everywhere.
 * Builder modules self-register only when their builder is detected.
 */
add_action( 'init', function () {
	$registry = \FluentFlow\Core_Registry::instance();
	$registry->boot();
}, 5 );

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, function () {
	$registry = \FluentFlow\Core_Registry::instance();
	$registry->activate();
} );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, function () {
	$registry = \FluentFlow\Core_Registry::instance();
	$registry->deactivate();
} );

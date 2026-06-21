<?php
/**
 * Plugin Name:       FluentFlow — Page Builder Bridge
 * Plugin URI:        https://fluentflow.io
 * Description:       Minimal FluentCart data bridge for Bricks Builder and page-builder dynamic design control.
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
 * PSR-4 autoloader for FluentFlow classes.
 */
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'FluentFlow\\';

	if ( 0 !== strncmp( $class, $prefix, strlen( $prefix ) ) ) {
		return;
	}

	$relative = substr( $class, strlen( $prefix ) );
	$file     = FFBB_DIR . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

	if ( is_readable( $file ) ) {
		require_once $file;
	}
} );

require_once FFBB_DIR . '/includes/compat.php';

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
	$registry = \FluentFlow\Core\Plugin::instance();
	$registry->boot();
}, 5 );

/**
 * Activation hook.
 */
register_activation_hook( __FILE__, function () {
	$registry = \FluentFlow\Core\Plugin::instance();
	$registry->activate();
} );

/**
 * Deactivation hook.
 */
register_deactivation_hook( __FILE__, function () {
	$registry = \FluentFlow\Core\Plugin::instance();
	$registry->deactivate();
} );

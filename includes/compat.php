<?php
defined( 'ABSPATH' ) || exit;

$ffbb_aliases = [
	'FluentFlow\Core_Registry'     => 'FluentFlow\Core\Plugin',
	'FluentFlow\Data_Fetcher'      => 'FluentFlow\Data\DataFetcher',
	'FluentFlow\Shortcodes'        => 'FluentFlow\Shortcodes\Shortcodes',
	'FluentFlow\Admin_Dashboard'   => 'FluentFlow\Admin\AdminDashboard',
	'FluentFlow\Feature_Interface' => 'FluentFlow\Contracts\FeatureInterface',
	'FluentFlow\Module_Bricks'     => 'FluentFlow\Integrations\Bricks\BricksModule',
	'FluentFlow\Module_Elementor'  => 'FluentFlow\Integrations\Elementor\ElementorModule',
	'FluentFlow\Module_Overrides'  => 'FluentFlow\Integrations\Bricks\TemplateOverridesModule',
	'FluentFlow\Module_Pro'        => 'FluentFlow\Licensing\ProModule',
];

foreach ( $ffbb_aliases as $legacy => $modern ) {
	if ( class_exists( $modern ) || interface_exists( $modern ) ) {
		class_alias( $modern, $legacy );
	}
}

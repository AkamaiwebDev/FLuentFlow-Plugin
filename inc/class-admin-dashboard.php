<?php
namespace FluentFlow;

defined( 'ABSPATH' ) || exit;

final class Admin_Dashboard {

	private static ?self $instance = null;

	private const PARENT_SLUG   = 'fluentflow';
	private const CAPABILITY    = 'manage_options';
	private const OPTION_KEY    = 'ffbb_settings';

	private array $defaults = [
		'modules'               => [],
		'license_key'           => '',
	];

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'add_admin_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_ffbb_save_settings', [ $this, 'handle_save' ] );
		add_action( 'admin_post_ffbb_save_license', [ $this, 'handle_license_save' ] );
	}

	public function add_admin_menus(): void {
		add_menu_page(
			__( 'FluentFlow', 'fluentflow-bricks-bridge' ),
			__( 'FluentFlow', 'fluentflow-bricks-bridge' ),
			self::CAPABILITY,
			self::PARENT_SLUG,
			[ $this, 'render_settings_page' ],
			'dashicons-admin-generic',
			30
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Settings', 'fluentflow-bricks-bridge' ),
			__( 'Settings', 'fluentflow-bricks-bridge' ),
			self::CAPABILITY,
			self::PARENT_SLUG,
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Tags Reference', 'fluentflow-bricks-bridge' ),
			__( 'Tags', 'fluentflow-bricks-bridge' ),
			self::CAPABILITY,
			'fluentflow-tags',
			[ $this, 'render_tags_page' ]
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'License', 'fluentflow-bricks-bridge' ),
			__( 'License', 'fluentflow-bricks-bridge' ),
			self::CAPABILITY,
			'fluentflow-license',
			[ $this, 'render_license_page' ]
		);
	}

	public function enqueue_assets( string $hook_suffix ): void {
		$allowed = [
			'toplevel_page_' . self::PARENT_SLUG,
			'fluentflow_page_fluentflow-tags',
			'fluentflow_page_fluentflow-license',
		];

		if ( ! in_array( $hook_suffix, $allowed, true ) ) {
			return;
		}

		wp_enqueue_style(
			'ffbb-admin',
			FFBB_URL . 'assets/css/admin-dashboard-style.css',
			[],
			FFBB_VERSION
		);

		wp_enqueue_script(
			'ffbb-admin',
			FFBB_URL . 'assets/js/admin-dashboard.js',
			[],
			FFBB_VERSION,
			true
		);
	}

	public function render_settings_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'fluentflow-bricks-bridge' ) );
		}

		$settings = get_option( self::OPTION_KEY, $this->defaults );
		$registry = Core_Registry::instance();

		require FFBB_DIR . '/inc/views/admin-settings.php';
	}

	public function render_tags_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'fluentflow-bricks-bridge' ) );
		}

		$token_groups = Data_Fetcher::get_token_groups();
		require FFBB_DIR . '/inc/views/admin-tags.php';
	}

	public function render_license_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions.', 'fluentflow-bricks-bridge' ) );
		}

		$settings = get_option( self::OPTION_KEY, $this->defaults );
		$license  = $settings['license_key'] ?? '';
		$pro      = Core_Registry::instance()->get_module( 'pro' );
		$status   = $pro ? $pro->is_licensed() : false;

		require FFBB_DIR . '/inc/views/admin-license.php';
	}

	public function handle_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'fluentflow-bricks-bridge' ) );
		}

		check_admin_referer( 'ffbb_save_settings', 'ffbb_nonce' );

		$registry = Core_Registry::instance();
		$modules  = [];
		foreach ( $registry->get_modules() as $module ) {
			$modules[ $module->get_id() ] = isset( $_POST['modules'][ $module->get_id() ] ) ? 1 : 0;
		}

		$existing                = get_option( self::OPTION_KEY, $this->defaults );
		$existing['modules']     = $modules;
		$existing['license_key'] = isset( $_POST['license_key'] )
			? sanitize_text_field( wp_unslash( $_POST['license_key'] ) )
			: '';

		update_option( self::OPTION_KEY, $existing );

		$overrides = [];
		if ( isset( $_POST['overrides'] ) && is_array( $_POST['overrides'] ) ) {
			foreach ( $_POST['overrides'] as $key => $val ) {
				$overrides[ sanitize_key( $key ) ] = absint( $val );
			}
		}
		update_option( Module_Overrides::OVERRIDES_OPTION, $overrides );

		$redirect = add_query_arg(
			[ 'page' => self::PARENT_SLUG, 'updated' => '1' ],
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public function handle_license_save(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'fluentflow-bricks-bridge' ) );
		}

		check_admin_referer( 'ffbb_save_license', 'ffbb_license_nonce' );

		$existing                = get_option( self::OPTION_KEY, $this->defaults );
		$existing['license_key'] = isset( $_POST['license_key'] )
			? sanitize_text_field( wp_unslash( $_POST['license_key'] ) )
			: '';

		update_option( self::OPTION_KEY, $existing );

		delete_transient( \FluentFlow\Module_Pro::VALIDATION_TRANSIENT );

		$redirect = add_query_arg(
			[ 'page' => 'fluentflow-license', 'updated' => '1' ],
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	public static function get_setting( string $key, $default = null ) {
		$settings = get_option( self::OPTION_KEY, [] );
		return $settings[ $key ] ?? $default;
	}

	private function __clone() {}
	public function __wakeup() {
		throw new \RuntimeException( 'Serialization of singleton is not allowed.' );
	}
}

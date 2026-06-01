<?php
namespace FluentFlow;

defined( 'ABSPATH' ) || exit;

final class Core_Registry {

	private static ?self $instance = null;

	/** @var Feature_Interface[] */
	private array $modules = [];

	private array $state = [];

	private function __construct() {
		$this->set_default_state();
	}

	private function set_default_state(): void {
		$this->state = [
			'version'     => FFBB_VERSION,
			'booted'      => false,
			'activated'   => false,
			'module_load' => [],
		];
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		if ( $this->state['booted'] ) {
			return;
		}

		$this->load_built_in_modules();
		$this->init_modules();
		$this->register_hooks();

		$this->state['booted'] = true;

		do_action( 'ffbb_booted', $this );
	}

	private function load_built_in_modules(): void {
		$modules = [
			'Bricks'    => __DIR__ . '/modules/class-module-bricks.php',
			'Elementor' => __DIR__ . '/modules/class-module-elementor.php',
			'Overrides' => __DIR__ . '/modules/class-module-overrides.php',
			'Pro'       => __DIR__ . '/modules/class-module-pro.php',
		];

		foreach ( $modules as $name => $file ) {
			if ( ! file_exists( $file ) ) {
				continue;
			}
			require_once $file;
			$class = __NAMESPACE__ . "\\Module_{$name}";
			if ( class_exists( $class ) ) {
				$this->register_module( new $class() );
			}
		}
	}

	public function register_module( Feature_Interface $module ): void {
		$id = $module->get_id();
		if ( isset( $this->modules[ $id ] ) ) {
			return;
		}
		$this->modules[ $id ] = $module;
		$this->state['module_load'][ $id ] = false;
	}

	public function deregister_module( string $id ): void {
		if ( ! isset( $this->modules[ $id ] ) ) {
			return;
		}
		$this->modules[ $id ]->deactivate();
		unset( $this->modules[ $id ] );
		unset( $this->state['module_load'][ $id ] );
	}

	private function init_modules(): void {
		foreach ( $this->modules as $id => $module ) {
			$module->init();
			$this->state['module_load'][ $id ] = true;
		}
	}

	private function register_hooks(): void {
		Shortcodes::instance()->init();

		add_action( 'wp_ajax_ffbb_cart_data', [ Data_Fetcher::class, 'handle_ajax_cart_data' ] );
		add_action( 'wp_ajax_nopriv_ffbb_cart_data', [ Data_Fetcher::class, 'handle_ajax_cart_data' ] );

		if ( is_admin() ) {
			Admin_Dashboard::instance()->init();
		}
	}

	public function get_module( string $id ): ?Feature_Interface {
		return $this->modules[ $id ] ?? null;
	}

	public function get_modules(): array {
		return $this->modules;
	}

	public function get_state( ?string $key = null ) {
		if ( null === $key ) {
			return $this->state;
		}
		return $this->state[ $key ] ?? null;
	}

	public function set_state( string $key, $value ): void {
		$this->state[ $key ] = $value;
	}

	public function activate(): void {
		$this->state['activated'] = true;
		if ( empty( $this->modules ) ) {
			$this->load_built_in_modules();
		}
		foreach ( $this->modules as $module ) {
			$module->activate();
		}
	}

	public function deactivate(): void {
		$this->state['activated'] = false;
		if ( empty( $this->modules ) ) {
			$this->load_built_in_modules();
		}
		foreach ( $this->modules as $module ) {
			$module->deactivate();
		}
	}

	private function __clone() {}
	public function __wakeup() {
		throw new \RuntimeException( 'Serialization of singleton is not allowed.' );
	}
}

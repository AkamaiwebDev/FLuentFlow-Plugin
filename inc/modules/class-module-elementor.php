<?php
namespace FluentFlow;

defined( 'ABSPATH' ) || exit;

class Module_Elementor implements Feature_Interface {

	public function get_id(): string {
		return 'elementor';
	}

	public function get_name(): string {
		return __( 'Elementor Tags', 'fluentflow-bricks-bridge' );
	}

	public function get_description(): string {
		return __( 'Register custom dynamic tags for Elementor Builder.', 'fluentflow-bricks-bridge' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function get_icon(): string {
		return '🎨';
	}

	public function init(): void {
		do_action( 'ffbb_debug', 'Module_Elementor::init — registering hooks' );

		$settings = Admin_Dashboard::get_setting( 'modules', [] );
		$enabled  = $settings['elementor'] ?? '0';
		if ( empty( $enabled ) ) {
			do_action( 'ffbb_debug', 'Module_Elementor::init — toggled OFF in settings, skipping' );
			return;
		}

		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		add_action( 'elementor/dynamic_tags/register_groups', [ $this, 'register_group' ] );
		add_action( 'elementor/dynamic_tags/register', [ $this, 'register_tags' ] );
	}

	public function register_group( $groups ): void {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}
		$groups->register_group( 'fluentflow', [
			'title' => __( 'FluentFlow', 'fluentflow-bricks-bridge' ),
		] );
	}

	public function register_tags( $dynamic_tags ): void {
		if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
			return;
		}

		if ( ! class_exists( '\Elementor\Core\DynamicTags\Tag' ) ) {
			return;
		}

		$tokens = Data_Fetcher::get_token_registry();

		foreach ( $tokens as $token => $info ) {
			try {
				$tag = new class( $token, $info ) extends \Elementor\Core\DynamicTags\Tag {
					private string $token;
					private array  $info;

					public function __construct( string $token, array $info ) {
						parent::__construct( [ 'token' => $token, 'info' => $info ] );
						$this->token = $token;
						$this->info  = $info;
					}

					public function get_name(): string {
						return str_replace( [ '{', '}' ], '', $this->token );
					}

					public function get_title(): string {
						return $this->info['label'] ?? $this->token;
					}

					public function get_group(): string {
						return 'fluentflow';
					}

					public function get_categories(): array {
						return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
					}

					public function render(): void {
						echo Data_Fetcher::resolve( $this->token );
					}
				};

				$dynamic_tags->register( $tag );
			} catch ( \Throwable $e ) {
				do_action( 'ffbb_debug', "Module_Elementor::register_tags — error registering {$token}: {$e->getMessage()}" );
			}
		}

		do_action( 'ffbb_debug', 'Module_Elementor::register_tags — registered ' . count( $tokens ) . ' tags' );
	}

	public function activate(): void {
	}

	public function deactivate(): void {
		remove_action( 'elementor/dynamic_tags/register_groups', [ $this, 'register_group' ] );
		remove_action( 'elementor/dynamic_tags/register', [ $this, 'register_tags' ] );
	}
}

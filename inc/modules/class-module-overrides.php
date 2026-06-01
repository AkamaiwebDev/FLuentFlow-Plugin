<?php
namespace FluentFlow;

defined( 'ABSPATH' ) || exit;

class Module_Overrides implements Feature_Interface {

	const OVERRIDES_OPTION = 'ffbb_template_overrides';

	private array $page_type_map = [
		'cart'              => 'cart',
		'checkout'          => 'checkout',
		'customer_dashboard' => 'customer_dashboard',
	];

	private static bool $is_rendering_override = false;

	public function get_id(): string {
		return 'overrides';
	}

	public function get_name(): string {
		return __( 'Template Overrides', 'fluentflow-bricks-bridge' );
	}

	public function get_description(): string {
		return __( 'Override FluentCart Cart, Checkout, and Customer Dashboard pages with Bricks templates.', 'fluentflow-bricks-bridge' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function get_icon(): string {
		return '🔄';
	}

	public function init(): void {
		$settings = Admin_Dashboard::get_setting( 'modules', [] );
		$enabled  = $settings['overrides'] ?? '1';
		if ( empty( $enabled ) ) {
			return;
		}

		add_filter( 'the_content', [ $this, 'override_page_content' ], 99999 );
	}

	public function activate(): void {
	}

	public function deactivate(): void {
		remove_filter( 'the_content', [ $this, 'override_page_content' ], 99999 );
	}

	public function override_page_content( $content ): string {
		if ( self::$is_rendering_override ) {
			return $content;
		}

		if ( ! in_the_loop() || ! is_main_query() || ! defined( 'BRICKS_VERSION' ) ) {
			return $content;
		}

		self::$is_rendering_override = true;

		$page_type = $this->get_current_page_type();
		if ( ! $page_type ) {
			self::$is_rendering_override = false;
			return $content;
		}

		$template_id = $this->get_mapped_template_id( $page_type );
		if ( ! $template_id ) {
			self::$is_rendering_override = false;
			return $content;
		}

		$this->load_fluentcart_assets( $page_type );

		$bricks_content = $this->render_bricks_template( $template_id );
		if ( empty( trim( $bricks_content ) ) ) {
			self::$is_rendering_override = false;
			return $content;
		}

		self::$is_rendering_override = false;
		return $bricks_content;
	}

	private function get_current_page_type(): ?string {
		if ( ! class_exists( '\FluentCart\App\Services\TemplateService' ) ) {
			return null;
		}

		$page_type = \FluentCart\App\Services\TemplateService::getCurrentFcPageType();

		return $this->page_type_map[ $page_type ] ?? null;
	}

	private function get_mapped_template_id( string $page_type ): int {
		$overrides = get_option( self::OVERRIDES_OPTION, [] );
		$key       = $page_type . '_template_id';
		return absint( $overrides[ $key ] ?? 0 );
	}

	private function load_fluentcart_assets( string $page_type ): void {
		if ( ! class_exists( '\FluentCart\App\Modules\Templating\AssetLoader' ) ) {
			return;
		}

		switch ( $page_type ) {
			case 'checkout':
				\FluentCart\App\Modules\Templating\AssetLoader::loadCheckoutAssets();
				break;
			case 'cart':
				\FluentCart\App\Modules\Templating\AssetLoader::loadCartAssets();
				break;
			case 'customer_dashboard':
				\FluentCart\App\Modules\Templating\AssetLoader::loadCustomerDashboardGlobalAssets();
				\FluentCart\App\Modules\Templating\AssetLoader::loadCustomerDashboardAssets();
				break;
		}
	}

	private function render_bricks_template( int $template_id ): string {
		$shortcode = '[bricks_template id="' . $template_id . '"]';

		$html = do_shortcode( $shortcode );

		if ( ! empty( trim( $html ) ) ) {
			return $this->resolve_ff_tags( $html );
		}

		if ( class_exists( '\Bricks\Database' ) && class_exists( '\Bricks\Frontend' ) ) {
			$elements = \Bricks\Database::get_data( $template_id );
			if ( is_array( $elements ) && count( $elements ) ) {
				$html = \Bricks\Frontend::render_data( $elements );
				if ( ! empty( trim( $html ) ) ) {
					return $this->resolve_ff_tags( $html );
				}
			}
		}

		return '';
	}

	private function resolve_ff_tags( string $content ): string {
		if ( false === strpos( $content, '{ff_' ) ) {
			return $content;
		}

		return preg_replace_callback(
			'/\{ff_\w+\}/',
			function ( $matches ) {
				return Data_Fetcher::resolve( $matches[0] );
			},
			$content
		);
	}

	public static function get_bricks_templates(): array {
		$templates = get_posts( [
			'post_type'      => 'bricks_template',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$options = [ 0 => __( '— None —', 'fluentflow-bricks-bridge' ) ];
		foreach ( $templates as $t ) {
			$options[ $t->ID ] = $t->post_title . ' (#' . $t->ID . ')';
		}
		return $options;
	}

	public static function get_overrides(): array {
		return get_option( self::OVERRIDES_OPTION, [
			'cart_template_id'               => 0,
			'checkout_template_id'           => 0,
			'customer_dashboard_template_id' => 0,
		] );
	}
}

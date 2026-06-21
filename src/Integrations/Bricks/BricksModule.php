<?php
namespace FluentFlow\Integrations\Bricks;

use FluentFlow\Admin\AdminDashboard;
use FluentFlow\Contracts\FeatureInterface;
use FluentFlow\Data\DataFetcher;
use FluentFlow\DataProvider\CartData;
use FluentFlow\DataProvider\CouponData;
use FluentFlow\DataProvider\CustomerData;
use FluentFlow\DataProvider\SubscriptionData;

defined( 'ABSPATH' ) || exit;

class BricksModule implements FeatureInterface {

	public function get_id(): string {
		return 'bricks';
	}

	public function get_name(): string {
		return __( 'Bricks Tags', 'fluentflow-bricks-bridge' );
	}

	public function get_description(): string {
		return __( 'Register custom dynamic tags and elements for Bricks Builder.', 'fluentflow-bricks-bridge' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function get_icon(): string {
		return '🧱';
	}

	public function init(): void {
		do_action( 'ffbb_debug', 'Module_Bricks::init — registering hooks unconditionally' );

		$settings = AdminDashboard::get_setting( 'modules', [] );
		$enabled  = $settings['bricks'] ?? '1';
		if ( empty( $enabled ) ) {
			do_action( 'ffbb_debug', 'Module_Bricks::init — toggled OFF in settings, skipping' );
			return;
		}

		add_filter( 'bricks/builder/i18n', [ $this, 'add_i18n_strings' ] );
		add_action( 'init', [ $this, 'register_elements' ] );

		add_filter( 'bricks/dynamic_tags_list', [ $this, 'register_dynamic_tags_list' ], 10, 1 );
		add_filter( 'bricks/dynamic_data/render_tag', [ $this, 'render_dynamic_tag' ], 20, 3 );
		add_filter( 'bricks/dynamic_data/render_content', [ $this, 'render_content' ], 20, 3 );
		add_filter( 'bricks/frontend/render_data', [ $this, 'render_content' ], 20, 3 );

		add_filter( 'bricks/setup/control_options', [ $this, 'add_cart_query_type' ], 10, 1 );
		add_filter( 'bricks/query/run', [ $this, 'run_cart_query' ], 10, 2 );
		add_filter( 'bricks/query/loop_object', [ $this, 'set_cart_loop_object' ], 10, 3 );
		add_filter( 'bricks/query/loop_object_id', [ $this, 'set_cart_loop_object_id' ], 10, 3 );
		add_filter( 'bricks/query/loop_object_type', [ $this, 'set_cart_loop_object_type' ], 10, 3 );

		add_filter( 'bricks/setup/control_options', [ $this, 'add_customer_orders_query_type' ], 11, 1 );
		add_filter( 'bricks/query/run', [ $this, 'run_customer_orders_query' ], 11, 2 );
		add_filter( 'bricks/query/loop_object', [ $this, 'set_customer_orders_loop_object' ], 11, 3 );
		add_filter( 'bricks/query/loop_object_id', [ $this, 'set_customer_orders_loop_object_id' ], 11, 3 );
		add_filter( 'bricks/query/loop_object_type', [ $this, 'set_customer_orders_loop_object_type' ], 11, 3 );

		add_filter( 'bricks/setup/control_options', [ $this, 'add_customer_subscription_coupon_query_types' ], 12, 1 );
		add_filter( 'bricks/query/run', [ $this, 'run_customer_subscription_coupon_query' ], 12, 2 );
		add_filter( 'bricks/query/loop_object', [ $this, 'set_customer_subscription_coupon_loop_object' ], 12, 3 );
		add_filter( 'bricks/query/loop_object_id', [ $this, 'set_customer_subscription_coupon_loop_object_id' ], 12, 3 );
		add_filter( 'bricks/query/loop_object_type', [ $this, 'set_customer_subscription_coupon_loop_object_type' ], 12, 3 );
	}

	public function activate(): void {
		if ( defined( 'BRICKS_VERSION' ) ) {
			$this->register_elements();
		}
	}

	public function deactivate(): void {
		remove_filter( 'bricks/builder/i18n', [ $this, 'add_i18n_strings' ] );
		remove_action( 'init', [ $this, 'register_elements' ] );
		remove_filter( 'bricks/dynamic_tags_list', [ $this, 'register_dynamic_tags_list' ], 10 );
		remove_filter( 'bricks/dynamic_data/render_tag', [ $this, 'render_dynamic_tag' ], 20, 3 );
		remove_filter( 'bricks/dynamic_data/render_content', [ $this, 'render_content' ], 20, 3 );
		remove_filter( 'bricks/frontend/render_data', [ $this, 'render_content' ], 20, 3 );

		remove_filter( 'bricks/setup/control_options', [ $this, 'add_cart_query_type' ], 10 );
		remove_filter( 'bricks/query/run', [ $this, 'run_cart_query' ], 10, 2 );
		remove_filter( 'bricks/query/loop_object', [ $this, 'set_cart_loop_object' ], 10, 3 );
		remove_filter( 'bricks/query/loop_object_id', [ $this, 'set_cart_loop_object_id' ], 10, 3 );
		remove_filter( 'bricks/query/loop_object_type', [ $this, 'set_cart_loop_object_type' ], 10, 3 );

		remove_filter( 'bricks/setup/control_options', [ $this, 'add_customer_orders_query_type' ], 11 );
		remove_filter( 'bricks/query/run', [ $this, 'run_customer_orders_query' ], 11, 2 );
		remove_filter( 'bricks/query/loop_object', [ $this, 'set_customer_orders_loop_object' ], 11, 3 );
		remove_filter( 'bricks/query/loop_object_id', [ $this, 'set_customer_orders_loop_object_id' ], 11, 3 );
		remove_filter( 'bricks/query/loop_object_type', [ $this, 'set_customer_orders_loop_object_type' ], 11, 3 );

		remove_filter( 'bricks/setup/control_options', [ $this, 'add_customer_subscription_coupon_query_types' ], 12 );
		remove_filter( 'bricks/query/run', [ $this, 'run_customer_subscription_coupon_query' ], 12, 2 );
		remove_filter( 'bricks/query/loop_object', [ $this, 'set_customer_subscription_coupon_loop_object' ], 12, 3 );
		remove_filter( 'bricks/query/loop_object_id', [ $this, 'set_customer_subscription_coupon_loop_object_id' ], 12, 3 );
		remove_filter( 'bricks/query/loop_object_type', [ $this, 'set_customer_subscription_coupon_loop_object_type' ], 12, 3 );
	}

	public function register_elements(): void {
		add_filter( 'bricks/elements', function ( $elements ) {
			$elements['ffbb_fluent_field'] = [
				'name'     => __( 'Fluent Field', 'fluentflow-bricks-bridge' ),
				'icon'     => 'ti-file',
				'category' => 'fluentflow',
				'tag'      => 'div',
				'controls' => $this->field_controls(),
				'render'   => [ $this, 'render_fluent_field' ],
			];

			$elements['ffbb_data_table'] = [
				'name'     => __( 'Data Table', 'fluentflow-bricks-bridge' ),
				'icon'     => 'ti-layout-grid3',
				'category' => 'fluentflow',
				'tag'      => 'div',
				'controls' => $this->table_controls(),
				'render'   => [ $this, 'render_data_table' ],
			];

			return $elements;
		}, 10, 1 );
	}

	private function field_controls(): array {
		return [
			'fieldKey' => [
				'label' => __( 'Field Key', 'fluentflow-bricks-bridge' ),
				'type'  => 'text',
				'group' => 'general',
			],
			'fallback' => [
				'label'   => __( 'Fallback Text', 'fluentflow-bricks-bridge' ),
				'type'    => 'text',
				'default' => '',
				'group'   => 'general',
			],
			'tag' => [
				'label'   => __( 'HTML Tag', 'fluentflow-bricks-bridge' ),
				'type'    => 'select',
				'options' => [
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
					'h1'   => 'h1',
					'h2'   => 'h2',
					'h3'   => 'h3',
					'h4'   => 'h4',
				],
				'default' => 'div',
				'group'   => 'general',
			],
		];
	}

	private function table_controls(): array {
		return [
			'columns' => [
				'label'   => __( 'Columns', 'fluentflow-bricks-bridge' ),
				'type'    => 'number',
				'min'     => 1,
				'max'     => 12,
				'default' => 3,
				'group'   => 'general',
			],
			'dataSource' => [
				'label'   => __( 'Data Source', 'fluentflow-bricks-bridge' ),
				'type'    => 'text',
				'group'   => 'general',
			],
		];
	}

	public function render_fluent_field( array $element ): void {
		$field_key = $element['settings']['fieldKey'] ?? '';
		$fallback  = $element['settings']['fallback'] ?? '';
		$tag       = $element['settings']['tag'] ?? 'div';

		$value  = DataFetcher::resolve( $field_key );
		$output = '' !== $value ? $value : $fallback;

		printf(
			'<%1$s class="ffbb-fluent-field">%2$s</%1$s>',
			esc_attr( $tag ),
			esc_html( $output )
		);
	}

	public function render_data_table( array $element ): void {
		$columns     = absint( $element['settings']['columns'] ?? 3 );
		$data_source = $element['settings']['dataSource'] ?? '';
		$data        = apply_filters( 'ffbb_data_table_data', [], $data_source );

		echo '<div class="ffbb-data-table" style="display:grid;grid-template-columns:repeat(' . esc_attr( $columns ) . ',1fr);gap:8px;">';
		foreach ( $data as $row ) {
			echo '<div class="ffbb-data-cell">' . esc_html( is_scalar( $row ) ? $row : '' ) . '</div>';
		}
		echo '</div>';
	}

	/**
	 * Register FF tags in Bricks dynamic tags panel.
	 *
	 * FluentCart uses filter 'bricks/dynamic_tags_list' (underscore, not slash)
	 * with an indexed array where each entry has 'name' (WITH braces) and 'label'.
	 *
	 * @param array $tags Existing tags.
	 * @return array
	 */
	public function register_dynamic_tags_list( array $tags ): array {
		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return $tags;
		}

		$tokens = DataFetcher::get_token_registry();
		$count  = 0;

		foreach ( $tokens as $token => $info ) {
			$tags[] = [
				'name'  => $token,
				'label' => $info['label'],
				'group' => esc_html( $info['group'] ?? 'FluentFlow' ),
			];
			$count++;
		}

		do_action( 'ffbb_debug', "Module_Bricks::register_dynamic_tags_list — registered {$count} tags" );

		return $tags;
	}

	/**
	 * Resolve an FF dynamic tag to its live value.
	 *
	 * Hooked into all three Bricks render filters (render_tag, render_content, render_data)
	 * to maximise compatibility. Signature matches FluentCart's DynamicData::renderValue.
	 *
	 * @param mixed         $tag     Tag string e.g. "{ff_product_title}" or a resolved value from another provider.
	 * @param \WP_Post|null $post    Current post object.
	 * @param string        $context Render context (text, html, image, etc.).
	 * @return mixed
	 */
	public function render_dynamic_tag( $tag, $post = null, $context = 'text' ) {
		if ( ! is_string( $tag ) ) {
			return $tag;
		}
		$tag = trim( $tag );

		do_action( 'ffbb_debug', "render_dynamic_tag fired — raw tag: {$tag}" );

		$normalised = $this->normalise_tag( $tag );
		if ( null === $normalised ) {
			return $tag;
		}

		$post_id = 0;
		if ( $post instanceof \WP_Post ) {
			$post_id = $post->ID;
		}

		$result = DataFetcher::resolve( $normalised, $post_id );
		do_action( 'ffbb_debug', "render_dynamic_tag result for {$normalised}: {$result}" );

		return $result;
	}

	/**
	 * Normalise a tag string to the braced token format DataFetcher expects.
	 *
	 * Accepts "ff_product_title" or "{ff_product_title}" or "{ff_product_title:linked}".
	 * Returns "{ff_product_title}" on success, null if not an ff_ tag.
	 */
	private function normalise_tag( string $tag ): ?string {
		$tag = trim( $tag );

		$has_braces      = str_starts_with( $tag, '{' );
		$starts_with_ff  = $has_braces ? str_starts_with( $tag, '{ff_' ) : str_starts_with( $tag, 'ff_' );

		if ( ! $starts_with_ff ) {
			return null;
		}

		$bare = str_replace( [ '{', '}' ], '', $tag );

		$colon_pos = strpos( $bare, ':' );
		if ( false !== $colon_pos ) {
			$bare = substr( $bare, 0, $colon_pos );
		}

		return '{' . $bare . '}';
	}

	/**
	 * Scan content for {ff_...} tags and replace them.
	 *
	 * Hooked into bricks/dynamic_data/render_content and bricks/frontend/render_data
	 * as a catch-all for contexts where individual tag rendering doesn't fire.
	 *
	 * @param mixed         $content Content string potentially containing {ff_...} tags.
	 * @param \WP_Post|null $post    Current post object.
	 * @param string        $context Render context.
	 * @return mixed
	 */
	public function render_content( $content, $post = null, $context = 'text' ) {
		if ( ! is_string( $content ) || false === strpos( $content, '{ff_' ) ) {
			return $content;
		}

		$post_id = 0;
		if ( $post instanceof \WP_Post ) {
			$post_id = $post->ID;
		}

		$content = preg_replace_callback(
			'/\{ff_\w+\}/',
			function ( $matches ) use ( $post_id ) {
				return DataFetcher::resolve( $matches[0], $post_id );
			},
			$content
		);

		return $content;
	}

	public function add_i18n_strings( array $strings ): array {
		if ( ! defined( 'BRICKS_VERSION' ) ) {
			return $strings;
		}
		$strings['fluentflow'] = __( 'FluentFlow', 'fluentflow-bricks-bridge' );
		return $strings;
	}

	/**
	 * Register the Cart Items query type in the Bricks query builder dropdown.
	 */
	public function add_cart_query_type( array $control_options ): array {
		$control_options['queryTypes']['ff_cart_items'] = esc_html__( 'Cart Items', 'fluentflow-bricks-bridge' );
		return $control_options;
	}

	/**
	 * Provide cart items as query results for the ff_cart_items query type.
	 */
	public function run_cart_query( $results, $query ) {
		if ( $query->object_type !== 'ff_cart_items' ) {
			return $results;
		}
		return ( new CartData() )->items();
	}

	/**
	 * Set up the global $post for each cart item so standard WP tags work.
	 */
	public function set_cart_loop_object( $loop_object, $loop_key, $query ) {
		if ( $query->object_type !== 'ff_cart_items' ) {
			return $loop_object;
		}
		$post_id = $loop_object['post_id'] ?? 0;
		if ( $post_id ) {
			global $post;
			$post = get_post( $post_id );
			setup_postdata( $post );
		}
		return $loop_object;
	}

	public function set_cart_loop_object_id( $object_id, $object, $query_id ) {
		$query_object_type = \Bricks\Query::get_query_object_type( $query_id );
		if ( $query_object_type !== 'ff_cart_items' ) {
			return $object_id;
		}
		return $object['post_id'] ?? 0;
	}

	public function set_cart_loop_object_type( $object_type, $object, $query_id ) {
		$query_object_type = \Bricks\Query::get_query_object_type( $query_id );
		if ( $query_object_type !== 'ff_cart_items' ) {
			return $object_type;
		}
		return 'post';
	}

	/**
	 * Register the Customer Orders query type in the Bricks query builder dropdown.
	 */
	public function add_customer_orders_query_type( array $control_options ): array {
		$control_options['queryTypes']['ff_customer_orders'] = esc_html__( 'Customer Orders', 'fluentflow-bricks-bridge' );
		return $control_options;
	}

	/**
	 * Provide customer orders as query results.
	 */
	public function run_customer_orders_query( $results, $query ) {
		if ( $query->object_type !== 'ff_customer_orders' ) {
			return $results;
		}
		return ( new CustomerData() )->orders();
	}

	/**
	 * Set the order context for each loop iteration.
	 */
	public function set_customer_orders_loop_object( $loop_object, $loop_key, $query ) {
		if ( $query->object_type !== 'ff_customer_orders' ) {
			return $loop_object;
		}
		if ( is_object( $loop_object ) && isset( $loop_object->id ) ) {
			$GLOBALS['fc_order'] = $loop_object;
		}
		return $loop_object;
	}

	public function set_customer_orders_loop_object_id( $object_id, $object, $query_id ) {
		$query_object_type = \Bricks\Query::get_query_object_type( $query_id );
		if ( $query_object_type !== 'ff_customer_orders' ) {
			return $object_id;
		}
		return $object->id ?? 0;
	}

	public function set_customer_orders_loop_object_type( $object_type, $object, $query_id ) {
		$query_object_type = \Bricks\Query::get_query_object_type( $query_id );
		if ( $query_object_type !== 'ff_customer_orders' ) {
			return $object_type;
		}
		return null;
	}

	public function add_customer_subscription_coupon_query_types( array $control_options ): array {
		$control_options['queryTypes']['ff_customer']      = esc_html__( 'Customer', 'fluentflow-bricks-bridge' );
		$control_options['queryTypes']['ff_subscriptions'] = esc_html__( 'Subscriptions', 'fluentflow-bricks-bridge' );
		$control_options['queryTypes']['ff_coupons']       = esc_html__( 'Coupons', 'fluentflow-bricks-bridge' );

		return $control_options;
	}

	public function run_customer_subscription_coupon_query( $results, $query ) {
		return match ( $query->object_type ) {
			'ff_customer'      => ( new CustomerData() )->current_as_loop(),
			'ff_subscriptions' => ( new SubscriptionData() )->current_customer_subscriptions(),
			'ff_coupons'       => ( new CouponData() )->all(),
			default            => $results,
		};
	}

	public function set_customer_subscription_coupon_loop_object( $loop_object, $loop_key, $query ) {
		return $loop_object;
	}

	public function set_customer_subscription_coupon_loop_object_id( $object_id, $object, $query_id ) {
		$query_object_type = \Bricks\Query::get_query_object_type( $query_id );

		if ( ! in_array( $query_object_type, [ 'ff_customer', 'ff_subscriptions', 'ff_coupons' ], true ) ) {
			return $object_id;
		}

		return $object->id ?? 0;
	}

	public function set_customer_subscription_coupon_loop_object_type( $object_type, $object, $query_id ) {
		$query_object_type = \Bricks\Query::get_query_object_type( $query_id );

		if ( ! in_array( $query_object_type, [ 'ff_customer', 'ff_subscriptions', 'ff_coupons' ], true ) ) {
			return $object_type;
		}

		return null;
	}
}

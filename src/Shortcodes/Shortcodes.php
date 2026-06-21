<?php
namespace FluentFlow\Shortcodes;

use FluentFlow\Data\DataFetcher;
use FluentFlow\DataProvider\CartData;

defined( 'ABSPATH' ) || exit;

class Shortcodes {

	private static ?self $instance = null;

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		$shortcodes = [
			'ff_product_title'             => [ $this, 'handle_product' ],
			'ff_product_price'             => [ $this, 'handle_product' ],
			'ff_product_sku'               => [ $this, 'handle_product' ],
			'ff_product_stock_status'      => [ $this, 'handle_product' ],
			'ff_product_thumbnail'         => [ $this, 'handle_product' ],
			'ff_product_description'       => [ $this, 'handle_product' ],
			'ff_product_url'               => [ $this, 'handle_product' ],
			'ff_product_min_price'         => [ $this, 'handle_product' ],
			'ff_product_max_price'         => [ $this, 'handle_product' ],
			'ff_product_variation_count'   => [ $this, 'handle_product' ],
			'ff_product_fulfillment_type'  => [ $this, 'handle_product' ],

			'ff_customer_name'             => [ $this, 'handle_customer' ],
			'ff_customer_email'            => [ $this, 'handle_customer' ],
			'ff_customer_ltv'              => [ $this, 'handle_customer' ],
			'ff_customer_order_count'      => [ $this, 'handle_customer' ],
			'ff_customer_first_order_date' => [ $this, 'handle_customer' ],
			'ff_customer_last_order_date'  => [ $this, 'handle_customer' ],
			'ff_customer_aov'              => [ $this, 'handle_customer' ],
			'ff_customer_photo'            => [ $this, 'handle_customer' ],
			'ff_customer_billing'          => [ $this, 'handle_customer' ],
			'ff_customer_shipping'         => [ $this, 'handle_customer' ],

			'ff_order_id'                  => [ $this, 'handle_order' ],
			'ff_order_total'               => [ $this, 'handle_order' ],
			'ff_order_subtotal'            => [ $this, 'handle_order' ],
			'ff_order_status'              => [ $this, 'handle_order' ],
			'ff_order_payment_status'      => [ $this, 'handle_order' ],
			'ff_order_payment_method'      => [ $this, 'handle_order' ],
			'ff_order_currency'            => [ $this, 'handle_order' ],
			'ff_order_item_count'          => [ $this, 'handle_order' ],
			'ff_order_receipt_number'      => [ $this, 'handle_order' ],
			'ff_order_date'                => [ $this, 'handle_order' ],
			'ff_order_invoice_no'          => [ $this, 'handle_order' ],
			'ff_order_uuid'                => [ $this, 'handle_order' ],
			'ff_order_type'                => [ $this, 'handle_order' ],

			'ff_cart_item_count'           => [ $this, 'handle_cart' ],
			'ff_cart_total'                => [ $this, 'handle_cart' ],
			'ff_cart_subtotal'             => [ $this, 'handle_cart' ],
			'ff_cart_items_table'          => [ $this, 'handle_cart' ],
			'ff_cart_item_id'              => [ $this, 'handle_cart' ],
			'ff_cart_item_name'            => [ $this, 'handle_cart' ],
			'ff_cart_item_variation'       => [ $this, 'handle_cart' ],
			'ff_cart_item_image'           => [ $this, 'handle_cart' ],
			'ff_cart_item_price'           => [ $this, 'handle_cart' ],
			'ff_cart_item_quantity'        => [ $this, 'handle_cart' ],
			'ff_cart_item_subtotal'        => [ $this, 'handle_cart' ],
			'ff_cart_item_url'             => [ $this, 'handle_cart' ],
			'ff_cart_item_remove'          => [ $this, 'handle_cart' ],

			'ff_subscription_status'       => [ $this, 'handle_subscription' ],
			'ff_subscription_recurring'    => [ $this, 'handle_subscription' ],
			'ff_subscription_next_billing' => [ $this, 'handle_subscription' ],

			'ff_coupon_code'               => [ $this, 'handle_coupon' ],
			'ff_coupon_amount'             => [ $this, 'handle_coupon' ],
			'ff_coupon_type'               => [ $this, 'handle_coupon' ],

			'ff_add_to_cart'               => [ $this, 'handle_button' ],
			'ff_buy_now'                   => [ $this, 'handle_button' ],
			'ff_direct_checkout'           => [ $this, 'handle_button' ],

			'ff_checkout_form'             => [ $this, 'handle_cart' ],
		];

		add_shortcode( 'ff_cart_items', [ $this, 'handle_cart_items_loop' ] );
		add_shortcode( 'ff_customer_orders', [ $this, 'handle_customer_orders_loop' ] );

		foreach ( $shortcodes as $tag => $callback ) {
			add_shortcode( $tag, $callback );
		}
	}

	/**
	 * Container shortcode that loops over cart items.
	 *
	 * Usage: [ff_cart_items]<div>{ff_cart_item_name} — {ff_cart_item_price}</div>[/ff_cart_items]
	 */
	public function handle_cart_items_loop( $atts, string $content = '' ): string {
		$data = ( new CartData() )->items();
		if ( empty( $data ) ) {
			return DataFetcher::resolve( '{ff_cart_items_table}' );
		}

		DataFetcher::enqueue_fluentcart_assets();
		DataFetcher::enqueue_cart_live_assets();

		$output = '';
		foreach ( $data as $item ) {
			DataFetcher::set_cart_item_context( $item );
			$processed = do_shortcode( $content );
			$processed = DataFetcher::resolve_content_tokens( $processed );
			$output   .= $processed;
		}
		DataFetcher::reset_cart_item_context();

		return $output;
	}

	public function handle_customer_orders_loop( $atts, string $content = '' ): string {
		if ( ! class_exists( '\FluentCart\Api\Resource\CustomerResource' ) ) {
			return '';
		}
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$customer = \FluentCart\Api\Resource\CustomerResource::getCurrentCustomer();
		if ( ! $customer ) {
			return '';
		}
		$orders = $customer->orders()
			->whereIn( 'type', [ 'payment', 'subscription' ] )
			->orderBy( 'created_at', 'desc' )
			->limit( 50 )
			->get();
		if ( ! $orders || $orders->isEmpty() ) {
			return '';
		}
		$output = '';
		foreach ( $orders as $order ) {
			DataFetcher::set_order_context( $order );
			$processed = do_shortcode( $content );
			$processed = DataFetcher::resolve_content_tokens( $processed );
			$output   .= $processed;
		}
		DataFetcher::reset_order_context();
		return $output;
	}

	private function braced_token_from_shortcode( string $shortcode ): string {
		$parts = explode( '_', $shortcode, 2 );
		$rest  = $parts[1] ?? '';
		return '{ff_' . $rest . '}';
	}

	public function handle_product( $atts, string $content = '', string $tag = '' ): string {
		$atts = shortcode_atts( [ 'id' => '' ], $atts, $tag );
		$id   = '' !== $atts['id'] ? (int) $atts['id'] : null;
		$token = $this->braced_token_from_shortcode( $tag );
		return DataFetcher::resolve( $token, $id );
	}

	public function handle_customer( $atts, string $content = '', string $tag = '' ): string {
		$atts  = shortcode_atts( [ 'id' => '' ], $atts, $tag );
		$id    = '' !== $atts['id'] ? (int) $atts['id'] : null;
		$token = $this->braced_token_from_shortcode( $tag );
		return DataFetcher::resolve( $token, $id );
	}

	public function handle_order( $atts, string $content = '', string $tag = '' ): string {
		$atts  = shortcode_atts( [ 'id' => '' ], $atts, $tag );
		$id    = '' !== $atts['id'] ? (int) $atts['id'] : null;
		$token = $this->braced_token_from_shortcode( $tag );
		return DataFetcher::resolve( $token, $id );
	}

	public function handle_cart( $atts, string $content = '', string $tag = '' ): string {
		$token = $this->braced_token_from_shortcode( $tag );
		return DataFetcher::resolve( $token );
	}

	public function handle_subscription( $atts, string $content = '', string $tag = '' ): string {
		$atts  = shortcode_atts( [ 'id' => '' ], $atts, $tag );
		$id    = '' !== $atts['id'] ? (int) $atts['id'] : null;
		$token = $this->braced_token_from_shortcode( $tag );
		return DataFetcher::resolve( $token, $id );
	}

	public function handle_coupon( $atts, string $content = '', string $tag = '' ): string {
		$atts  = shortcode_atts( [ 'id' => '' ], $atts, $tag );
		$id    = '' !== $atts['id'] ? (int) $atts['id'] : null;
		$token = $this->braced_token_from_shortcode( $tag );
		return DataFetcher::resolve( $token, $id );
	}

	public function handle_button( $atts, string $content = '', string $tag = '' ): string {
		$atts = shortcode_atts( [
			'id'   => '',
			'text' => '',
		], $atts, $tag );

		$id   = '' !== $atts['id'] ? (int) $atts['id'] : null;
		$text = '' !== $atts['text'] ? $atts['text'] : null;

		$token = $this->braced_token_from_shortcode( $tag );

		$html = DataFetcher::resolve( $token, $id );

		if ( $text ) {
			$html = preg_replace(
				'/<(button|a)\b[^>]*>\K[^<]*(?=<\/(button|a)>)/',
				esc_html( $text ),
				$html
			);
		}

		return $html;
	}
}

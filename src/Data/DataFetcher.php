<?php
namespace FluentFlow\Data;

use FluentFlow\DataProvider\CartData;
use FluentFlow\DataProvider\CouponData;
use FluentFlow\DataProvider\CustomerData;
use FluentFlow\DataProvider\ProductData;
use FluentFlow\DataProvider\SubscriptionData;

defined( 'ABSPATH' ) || exit;

class DataFetcher {

	private static ?array $cart_item_context = null;
	private static ?object $customer_context = null;
	private static ?object $order_context = null;
	private static ?object $subscription_context = null;
	private static ?object $coupon_context = null;

	/**
	 * Get the current cart item from the Bricks query loop or static context.
	 */
	private static function get_current_cart_item(): ?array {
		if ( self::$cart_item_context !== null ) {
			return self::$cart_item_context;
		}
		if ( class_exists( '\Bricks\Query' ) && \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();
			if ( $query_type === 'ff_cart_items' ) {
				$loop_object = \Bricks\Query::get_loop_object();
				if ( is_array( $loop_object ) ) {
					return $loop_object;
				}
			}
		}
		return null;
	}

	/**
	 * Set the cart item context (used by the [ff_cart_items] shortcode loop).
	 */
	public static function set_cart_item_context( ?array $item ): void {
		self::$cart_item_context = $item;
	}

	public static function reset_cart_item_context(): void {
		self::$cart_item_context = null;
	}

	public static function set_customer_context( ?object $customer ): void {
		self::$customer_context = $customer;
	}

	public static function reset_customer_context(): void {
		self::$customer_context = null;
	}

	public static function set_order_context( ?object $order ): void {
		self::$order_context = $order;
	}

	public static function reset_order_context(): void {
		self::$order_context = null;
	}

	public static function set_subscription_context( ?object $subscription ): void {
		self::$subscription_context = $subscription;
	}

	public static function reset_subscription_context(): void {
		self::$subscription_context = null;
	}

	public static function set_coupon_context( ?object $coupon ): void {
		self::$coupon_context = $coupon;
	}

	public static function reset_coupon_context(): void {
		self::$coupon_context = null;
	}

	private static function get_current_customer(): ?object {
		if ( self::$customer_context !== null ) {
			return self::$customer_context;
		}
		if ( class_exists( '\Bricks\Query' ) && \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();
			if ( $query_type === 'ff_customer' ) {
				$loop_object = \Bricks\Query::get_loop_object();
				if ( is_object( $loop_object ) && isset( $loop_object->id ) ) {
					return $loop_object;
				}
			}
		}
		return null;
	}

	private static function get_current_order(): ?object {
		if ( self::$order_context !== null ) {
			return self::$order_context;
		}
		if ( class_exists( '\Bricks\Query' ) && \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();
			if ( $query_type === 'ff_customer_orders' ) {
				$loop_object = \Bricks\Query::get_loop_object();
				if ( is_object( $loop_object ) && isset( $loop_object->id ) ) {
					return $loop_object;
				}
			}
		}
		return null;
	}

	private static function get_current_subscription(): ?object {
		if ( self::$subscription_context !== null ) {
			return self::$subscription_context;
		}
		if ( class_exists( '\Bricks\Query' ) && \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();
			if ( $query_type === 'ff_subscriptions' ) {
				$loop_object = \Bricks\Query::get_loop_object();
				if ( is_object( $loop_object ) && isset( $loop_object->id ) ) {
					return $loop_object;
				}
			}
		}
		return null;
	}

	private static function get_current_coupon(): ?object {
		if ( self::$coupon_context !== null ) {
			return self::$coupon_context;
		}
		if ( class_exists( '\Bricks\Query' ) && \Bricks\Query::is_looping() ) {
			$query_type = \Bricks\Query::get_query_object_type();
			if ( $query_type === 'ff_coupons' ) {
				$loop_object = \Bricks\Query::get_loop_object();
				if ( is_object( $loop_object ) && isset( $loop_object->id ) ) {
					return $loop_object;
				}
			}
		}
		return null;
	}

	/**
	 * Resolve all {ff_...} tokens found in a content string.
	 */
	public static function resolve_content_tokens( string $content ): string {
		return preg_replace_callback(
			'/\{ff_\w+\}/',
			function ( $matches ) {
				return self::resolve( $matches[0] );
			},
			$content
		);
	}

	/**
	 * Resolve a token (e.g. {ff_product_price}) to its live value.
	 *
	 * Accepts both braced and unbraced formats ("{ff_product_title}" or "ff_product_title").
	 */
	public static function resolve( string $token, ?int $context_id = null ): string {
		$token = strtolower( trim( $token ) );

		if ( ! str_starts_with( $token, '{' ) && str_starts_with( $token, 'ff_' ) ) {
			$token = '{' . $token . '}';
		}

		return match ( $token ) {
			'{ff_product_title}'             => self::resolve_product_title( $context_id ),
			'{ff_product_price}'             => self::resolve_product_price( $context_id ),
			'{ff_product_sku}'               => self::resolve_product_sku( $context_id ),
			'{ff_product_stock_status}'      => self::resolve_product_stock_status( $context_id ),
			'{ff_product_thumbnail}'         => self::resolve_product_thumbnail( $context_id ),
			'{ff_product_description}'       => self::resolve_product_description( $context_id ),
			'{ff_product_url}'               => self::resolve_product_url( $context_id ),
			'{ff_product_min_price}'         => self::resolve_product_min_price( $context_id ),
			'{ff_product_max_price}'         => self::resolve_product_max_price( $context_id ),
			'{ff_product_variation_count}'   => self::resolve_product_variation_count( $context_id ),
			'{ff_product_fulfillment_type}'  => self::resolve_product_fulfillment_type( $context_id ),

			'{ff_customer_name}'             => self::resolve_customer_name( $context_id ),
			'{ff_customer_email}'            => self::resolve_customer_email( $context_id ),
			'{ff_customer_ltv}'              => self::resolve_customer_ltv( $context_id ),
			'{ff_customer_order_count}'      => self::resolve_customer_order_count( $context_id ),
			'{ff_customer_first_order_date}' => self::resolve_customer_first_order_date( $context_id ),
			'{ff_customer_last_order_date}'  => self::resolve_customer_last_order_date( $context_id ),
			'{ff_customer_aov}'              => self::resolve_customer_aov( $context_id ),
			'{ff_customer_photo}'            => self::resolve_customer_photo( $context_id ),
			'{ff_customer_billing}'          => self::resolve_customer_billing( $context_id ),
			'{ff_customer_shipping}'         => self::resolve_customer_shipping( $context_id ),

			'{ff_order_id}'                  => self::resolve_order_id( $context_id ),
			'{ff_order_total}'               => self::resolve_order_total( $context_id ),
			'{ff_order_subtotal}'            => self::resolve_order_subtotal( $context_id ),
			'{ff_order_status}'              => self::resolve_order_status( $context_id ),
			'{ff_order_payment_status}'      => self::resolve_order_payment_status( $context_id ),
			'{ff_order_payment_method}'      => self::resolve_order_payment_method( $context_id ),
			'{ff_order_currency}'            => self::resolve_order_currency( $context_id ),
			'{ff_order_item_count}'          => self::resolve_order_item_count( $context_id ),
			'{ff_order_receipt_number}'      => self::resolve_order_receipt_number( $context_id ),
			'{ff_order_date}'                => self::resolve_order_date( $context_id ),
			'{ff_order_invoice_no}'          => self::resolve_order_invoice_no( $context_id ),
			'{ff_order_uuid}'                => self::resolve_order_uuid( $context_id ),
			'{ff_order_type}'                => self::resolve_order_type( $context_id ),

			'{ff_cart_item_count}'           => self::resolve_cart_item_count(),
			'{ff_cart_total}'                => self::resolve_cart_total(),
			'{ff_cart_subtotal}'             => self::resolve_cart_subtotal(),
			'{ff_cart_items_table}'          => self::resolve_cart_items_table(),
			'{ff_cart_item_id}'              => self::resolve_cart_item_id(),
			'{ff_cart_item_name}'            => self::resolve_cart_item_name(),
			'{ff_cart_item_variation}'       => self::resolve_cart_item_variation(),
			'{ff_cart_item_image}'           => self::resolve_cart_item_image(),
			'{ff_cart_item_price}'           => self::resolve_cart_item_price(),
			'{ff_cart_item_quantity}'        => self::resolve_cart_item_quantity(),
			'{ff_cart_item_subtotal}'        => self::resolve_cart_item_subtotal(),
			'{ff_cart_item_url}'             => self::resolve_cart_item_url(),
			'{ff_cart_item_remove}'          => self::resolve_cart_item_remove(),

			'{ff_subscription_status}'       => self::resolve_subscription_status( $context_id ),
			'{ff_subscription_recurring}'    => self::resolve_subscription_recurring( $context_id ),
			'{ff_subscription_next_billing}' => self::resolve_subscription_next_billing( $context_id ),

			'{ff_coupon_code}'               => self::resolve_coupon_code( $context_id ),
			'{ff_coupon_amount}'             => self::resolve_coupon_amount( $context_id ),
			'{ff_coupon_type}'               => self::resolve_coupon_type( $context_id ),

			'{ff_add_to_cart}'               => self::resolve_add_to_cart( $context_id ),
			'{ff_buy_now}'                   => self::resolve_buy_now( $context_id ),
			'{ff_direct_checkout}'           => self::resolve_direct_checkout( $context_id ),
			'{ff_checkout_form}'             => self::resolve_checkout_form(),

			'{ff_site_name}'                 => get_bloginfo( 'name' ),
			'{ff_site_description}'          => get_bloginfo( 'description' ),
			'{ff_current_year}'              => wp_date( 'Y' ),

			default                          => self::fallback(),
		};
	}

	/**
	 * Return all supported tokens for the admin reference table.
	 */
	public static function get_token_registry(): array {
		return [
			'{ff_product_title}'            => [ 'label' => __( 'Product Title', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_title}' ],
			'{ff_product_price}'            => [ 'label' => __( 'Product Price', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_price}' ],
			'{ff_product_sku}'              => [ 'label' => __( 'Product SKU', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_sku}' ],
			'{ff_product_stock_status}'     => [ 'label' => __( 'Stock Status', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_stock_status}' ],
			'{ff_product_thumbnail}'        => [ 'label' => __( 'Product Thumbnail URL', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_thumbnail}' ],
			'{ff_product_description}'      => [ 'label' => __( 'Product Description', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_description}' ],
			'{ff_product_url}'              => [ 'label' => __( 'Product URL', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_url}' ],
			'{ff_product_min_price}'        => [ 'label' => __( 'Product Min Price', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_min_price}' ],
			'{ff_product_max_price}'        => [ 'label' => __( 'Product Max Price', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_max_price}' ],
			'{ff_product_variation_count}'  => [ 'label' => __( 'Product Variation Count', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_variation_count}' ],
			'{ff_product_fulfillment_type}' => [ 'label' => __( 'Fulfillment Type', 'fluentflow-bricks-bridge' ), 'group' => __( 'Product', 'fluentflow-bricks-bridge' ), 'token' => '{ff_product_fulfillment_type}' ],

			'{ff_customer_name}'             => [ 'label' => __( 'Customer Name', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_name}' ],
			'{ff_customer_email}'            => [ 'label' => __( 'Customer Email', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_email}' ],
			'{ff_customer_ltv}'              => [ 'label' => __( 'Customer Lifetime Value', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_ltv}' ],
			'{ff_customer_order_count}'      => [ 'label' => __( 'Customer Order Count', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_order_count}' ],
			'{ff_customer_first_order_date}' => [ 'label' => __( 'First Order Date', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_first_order_date}' ],
			'{ff_customer_last_order_date}'  => [ 'label' => __( 'Last Order Date', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_last_order_date}' ],
			'{ff_customer_aov}'              => [ 'label' => __( 'Average Order Value', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_aov}' ],
			'{ff_customer_photo}'            => [ 'label' => __( 'Customer Photo', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_photo}' ],
			'{ff_customer_billing}'          => [ 'label' => __( 'Billing Address', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_billing}' ],
			'{ff_customer_shipping}'         => [ 'label' => __( 'Shipping Address', 'fluentflow-bricks-bridge' ), 'group' => __( 'Customer', 'fluentflow-bricks-bridge' ), 'token' => '{ff_customer_shipping}' ],

			'{ff_order_id}'                  => [ 'label' => __( 'Order ID', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_id}' ],
			'{ff_order_total}'               => [ 'label' => __( 'Order Total', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_total}' ],
			'{ff_order_subtotal}'            => [ 'label' => __( 'Order Subtotal', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_subtotal}' ],
			'{ff_order_status}'              => [ 'label' => __( 'Order Status', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_status}' ],
			'{ff_order_payment_status}'      => [ 'label' => __( 'Payment Status', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_payment_status}' ],
			'{ff_order_payment_method}'      => [ 'label' => __( 'Payment Method', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_payment_method}' ],
			'{ff_order_currency}'            => [ 'label' => __( 'Order Currency', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_currency}' ],
			'{ff_order_item_count}'          => [ 'label' => __( 'Order Item Count', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_item_count}' ],
			'{ff_order_receipt_number}'      => [ 'label' => __( 'Receipt Number', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_receipt_number}' ],
			'{ff_order_date}'                => [ 'label' => __( 'Order Date', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_date}' ],
			'{ff_order_invoice_no}'          => [ 'label' => __( 'Invoice Number', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_invoice_no}' ],
			'{ff_order_uuid}'                => [ 'label' => __( 'Order UUID', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_uuid}' ],
			'{ff_order_type}'                => [ 'label' => __( 'Order Type', 'fluentflow-bricks-bridge' ), 'group' => __( 'Order', 'fluentflow-bricks-bridge' ), 'token' => '{ff_order_type}' ],

			'{ff_cart_item_count}'           => [ 'label' => __( 'Cart Item Count', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_count}' ],
			'{ff_cart_total}'                => [ 'label' => __( 'Cart Total', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_total}' ],
			'{ff_cart_subtotal}'             => [ 'label' => __( 'Cart Subtotal', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_subtotal}' ],
			'{ff_cart_items_table}'          => [ 'label' => __( 'Cart Items Table', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_items_table}' ],
			'{ff_cart_item_id}'              => [ 'label' => __( 'Cart Item ID', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_id}' ],
			'{ff_cart_item_name}'            => [ 'label' => __( 'Cart Item Name', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_name}' ],
			'{ff_cart_item_variation}'       => [ 'label' => __( 'Cart Item Variation', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_variation}' ],
			'{ff_cart_item_image}'           => [ 'label' => __( 'Cart Item Image URL', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_image}' ],
			'{ff_cart_item_price}'           => [ 'label' => __( 'Cart Item Price', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_price}' ],
			'{ff_cart_item_quantity}'        => [ 'label' => __( 'Cart Item Quantity', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_quantity}' ],
			'{ff_cart_item_subtotal}'        => [ 'label' => __( 'Cart Item Subtotal', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_subtotal}' ],
			'{ff_cart_item_url}'             => [ 'label' => __( 'Cart Item URL', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_url}' ],
			'{ff_cart_item_remove}'          => [ 'label' => __( 'Cart Item Remove Button', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_cart_item_remove}' ],

			'{ff_subscription_status}'       => [ 'label' => __( 'Subscription Status', 'fluentflow-bricks-bridge' ), 'group' => __( 'Subscription', 'fluentflow-bricks-bridge' ), 'token' => '{ff_subscription_status}' ],
			'{ff_subscription_recurring}'    => [ 'label' => __( 'Subscription Recurring Amount', 'fluentflow-bricks-bridge' ), 'group' => __( 'Subscription', 'fluentflow-bricks-bridge' ), 'token' => '{ff_subscription_recurring}' ],
			'{ff_subscription_next_billing}' => [ 'label' => __( 'Next Billing Date', 'fluentflow-bricks-bridge' ), 'group' => __( 'Subscription', 'fluentflow-bricks-bridge' ), 'token' => '{ff_subscription_next_billing}' ],

			'{ff_coupon_code}'               => [ 'label' => __( 'Coupon Code', 'fluentflow-bricks-bridge' ), 'group' => __( 'Coupon', 'fluentflow-bricks-bridge' ), 'token' => '{ff_coupon_code}' ],
			'{ff_coupon_amount}'             => [ 'label' => __( 'Coupon Amount', 'fluentflow-bricks-bridge' ), 'group' => __( 'Coupon', 'fluentflow-bricks-bridge' ), 'token' => '{ff_coupon_amount}' ],
			'{ff_coupon_type}'               => [ 'label' => __( 'Coupon Type', 'fluentflow-bricks-bridge' ), 'group' => __( 'Coupon', 'fluentflow-bricks-bridge' ), 'token' => '{ff_coupon_type}' ],

			'{ff_add_to_cart}'               => [ 'label' => __( 'Add to Cart Button', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_add_to_cart}' ],
			'{ff_buy_now}'                   => [ 'label' => __( 'Buy Now Button', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_buy_now}' ],
			'{ff_direct_checkout}'           => [ 'label' => __( 'Direct Checkout Button', 'fluentflow-bricks-bridge' ), 'group' => __( 'Cart', 'fluentflow-bricks-bridge' ), 'token' => '{ff_direct_checkout}' ],
			'{ff_checkout_form}'             => [ 'label' => __( 'Checkout Form', 'fluentflow-bricks-bridge' ), 'group' => __( 'Checkout', 'fluentflow-bricks-bridge' ), 'token' => '{ff_checkout_form}' ],
		];
	}

	/**
	 * Return token groups for the shortcode reference table.
	 */
	public static function get_token_groups(): array {
		$tokens = self::get_token_registry();
		$groups = [];
		foreach ( $tokens as $info ) {
			$groups[ $info['group'] ][] = $info;
		}
		return $groups;
	}

	/*
	 * -------------------------------------------------------------------------
	 * Product Resolvers
	 * -------------------------------------------------------------------------
	 */

	private static function get_product_post_type(): string {
		return ( new ProductData() )->post_type();
	}

	private static function resolve_product_id( ?int $context_id = null ): ?int {
		return ( new ProductData() )->resolve_id( $context_id );
	}

	private static function get_product_model( ?int $context_id = null ): ?object {
		return ( new ProductData() )->model( $context_id );
	}

	private static function resolve_product_title( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product || empty( $product->post_title ) ) {
			return self::fallback();
		}
		return $product->post_title;
	}

	private static function resolve_product_price( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product ) {
			return self::fallback();
		}
		$detail = $product->detail;
		if ( ! $detail ) {
			return self::fallback();
		}
		$price = $detail->min_price ?? 0;
		if ( $detail->min_price !== $detail->max_price ) {
			$from = self::format_price( $detail->min_price );
			$to   = self::format_price( $detail->max_price );
			return "{$from} – {$to}";
		}
		return self::format_price( $price );
	}

	private static function resolve_product_sku( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product ) {
			return self::fallback();
		}
		$variant = $product->variants()->first();
		if ( ! $variant || empty( $variant->sku ) ) {
			return self::fallback();
		}
		return $variant->sku;
	}

	private static function resolve_product_stock_status( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product ) {
			return self::fallback();
		}
		$detail = $product->detail;
		if ( ! $detail ) {
			return self::fallback();
		}
		$status = $detail->stock_availability ?? 'in-stock';
		$labels = [
			'in-stock'     => __( 'In Stock', 'fluentflow-bricks-bridge' ),
			'out-of-stock' => __( 'Out of Stock', 'fluentflow-bricks-bridge' ),
			'on-backorder' => __( 'On Backorder', 'fluentflow-bricks-bridge' ),
		];
		return $labels[ $status ] ?? $status;
	}

	private static function resolve_product_thumbnail( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product ) {
			return self::fallback();
		}
		$thumb_id = get_post_thumbnail_id( $product->ID );
		if ( ! $thumb_id ) {
			return self::fallback();
		}
		$src = wp_get_attachment_image_url( $thumb_id, 'full' );
		return $src ?: self::fallback();
	}

	private static function resolve_product_description( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product || empty( $product->post_content ) ) {
			return self::fallback();
		}
		return wp_trim_words( $product->post_content, 30, '…' );
	}

	private static function resolve_product_url( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product ) {
			return self::fallback();
		}
		return (string) get_permalink( $product->ID );
	}

	private static function resolve_product_min_price( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product || ! $product->detail ) {
			return self::fallback();
		}
		return self::format_price( $product->detail->min_price ?? 0 );
	}

	private static function resolve_product_max_price( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product || ! $product->detail ) {
			return self::fallback();
		}
		return self::format_price( $product->detail->max_price ?? 0 );
	}

	private static function resolve_product_variation_count( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product ) {
			return self::fallback();
		}
		$count = $product->variants()->count();
		return (string) $count;
	}

	private static function resolve_product_fulfillment_type( ?int $context_id = null ): string {
		$product = self::get_product_model( $context_id );
		if ( ! $product || ! $product->detail ) {
			return self::fallback();
		}
		$type = $product->detail->fulfillment_type ?? 'physical';
		$labels = [
			'physical' => __( 'Physical', 'fluentflow-bricks-bridge' ),
			'digital'  => __( 'Digital', 'fluentflow-bricks-bridge' ),
		];
		return $labels[ $type ] ?? $type;
	}

	/*
	 * -------------------------------------------------------------------------
	 * Customer Resolvers
	 * -------------------------------------------------------------------------
	 */

	private static function resolve_customer_id( ?int $context_id = null ): ?int {
		if ( null !== $context_id && $context_id > 0 ) {
			return $context_id;
		}
		$customer = self::get_customer_model();
		return $customer ? (int) $customer->id : null;
	}

	private static function get_customer_model( ?int $context_id = null ): ?object {
		if ( null !== $context_id && $context_id > 0 ) {
			return ( new CustomerData() )->find( $context_id );
		}

		$context_customer = self::get_current_customer();
		if ( $context_customer ) {
			return $context_customer;
		}

		return ( new CustomerData() )->current();
	}

	private static function resolve_customer_name( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer ) {
			return self::fallback();
		}
		$name = trim( $customer->first_name . ' ' . $customer->last_name );
		return $name ?: self::fallback();
	}

	private static function resolve_customer_email( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer || empty( $customer->email ) ) {
			return self::fallback();
		}
		return $customer->email;
	}

	private static function resolve_customer_ltv( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer || ! isset( $customer->ltv ) ) {
			return self::fallback();
		}
		return self::format_price( (float) $customer->ltv );
	}

	private static function resolve_customer_order_count( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer || ! isset( $customer->purchase_count ) ) {
			return self::fallback();
		}
		return (string) $customer->purchase_count;
	}

	private static function resolve_customer_first_order_date( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer || empty( $customer->first_purchase_date ) ) {
			return self::fallback();
		}
		return self::format_date( $customer->first_purchase_date );
	}

	private static function resolve_customer_last_order_date( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer || empty( $customer->last_purchase_date ) ) {
			return self::fallback();
		}
		return self::format_date( $customer->last_purchase_date );
	}

	private static function resolve_customer_aov( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer || ! isset( $customer->aov ) ) {
			return self::fallback();
		}
		return self::format_price( (float) $customer->aov );
	}

	private static function resolve_customer_photo( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer ) {
			return self::fallback();
		}
		$photo = $customer->photo ?? '';
		if ( $photo ) {
			return sprintf( '<img src="%s" alt="%s" class="ffbb-customer-photo" />', esc_url( $photo ), esc_attr( $customer->full_name ?? '' ) );
		}
		$email = $customer->email ?? '';
		if ( $email ) {
			$url = get_avatar_url( $email, [ 'size' => 96 ] );
			if ( $url ) {
				return sprintf( '<img src="%s" alt="%s" class="ffbb-customer-photo" />', esc_url( $url ), esc_attr( $customer->full_name ?? '' ) );
			}
		}
		return self::fallback();
	}

	private static function resolve_customer_billing( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer ) {
			return self::fallback();
		}
		$addresses = $customer->billing_address ?? null;
		if ( ! $addresses || $addresses->isEmpty() ) {
			return self::fallback();
		}
		$address = $addresses->first();
		return self::format_address( $address );
	}

	private static function resolve_customer_shipping( ?int $context_id = null ): string {
		$customer = self::get_customer_model( $context_id );
		if ( ! $customer ) {
			return self::fallback();
		}
		$addresses = $customer->shipping_address ?? null;
		if ( ! $addresses || $addresses->isEmpty() ) {
			return self::fallback();
		}
		$address = $addresses->first();
		return self::format_address( $address );
	}

	private static function format_address( $address ): string {
		if ( ! $address ) {
			return self::fallback();
		}
		$parts = [];
		if ( ! empty( $address->name ) ) {
			$parts[] = esc_html( $address->name );
		}
		if ( ! empty( $address->address_1 ) ) {
			$parts[] = esc_html( $address->address_1 );
		}
		if ( ! empty( $address->address_2 ) ) {
			$parts[] = esc_html( $address->address_2 );
		}
		if ( ! empty( $address->city ) ) {
			$parts[] = esc_html( $address->city );
		}
		if ( ! empty( $address->state ) ) {
			$parts[] = esc_html( $address->state );
		}
		if ( ! empty( $address->postcode ) ) {
			$parts[] = esc_html( $address->postcode );
		}
		if ( ! empty( $address->country ) ) {
			$country_name = function_exists( '\FluentCart\App\Helpers\Helper::getCountryName' )
				? \FluentCart\App\Helpers\Helper::getCountryName( $address->country )
				: $address->country;
			$parts[] = esc_html( $country_name );
		}
		return implode( '<br>', $parts );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Order Resolvers
	 * -------------------------------------------------------------------------
	 */

	private static function get_order_model( ?int $context_id = null ): ?object {
		if ( ! class_exists( '\FluentCart\App\Models\Order' ) ) {
			return null;
		}
		$order_id = $context_id;
		if ( ! $order_id || $order_id < 1 ) {
			$context_order = self::get_current_order();
			if ( $context_order !== null ) {
				return $context_order;
			}
			if ( ! empty( $GLOBALS['fc_order'] ) ) {
				return $GLOBALS['fc_order'];
			}
			return null;
		}
		$order = \FluentCart\App\Models\Order::find( $order_id );
		return $order ?: null;
	}

	private static function resolve_order_id( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->id ) ) {
			return self::fallback();
		}
		return (string) $order->id;
	}

	private static function resolve_order_total( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || ! isset( $order->total_amount ) ) {
			return self::fallback();
		}
		return self::format_price( (int) $order->total_amount / 100 );
	}

	private static function resolve_order_subtotal( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || ! isset( $order->subtotal ) ) {
			return self::fallback();
		}
		return self::format_price( (int) $order->subtotal / 100 );
	}

	private static function resolve_order_status( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->status ) ) {
			return self::fallback();
		}
		$labels = [
			'draft'          => __( 'Draft', 'fluentflow-bricks-bridge' ),
			'pending'        => __( 'Pending', 'fluentflow-bricks-bridge' ),
			'on-hold'        => __( 'On Hold', 'fluentflow-bricks-bridge' ),
			'processing'     => __( 'Processing', 'fluentflow-bricks-bridge' ),
			'completed'      => __( 'Completed', 'fluentflow-bricks-bridge' ),
			'failed'         => __( 'Failed', 'fluentflow-bricks-bridge' ),
			'refunded'       => __( 'Refunded', 'fluentflow-bricks-bridge' ),
			'partial-refund' => __( 'Partially Refunded', 'fluentflow-bricks-bridge' ),
			'cancelled'      => __( 'Cancelled', 'fluentflow-bricks-bridge' ),
		];
		return $labels[ $order->status ] ?? ucfirst( $order->status );
	}

	private static function resolve_order_payment_status( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || ! isset( $order->payment_status ) ) {
			return self::fallback();
		}
		return ucfirst( str_replace( '_', ' ', $order->payment_status ) );
	}

	private static function resolve_order_payment_method( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->payment_method_title ) ) {
			return self::fallback();
		}
		return $order->payment_method_title;
	}

	private static function resolve_order_currency( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->currency ) ) {
			return self::fallback();
		}
		return strtoupper( $order->currency );
	}

	private static function resolve_order_item_count( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order ) {
			return self::fallback();
		}
		$count = $order->order_items()->count();
		return (string) $count;
	}

	private static function resolve_order_receipt_number( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->receipt_number ) ) {
			return self::fallback();
		}
		return (string) $order->receipt_number;
	}

	private static function resolve_order_date( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->created_at ) ) {
			return self::fallback();
		}
		return self::format_date( $order->created_at );
	}

	private static function resolve_order_invoice_no( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->invoice_no ) ) {
			return self::fallback();
		}
		return esc_html( $order->invoice_no );
	}

	private static function resolve_order_uuid( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->uuid ) ) {
			return self::fallback();
		}
		return esc_html( $order->uuid );
	}

	private static function resolve_order_type( ?int $context_id = null ): string {
		$order = self::get_order_model( $context_id );
		if ( ! $order || empty( $order->type ) ) {
			return self::fallback();
		}
		$labels = [
			'payment'      => __( 'One-time', 'fluentflow-bricks-bridge' ),
			'subscription' => __( 'Subscription', 'fluentflow-bricks-bridge' ),
			'renewal'      => __( 'Renewal', 'fluentflow-bricks-bridge' ),
		];
		return $labels[ $order->type ] ?? ucfirst( $order->type );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Cart Resolvers
	 * -------------------------------------------------------------------------
	 */

	private static function get_cart_model(): ?object {
		return ( new CartData() )->cart();
	}

	private static function resolve_cart_item_count(): string {
		self::enqueue_cart_live_assets();
		$count = ( new CartData() )->item_count();
		return sprintf( '<span class="ffbb-cart-count" data-ffbb-token="cart_item_count">%d</span>', $count );
	}

	private static function resolve_cart_total(): string {
		self::enqueue_cart_live_assets();
		$total = ( new CartData() )->estimated_total();
		if ( null === $total ) {
			return self::fallback();
		}
		return sprintf(
			'<span class="ffbb-cart-total" data-ffbb-token="cart_total">%s</span>',
			self::format_price( $total / 100 )
		);
	}

	private static function resolve_cart_subtotal(): string {
		self::enqueue_cart_live_assets();
		$subtotal = ( new CartData() )->subtotal();
		return sprintf(
			'<span class="ffbb-cart-subtotal" data-ffbb-token="cart_subtotal">%s</span>',
			self::format_price( $subtotal / 100 )
		);
	}

	private static function resolve_cart_items_table(): string {
		self::enqueue_cart_live_assets();
		$cart_data = new CartData();
		$data      = $cart_data->items();
		if ( empty( $data ) ) {
			return self::resolve_empty_cart_message();
		}

		self::enqueue_fluentcart_assets();

		$html = '<div class="ffbb-cart-items-table" data-fluent-cart-cart-content-wrapper>';
		$html .= '<table class="ffbb-cart-table">';
		$html .= '<thead><tr>';
		$html .= '<th class="ffbb-cart-col-product">' . esc_html__( 'Product', 'fluentflow-bricks-bridge' ) . '</th>';
		$html .= '<th class="ffbb-cart-col-price">' . esc_html__( 'Price', 'fluentflow-bricks-bridge' ) . '</th>';
		$html .= '<th class="ffbb-cart-col-quantity">' . esc_html__( 'Quantity', 'fluentflow-bricks-bridge' ) . '</th>';
		$html .= '<th class="ffbb-cart-col-subtotal">' . esc_html__( 'Subtotal', 'fluentflow-bricks-bridge' ) . '</th>';
		$html .= '<th class="ffbb-cart-col-remove"></th>';
		$html .= '</tr></thead>';
		$html .= '<tbody>';

		foreach ( $data as $item_id => $item ) {
			$item_id    = esc_attr( $item['id'] ?? $item_id );
			$post_id    = (int) ( $item['post_id'] ?? 0 );
			$title      = esc_html( $item['post_title'] ?? '' );
			$variation  = esc_html( $item['title'] ?? '' );
			$url        = esc_url( $item['view_url'] ?? '' );
			$image      = esc_url( $cart_data->image_for_item( $item ) );
			$qty        = (int) ( $item['quantity'] ?? 1 );
			$unit_price = self::format_price( (float) ( $item['unit_price'] ?? $item['price'] ?? 0 ) / 100 );
			$subtotal   = self::format_price( (float) ( $item['subtotal'] ?? $item['line_total'] ?? 0 ) / 100 );

			$html .= '<tr class="ffbb-cart-item fct-cart-item" data-cart-items>';
			$html .= '<td class="ffbb-cart-item-info">';
			if ( $image ) {
				$html .= '<a href="' . $url . '" class="ffbb-cart-item-thumb">';
				$html .= '<img src="' . $image . '" alt="' . $title . '" data-fluent-cart-cart-list-item-image />';
				$html .= '</a>';
			}
			$html .= '<div class="ffbb-cart-item-details">';
			if ( $url ) {
				$html .= '<a href="' . $url . '" class="ffbb-cart-item-title" data-fluent-cart-cart-list-item-title><span data-fluent-cart-cart-list-item-title-element>' . $title . '</span></a>';
			} else {
				$html .= '<span class="ffbb-cart-item-title">' . $title . '</span>';
			}
			if ( $variation && $variation !== $title ) {
				$html .= '<small class="ffbb-cart-item-variant">' . $variation . '</small>';
			}
			$html .= '</div>';
			$html .= '</td>';

			$html .= '<td class="ffbb-cart-item-price" data-fluent-cart-cart-list-item-price>';
			$html .= $unit_price;
			$html .= '</td>';

			$html .= '<td class="ffbb-cart-item-quantity" data-fluent-cart-cart-list-item-quantity-wrapper>';
			$html .= '<div class="fct-cart-item-quantity">';
			$html .= '<button type="button" class="qty-btn decrease-btn" data-item-id="' . $item_id . '" data-fluent-cart-cart-list-item-decrease-button>&minus;</button>';
			$html .= '<input type="number" class="qty-value" data-item-id="' . $item_id . '" data-fluent-cart-cart-list-item-quantity-input value="' . $qty . '" min="1" />';
			$html .= '<button type="button" class="qty-btn increase-btn" data-item-id="' . $item_id . '" data-fluent-cart-cart-list-item-increase-button>+</button>';
			$html .= '</div>';
			$html .= '</td>';

			$html .= '<td class="ffbb-cart-item-subtotal" data-fluent-cart-cart-list-item-total-price>' . $subtotal . '</td>';

			$html .= '<td class="ffbb-cart-item-remove">';
			$html .= '<button type="button" class="fct-cart-item-delete-button" data-fluent-cart-cart-list-item-delete-button data-item-id="' . $item_id . '">' . esc_html__( 'Remove', 'fluentflow-bricks-bridge' ) . '</button>';
			$html .= '</td>';

			$html .= '</tr>';
		}

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

	private static function resolve_empty_cart_message(): string {
		$shop_url = function_exists( 'fluent_cart_get_shop_page_url' ) ? fluent_cart_get_shop_page_url() : home_url( '/shop' );
		$message  = apply_filters( 'ffbb_cart_empty_message', __( 'Your cart is empty.', 'fluentflow-bricks-bridge' ) );
		return sprintf(
			'<div class="fluent-cart-cart-empty-content ffbb-cart-empty"><p>%s</p><a class="continue-shopping-link" href="%s">%s</a></div>',
			esc_html( $message ),
			esc_url( $shop_url ),
			esc_html__( 'Continue Shopping', 'fluentflow-bricks-bridge' )
		);
	}

	private static function resolve_cart_item_id(): string {
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$item_id = esc_attr( $item['id'] ?? '' );
		return sprintf(
			'<span data-ffbb-token="item_id" data-ffbb-item-id="%s">%s</span>',
			$item_id,
			$item_id
		);
	}

	private static function resolve_cart_item_name(): string {
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$name = $item['post_title'] ?? '';
		$url  = $item['view_url'] ?? '';
		$item_id = esc_attr( $item['id'] ?? '' );
		if ( $url ) {
			return sprintf(
				'<a href="%s" data-ffbb-token="name" data-ffbb-item-id="%s" data-fluent-cart-cart-list-item-title><span data-fluent-cart-cart-list-item-title-element>%s</span></a>',
				esc_url( $url ),
				$item_id,
				esc_html( $name )
			);
		}
		return sprintf(
			'<span data-ffbb-token="name" data-ffbb-item-id="%s">%s</span>',
			$item_id,
			esc_html( $name )
		);
	}

	private static function resolve_cart_item_variation(): string {
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$variation = $item['title'] ?? '';
		$name      = $item['post_title'] ?? '';
		if ( ! $variation || $variation === $name ) {
			return self::fallback();
		}
		return esc_html( $variation );
	}

	private static function resolve_cart_item_image(): string {
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$image = ( new CartData() )->image_for_item( $item );
		if ( ! $image ) {
			return self::fallback();
		}
		$item_id = esc_attr( $item['id'] ?? '' );
		return sprintf(
			'<img src="%s" alt="%s" data-ffbb-token="image" data-ffbb-item-id="%s" data-fluent-cart-cart-list-item-image />',
			esc_url( $image ),
			esc_attr( $item['post_title'] ?? '' ),
			$item_id
		);
	}

	private static function resolve_cart_item_image_from_variation( array $item ): string {
		return ( new CartData() )->image_for_item( $item );
	}

	private static function get_fluentcart_placeholder(): string {
		return ( new CartData() )->placeholder();
	}

	private static function resolve_cart_item_price(): string {
		self::enqueue_cart_live_assets();
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$price = (float) ( $item['unit_price'] ?? $item['price'] ?? 0 );
		$item_id = esc_attr( $item['id'] ?? '' );
		return sprintf(
			'<span class="ffbb-cart-item-price" data-ffbb-token="price" data-ffbb-item-id="%s">%s</span>',
			$item_id,
			self::format_price( $price / 100 )
		);
	}

	private static function resolve_cart_item_quantity(): string {
		self::enqueue_cart_live_assets();
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$item_id = esc_attr( $item['id'] ?? '' );
		$qty     = (int) ( $item['quantity'] ?? 1 );

		self::enqueue_fluentcart_assets();

		return sprintf(
			'<div class="fct-cart-item-quantity" data-ffbb-token="quantity" data-ffbb-item-id="%s" data-fluent-cart-cart-list-item-quantity-wrapper>
				<button type="button" class="qty-btn decrease-btn" data-item-id="%s" data-fluent-cart-cart-list-item-decrease-button>&minus;</button>
				<input type="number" class="qty-value" data-item-id="%s" data-fluent-cart-cart-list-item-quantity-input value="%d" min="1" />
				<button type="button" class="qty-btn increase-btn" data-item-id="%s" data-fluent-cart-cart-list-item-increase-button>+</button>
			</div>',
			$item_id,
			$item_id,
			$item_id,
			$qty,
			$item_id
		);
	}

	private static function resolve_cart_item_subtotal(): string {
		self::enqueue_cart_live_assets();
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$subtotal = (float) ( $item['subtotal'] ?? $item['line_total'] ?? 0 );
		$item_id = esc_attr( $item['id'] ?? '' );
		return sprintf(
			'<span class="ffbb-cart-item-subtotal" data-ffbb-token="subtotal" data-ffbb-item-id="%s">%s</span>',
			$item_id,
			self::format_price( $subtotal / 100 )
		);
	}

	private static function resolve_cart_item_url(): string {
		$item = self::get_current_cart_item();
		if ( ! $item || empty( $item['view_url'] ) ) {
			return self::fallback();
		}
		return esc_url( $item['view_url'] );
	}

	private static function resolve_cart_item_remove(): string {
		$item = self::get_current_cart_item();
		if ( ! $item ) {
			return self::fallback();
		}
		$item_id = esc_attr( $item['id'] ?? '' );

		self::enqueue_fluentcart_assets();

		return sprintf(
			'<button type="button" class="fct-cart-item-delete-button" data-fluent-cart-cart-list-item-delete-button data-item-id="%s">%s</button>',
			$item_id,
			esc_html__( 'Remove', 'fluentflow-bricks-bridge' )
		);
	}

	/*
	 * -------------------------------------------------------------------------
	 * Subscription Resolvers
	 * -------------------------------------------------------------------------
	 */

	private static function get_subscription_model( ?int $context_id = null ): ?object {
		if ( $context_id && $context_id > 0 ) {
			return ( new SubscriptionData() )->find( $context_id );
		}

		$context_subscription = self::get_current_subscription();
		if ( $context_subscription ) {
			return $context_subscription;
		}

		return ( new SubscriptionData() )->first_active_for_current_customer();
	}

	private static function resolve_subscription_status( ?int $context_id = null ): string {
		$sub = self::get_subscription_model( $context_id );
		if ( ! $sub || empty( $sub->status ) ) {
			return self::fallback();
		}
		return ucfirst( $sub->status );
	}

	private static function resolve_subscription_recurring( ?int $context_id = null ): string {
		$sub = self::get_subscription_model( $context_id );
		if ( ! $sub || ! isset( $sub->recurring_amount ) ) {
			return self::fallback();
		}
		$interval = $sub->billing_interval ?? __( 'month', 'fluentflow-bricks-bridge' );
		$amount   = self::format_price( (int) $sub->recurring_amount / 100 );
		return "{$amount} / {$interval}";
	}

	private static function resolve_subscription_next_billing( ?int $context_id = null ): string {
		$sub = self::get_subscription_model( $context_id );
		if ( ! $sub || empty( $sub->next_billing_date ) ) {
			return self::fallback();
		}
		return self::format_date( $sub->next_billing_date );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Coupon Resolvers
	 * -------------------------------------------------------------------------
	 */

	private static function get_coupon_model( ?int $context_id = null ): ?object {
		if ( $context_id && $context_id > 0 ) {
			return ( new CouponData() )->find( $context_id );
		}

		return self::get_current_coupon();
	}

	private static function resolve_coupon_code( ?int $context_id = null ): string {
		$coupon = self::get_coupon_model( $context_id );
		if ( ! $coupon || empty( $coupon->code ) ) {
			return self::fallback();
		}
		return $coupon->code;
	}

	private static function resolve_coupon_amount( ?int $context_id = null ): string {
		$coupon = self::get_coupon_model( $context_id );
		if ( ! $coupon || ! isset( $coupon->amount ) ) {
			return self::fallback();
		}
		if ( $coupon->type === 'percentage' ) {
			return $coupon->amount . '%';
		}
		return self::format_price( (float) $coupon->amount );
	}

	private static function resolve_coupon_type( ?int $context_id = null ): string {
		$coupon = self::get_coupon_model( $context_id );
		if ( ! $coupon || empty( $coupon->type ) ) {
			return self::fallback();
		}
		$labels = [
			'percentage' => __( 'Percentage', 'fluentflow-bricks-bridge' ),
			'fixed'      => __( 'Fixed Amount', 'fluentflow-bricks-bridge' ),
		];
		return $labels[ $coupon->type ] ?? ucfirst( $coupon->type );
	}

	/*
	 * -------------------------------------------------------------------------
	 * Button Resolvers (Add to Cart, Buy Now, Direct Checkout)
	 * -------------------------------------------------------------------------
	 */

	private static function get_variation_id_for_product( ?int $product_id = null ): int {
		return ( new ProductData() )->first_variation_id( $product_id );
	}

	public static function enqueue_fluentcart_assets(): void {
		if ( ! class_exists( '\FluentCart\App\Modules\Templating\AssetLoader' ) ) {
			return;
		}
		if ( ! did_action( 'wp_enqueue_scripts' ) && ! doing_action( 'wp_enqueue_scripts' ) ) {
			add_action( 'wp_enqueue_scripts', [ '\FluentCart\App\Modules\Templating\AssetLoader', 'loadCartAssets' ] );
			return;
		}
		\FluentCart\App\Modules\Templating\AssetLoader::loadCartAssets();
	}

	private static function resolve_add_to_cart( ?int $context_id = null ): string {
		$product_id   = self::resolve_product_id( $context_id );
		$variation_id = self::get_variation_id_for_product( $product_id );

		if ( ! $variation_id ) {
			$variation_id = $context_id ?: 0;
		}

		self::enqueue_fluentcart_assets();

		$text = apply_filters( 'ffbb_add_to_cart_text', __( 'Add to Cart', 'fluentflow-bricks-bridge' ) );

		return sprintf(
			'<button type="button" class="fluent-cart-add-to-cart-button ffbb-button ffbb-add-to-cart" data-fluent-cart-add-to-cart-button data-cart-id="%d" data-product-id="%d">%s</button>',
			esc_attr( $variation_id ),
			esc_attr( $product_id ?: 0 ),
			esc_html( $text )
		);
	}

	private static function resolve_buy_now( ?int $context_id = null ): string {
		$product_id   = self::resolve_product_id( $context_id );
		$variation_id = self::get_variation_id_for_product( $product_id );

		if ( ! $variation_id ) {
			$variation_id = $context_id ?: 0;
		}

		$url  = site_url( '?fluent-cart=instant_checkout&item_id=' . $variation_id . '&quantity=1' );
		$text = apply_filters( 'ffbb_buy_now_text', __( 'Buy Now', 'fluentflow-bricks-bridge' ) );

		return sprintf(
			'<a href="%s" class="fluent-cart-direct-checkout-button ffbb-button ffbb-buy-now" data-fluent-cart-direct-checkout-button data-cart-id="%d" data-quantity="1">%s</a>',
			esc_url( $url ),
			esc_attr( $variation_id ),
			esc_html( $text )
		);
	}

	private static function resolve_direct_checkout( ?int $context_id = null ): string {
		$product_id   = self::resolve_product_id( $context_id );
		$variation_id = self::get_variation_id_for_product( $product_id );

		if ( ! $variation_id ) {
			$variation_id = $context_id ?: 0;
		}

		$url  = site_url( '?fluent-cart=instant_checkout&item_id=' . $variation_id . '&quantity=1' );
		$text = apply_filters( 'ffbb_direct_checkout_text', __( 'Buy Now', 'fluentflow-bricks-bridge' ) );

		return sprintf(
			'<a href="%s" class="fluent-cart-direct-checkout-button ffbb-button ffbb-direct-checkout" data-fluent-cart-direct-checkout-button data-cart-id="%d" data-quantity="1">%s</a>',
			esc_url( $url ),
			esc_attr( $variation_id ),
			esc_html( $text )
		);
	}

	private static function resolve_checkout_form(): string {
		if ( ! class_exists( '\FluentCart\App\Services\Renderer\CheckoutRenderer' ) ) {
			return self::fallback();
		}
		self::enqueue_checkout_assets();

		ob_start();
		echo do_shortcode( '[fluent_cart_checkout]' );
		return (string) ob_get_clean();
	}

	public static function handle_ajax_cart_data(): void {
		$cart_data = new CartData();
		$cart      = $cart_data->cart();

		if ( ! $cart ) {
			wp_send_json_error( [ 'message' => 'FluentCart not active' ] );
		}

		$data = $cart_data->items();

		if ( empty( $data ) ) {
			wp_send_json_success( [
				'items'      => [],
				'total'      => '',
				'subtotal'   => '',
				'item_count' => '0',
			] );
		}

		$count    = 0;
		$subtotal = 0;
		$items    = [];

		foreach ( $data as $item_id => $item ) {
			$id          = $item['id'] ?? $item_id;
			$qty         = (int) ( $item['quantity'] ?? 1 );
			$unit_price  = (float) ( $item['unit_price'] ?? $item['price'] ?? 0 );
			$line_total  = (float) ( $item['subtotal'] ?? $item['line_total'] ?? 0 );
			$count      += $qty;
			$subtotal   += $unit_price * $qty;

			$items[] = [
				'id'               => $id,
				'quantity'         => $qty,
				'unit_price'       => $unit_price,
				'line_total'       => $line_total,
				'price_formatted'  => self::format_price( $unit_price / 100 ),
				'subtotal_formatted' => self::format_price( $line_total / 100 ),
			];
		}

		$total = null !== $cart_data->estimated_total()
			? self::format_price( $cart_data->estimated_total() / 100 )
			: self::format_price( $subtotal / 100 );

		wp_send_json_success( [
			'items'      => $items,
			'total'      => $total,
			'subtotal'   => self::format_price( $subtotal / 100 ),
			'item_count' => (string) $count,
		] );
	}

	public static function enqueue_cart_live_assets(): void {
		static $enqueued = false;
		if ( $enqueued ) {
			return;
		}
		$enqueued = true;

		if ( ! did_action( 'wp_enqueue_scripts' ) && ! doing_action( 'wp_enqueue_scripts' ) ) {
			add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_cart_live_assets' ] );
			return;
		}
		$asset_url = FFBB_URL . 'assets/js/ffbb-cart-live.js';
		wp_enqueue_script( 'ffbb-cart-live', $asset_url, [], FFBB_VERSION, true );
		wp_localize_script( 'ffbb-cart-live', 'ffbb_cart_vars', [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		] );
	}

	public static function enqueue_checkout_assets(): void {
		if ( ! class_exists( '\FluentCart\App\Modules\Templating\AssetLoader' ) ) {
			return;
		}
		if ( ! did_action( 'wp_enqueue_scripts' ) && ! doing_action( 'wp_enqueue_scripts' ) ) {
			add_action( 'wp_enqueue_scripts', [ '\FluentCart\App\Modules\Templating\AssetLoader', 'markCheckoutAssetsRequired' ] );
			add_action( 'wp_enqueue_scripts', [ '\FluentCart\App\Modules\Templating\AssetLoader', 'loadCheckoutAssets' ] );
			return;
		}
		\FluentCart\App\Modules\Templating\AssetLoader::markCheckoutAssetsRequired();
		\FluentCart\App\Modules\Templating\AssetLoader::loadCheckoutAssets();
	}

	/*
	 * -------------------------------------------------------------------------
	 * Formatting Helpers
	 * -------------------------------------------------------------------------
	 */

	private static function format_price( float $amount ): string {
		$formatted = apply_filters( 'ffbb_format_price', '', $amount );
		if ( '' !== $formatted ) {
			return $formatted;
		}
		$currency = apply_filters( 'ffbb_currency_symbol', '$' );
		return $currency . number_format_i18n( $amount, 2 );
	}

	private static function format_date( string $date ): string {
		$timestamp = strtotime( $date );
		if ( ! $timestamp ) {
			return self::fallback();
		}
		$format = apply_filters( 'ffbb_date_format', get_option( 'date_format' ) );
		return wp_date( $format, $timestamp );
	}

	public static function fallback(): string {
		return apply_filters( 'ffbb_fallback_text', '', 'fluentflow-bricks-bridge' );
	}

	public static function placeholder(): string {
		return apply_filters( 'ffbb_placeholder_text', __( 'N/A', 'fluentflow-bricks-bridge' ), 'fluentflow-bricks-bridge' );
	}
}

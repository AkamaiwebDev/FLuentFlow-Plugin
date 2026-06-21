<?php
namespace FluentFlow\DataProvider;

defined( 'ABSPATH' ) || exit;

final class ProductData {

	public function post_type(): string {
		return apply_filters( 'ffbb_fluentcart_product_post_type', 'fluent-products' );
	}

	public function resolve_id( ?int $context_id = null ): ?int {
		$post_type = $this->post_type();

		if ( null !== $context_id && $context_id > 0 && get_post_type( $context_id ) === $post_type ) {
			return $context_id;
		}

		if ( function_exists( 'fluent_cart_get_current_product' ) ) {
			$product = fluent_cart_get_current_product();
			if ( $product && ! empty( $product->ID ) ) {
				return (int) $product->ID;
			}
		}

		$post_id = (int) get_the_ID();
		if ( $post_id > 0 && get_post_type( $post_id ) === $post_type ) {
			return $post_id;
		}

		return null;
	}

	public function model( ?int $context_id = null ): ?object {
		if ( ! class_exists( '\FluentCart\App\Models\Product' ) ) {
			return null;
		}

		$product_id = $this->resolve_id( $context_id );
		if ( ! $product_id ) {
			return null;
		}

		$product = \FluentCart\App\Models\Product::find( $product_id );
		return $product ?: null;
	}

	public function first_variation_id( ?int $product_id = null ): int {
		$product = $this->model( $product_id );
		if ( ! $product ) {
			return 0;
		}

		$variation = $product->variants()->first();
		return $variation ? (int) $variation->id : 0;
	}
}

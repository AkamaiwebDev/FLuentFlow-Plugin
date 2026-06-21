<?php
namespace FluentFlow\DataProvider;

defined( 'ABSPATH' ) || exit;

final class CartData {

	public function cart(): ?object {
		if ( ! class_exists( '\FluentCart\App\Helpers\CartHelper' ) ) {
			return null;
		}

		return \FluentCart\App\Helpers\CartHelper::getCart();
	}

	public function items(): array {
		$cart = $this->cart();
		if ( ! $cart || empty( $cart->cart_data ) ) {
			return [];
		}

		$data = is_string( $cart->cart_data ) ? json_decode( $cart->cart_data, true ) : $cart->cart_data;
		return is_array( $data ) ? $data : [];
	}

	public function item_count(): int {
		$count = 0;
		foreach ( $this->items() as $item ) {
			$count += (int) ( $item['quantity'] ?? 1 );
		}
		return $count;
	}

	public function subtotal(): float {
		$subtotal = 0;
		foreach ( $this->items() as $item ) {
			$price = (float) ( $item['price'] ?? 0 );
			$qty   = (int) ( $item['quantity'] ?? 1 );
			$subtotal += $price * $qty;
		}
		return $subtotal;
	}

	public function estimated_total(): ?float {
		$cart = $this->cart();
		if ( ! $cart || ! method_exists( $cart, 'getEstimatedTotal' ) ) {
			return null;
		}
		return (float) $cart->getEstimatedTotal();
	}

	public function image_for_item( array $item ): string {
		if ( ! empty( $item['featured_media'] ) ) {
			return (string) $item['featured_media'];
		}

		$image = $this->image_from_variation( $item );
		if ( $image ) {
			return $image;
		}

		$post_id = (int) ( $item['post_id'] ?? 0 );
		if ( $post_id ) {
			$image = get_the_post_thumbnail_url( $post_id, 'full' );
			if ( $image ) {
				return $image;
			}
		}

		return $this->placeholder();
	}

	public function placeholder(): string {
		if ( class_exists( '\FluentCart\App\Vite' ) ) {
			return (string) \FluentCart\App\Vite::getAssetUrl( 'images/placeholder.svg' );
		}
		return '';
	}

	private function image_from_variation( array $item ): string {
		$object_id = (int) ( $item['object_id'] ?? 0 );
		if ( ! $object_id || ! class_exists( '\FluentCart\App\Models\ProductVariation' ) ) {
			return '';
		}

		try {
			$variation = \FluentCart\App\Models\ProductVariation::find( $object_id );
			if ( ! $variation ) {
				return '';
			}

			if ( ! empty( $variation->thumbnail ) ) {
				return (string) $variation->thumbnail;
			}

			if ( ! empty( $variation->product ) && ! empty( $variation->product->thumbnail ) ) {
				return (string) $variation->product->thumbnail;
			}
		} catch ( \Throwable $e ) {
			do_action( 'ffbb_debug', 'CartData::image_from_variation error: ' . $e->getMessage() );
		}

		return '';
	}
}

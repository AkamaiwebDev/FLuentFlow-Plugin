<?php
namespace FluentFlow\DataProvider;

defined( 'ABSPATH' ) || exit;

final class CouponData {

	public function find( int $coupon_id ): ?object {
		if ( $coupon_id < 1 || ! class_exists( '\FluentCart\App\Models\Coupon' ) ) {
			return null;
		}

		$coupon = \FluentCart\App\Models\Coupon::find( $coupon_id );
		return $coupon ?: null;
	}

	public function all(): array {
		if ( ! class_exists( '\FluentCart\App\Models\Coupon' ) ) {
			return [];
		}

		$query = \FluentCart\App\Models\Coupon::orderBy( 'created_at', 'desc' )
			->limit( (int) apply_filters( 'ffbb_coupons_query_limit', 100 ) );

		$coupons = $query->get();
		return $coupons ? $coupons->all() : [];
	}
}

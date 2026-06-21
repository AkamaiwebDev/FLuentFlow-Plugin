<?php
namespace FluentFlow\DataProvider;

defined( 'ABSPATH' ) || exit;

final class SubscriptionData {

	public function find( int $subscription_id ): ?object {
		if ( $subscription_id < 1 || ! class_exists( '\FluentCart\App\Models\Subscription' ) ) {
			return null;
		}

		$subscription = \FluentCart\App\Models\Subscription::find( $subscription_id );
		return $subscription ?: null;
	}

	public function current_customer_subscriptions(): array {
		if ( ! class_exists( '\FluentCart\App\Models\Subscription' ) ) {
			return [];
		}

		$customer = ( new CustomerData() )->current();
		if ( ! $customer || empty( $customer->id ) ) {
			return [];
		}

		$query = \FluentCart\App\Models\Subscription::where( 'customer_id', $customer->id )
			->orderBy( 'created_at', 'desc' )
			->limit( (int) apply_filters( 'ffbb_subscriptions_query_limit', 50 ) );

		$subscriptions = $query->get();
		return $subscriptions ? $subscriptions->all() : [];
	}

	public function first_active_for_current_customer(): ?object {
		if ( ! class_exists( '\FluentCart\App\Models\Subscription' ) ) {
			return null;
		}

		$customer = ( new CustomerData() )->current();
		if ( ! $customer || empty( $customer->id ) ) {
			return null;
		}

		$subscription = \FluentCart\App\Models\Subscription::where( 'customer_id', $customer->id )
			->whereIn( 'status', [ 'active', 'trialling' ] )
			->first();

		return $subscription ?: null;
	}
}

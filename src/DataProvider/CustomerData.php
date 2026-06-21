<?php
namespace FluentFlow\DataProvider;

defined( 'ABSPATH' ) || exit;

final class CustomerData {

	public function current(): ?object {
		if ( class_exists( '\FluentCart\Api\Resource\CustomerResource' ) && is_user_logged_in() ) {
			$customer = \FluentCart\Api\Resource\CustomerResource::getCurrentCustomer();
			if ( $customer ) {
				return $customer;
			}
		}

		if ( ! class_exists( '\FluentCart\App\Models\Customer' ) ) {
			return null;
		}

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}

		$customer = \FluentCart\App\Models\Customer::where( 'user_id', $user_id )->first();
		return $customer ?: null;
	}

	public function find( int $customer_id ): ?object {
		if ( $customer_id < 1 || ! class_exists( '\FluentCart\App\Models\Customer' ) ) {
			return null;
		}

		$customer = \FluentCart\App\Models\Customer::find( $customer_id );
		return $customer ?: null;
	}

	public function current_as_loop(): array {
		$customer = $this->current();
		return $customer ? [ $customer ] : [];
	}

	public function orders(): array {
		$customer = $this->current();
		if ( ! $customer ) {
			return [];
		}

		$orders = $customer->orders()
			->whereIn( 'type', [ 'payment', 'subscription' ] )
			->orderBy( 'created_at', 'desc' )
			->limit( 50 )
			->get();

		return $orders ? $orders->all() : [];
	}
}

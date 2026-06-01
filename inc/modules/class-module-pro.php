<?php
namespace FluentFlow;

defined( 'ABSPATH' ) || exit;

class Module_Pro implements Feature_Interface {

	public const LICENSE_OPTION = 'ffbb_pro_license';
	public const VALIDATION_TRANSIENT = 'ffbb_license_check';

	public function get_id(): string {
		return 'pro';
	}

	public function get_name(): string {
		return __( 'Pro Licensing', 'fluentflow-bricks-bridge' );
	}

	public function get_description(): string {
		return __( 'License management and feature gating for FluentFlow Pro.', 'fluentflow-bricks-bridge' );
	}

	public function get_version(): string {
		return '1.0.0';
	}

	public function get_icon(): string {
		return '🔑';
	}

	public function init(): void {
		add_action( 'admin_init', [ $this, 'maybe_validate_license' ] );
		add_filter( 'ffbb_pro_feature_active', [ $this, 'is_licensed' ] );
		add_action( 'admin_notices', [ $this, 'license_notice' ] );
	}

	public function activate(): void {
		$this->maybe_validate_license();
	}

	public function deactivate(): void {
		delete_transient( self::VALIDATION_TRANSIENT );
		remove_action( 'admin_init', [ $this, 'maybe_validate_license' ] );
		remove_filter( 'ffbb_pro_feature_active', [ $this, 'is_licensed' ] );
		remove_action( 'admin_notices', [ $this, 'license_notice' ] );
	}

	public function maybe_validate_license(): void {
		$license = $this->get_stored_license();
		if ( empty( $license ) ) {
			return;
		}

		$cached = get_transient( self::VALIDATION_TRANSIENT );
		if ( false !== $cached ) {
			return;
		}

		$result = $this->remote_validate( $license );
		set_transient( self::VALIDATION_TRANSIENT, $result ? 'valid' : 'invalid', DAY_IN_SECONDS );
	}

	private function remote_validate( string $license_key ): bool {
		$response = wp_remote_post( 'https://fluentflow.io/api/license/validate', [
			'body' => [
				'license' => $license_key,
				'site'    => home_url(),
				'version' => FFBB_VERSION,
			],
			'timeout' => 15,
		] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return ! empty( $body['valid'] );
	}

	public function is_licensed(): bool {
		$status = get_transient( self::VALIDATION_TRANSIENT );
		return 'valid' === $status;
	}

	public function get_stored_license(): string {
		return Admin_Dashboard::get_setting( 'license_key', '' );
	}

	public function license_notice(): void {
		$license = $this->get_stored_license();
		if ( empty( $license ) ) {
			return;
		}

		if ( ! $this->is_licensed() ) {
			$screen = get_current_screen();
			if ( $screen && 'toplevel_page_fluentflow' === $screen->id ) {
				printf(
					'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
					esc_html__( 'Your FluentFlow Pro license key is invalid or expired.', 'fluentflow-bricks-bridge' )
				);
			}
		}
	}

	public static function require_valid_license(): void {
		$instance = Core_Registry::instance()->get_module( 'pro' );
		if ( ! $instance || ! $instance->is_licensed() ) {
			wp_die(
				esc_html__( 'A valid FluentFlow Pro license is required.', 'fluentflow-bricks-bridge' ),
				esc_html__( 'License Required', 'fluentflow-bricks-bridge' ),
				[ 'response' => 403 ]
			);
		}
	}
}

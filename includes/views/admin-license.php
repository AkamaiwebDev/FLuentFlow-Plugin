<?php
/**
 * @var string $license
 * @var bool $status
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="ffbb-wrap">
	<div class="ffbb-glass-panel">
		<div class="ffbb-header">
			<h1><?php esc_html_e( 'License', 'fluentflow-bricks-bridge' ); ?>
				<span class="ffbb-version">v<?php echo esc_html( FFBB_VERSION ); ?></span></h1>
			<p><?php esc_html_e( 'Manage your FluentFlow Pro license key.', 'fluentflow-bricks-bridge' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ffbb_save_license">
			<?php wp_nonce_field( 'ffbb_save_license', 'ffbb_license_nonce' ); ?>

			<div class="ffbb-modules-grid">
				<div class="ffbb-module-card ffbb-license-card">
					<h3><?php esc_html_e( 'Pro License Key', 'fluentflow-bricks-bridge' ); ?></h3>
					<p class="ffbb-module-desc">
						<?php esc_html_e( 'Enter your license key to activate Pro features.', 'fluentflow-bricks-bridge' ); ?>
					</p>

					<?php if ( $status ) : ?>
						<p class="ffbb-license-status ffbb-license-valid">
							<?php esc_html_e( '✓ License is active and valid.', 'fluentflow-bricks-bridge' ); ?>
						</p>
					<?php elseif ( ! empty( $license ) ) : ?>
						<p class="ffbb-license-status ffbb-license-invalid">
							<?php esc_html_e( '✗ License key is invalid or expired.', 'fluentflow-bricks-bridge' ); ?>
						</p>
					<?php endif; ?>

					<input type="text"
						name="license_key"
						class="ffbb-license-input"
						value=""
						autocomplete="off"
						placeholder="<?php esc_attr_e( 'Enter new license key', 'fluentflow-bricks-bridge' ); ?>"
					>
				</div>
			</div>

			<div class="ffbb-footer">
				<?php submit_button( __( 'Save License', 'fluentflow-bricks-bridge' ), 'primary', 'submit', false ); ?>
			</div>
		</form>
	</div>
</div>

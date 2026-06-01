<?php
/**
 * @var array $settings - The saved plugin settings.
 * @var \FluentFlow\Core_Registry $registry - The core registry instance.
 */

defined( 'ABSPATH' ) || exit;

$token_groups = \FluentFlow\Data_Fetcher::get_token_groups();
?>
<div class="ffbb-wrap">
	<div class="ffbb-glass-panel">
		<div class="ffbb-header">
			<h1><?php esc_html_e( 'FluentFlow', 'fluentflow-bricks-bridge' ); ?>
				<span class="ffbb-version">v<?php echo esc_html( FFBB_VERSION ); ?></span></h1>
			<p><?php esc_html_e( 'FluentCart Bridge — Dynamic Data Layer for Page Builders', 'fluentflow-bricks-bridge' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ffbb_save_settings">
			<?php wp_nonce_field( 'ffbb_save_settings', 'ffbb_nonce' ); ?>

			<div class="ffbb-modules-grid">
				<?php foreach ( $registry->get_modules() as $module ) : ?>
					<div class="ffbb-module-card">
						<div class="ffbb-module-card-header">
							<span class="ffbb-module-icon"><?php echo esc_html( $module->get_icon() ); ?></span>
							<h3><?php echo esc_html( $module->get_name() ); ?></h3>
						</div>
						<p class="ffbb-module-desc"><?php echo esc_html( $module->get_description() ); ?></p>
						<div class="ffbb-module-meta">
							<span class="ffbb-module-version">v<?php echo esc_html( $module->get_version() ); ?></span>
						</div>
						<?php $module_enabled = $settings['modules'][ $module->get_id() ] ?? '1'; ?>
						<label class="ffbb-toggle">
							<input type="checkbox"
								name="modules[<?php echo esc_attr( $module->get_id() ); ?>]"
								value="1"
								<?php checked( ! empty( $module_enabled ) ); ?>
							>
							<span class="ffbb-toggle-slider"></span>
							<span class="ffbb-toggle-label">
								<?php echo empty( $module_enabled )
									? esc_html__( 'Disabled', 'fluentflow-bricks-bridge' )
									: esc_html__( 'Enabled', 'fluentflow-bricks-bridge' ); ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>

				<?php if ( $registry->get_module( 'pro' ) ) : ?>
				<div class="ffbb-module-card ffbb-license-card">
					<h3><?php esc_html_e( 'Pro License', 'fluentflow-bricks-bridge' ); ?></h3>
					<p class="ffbb-module-desc">
						<?php esc_html_e( 'Enter your license key to activate Pro features.', 'fluentflow-bricks-bridge' ); ?>
					</p>
					<input type="text"
						name="license_key"
						class="ffbb-license-input"
						value="<?php echo esc_attr( $settings['license_key'] ?? '' ); ?>"
						placeholder="<?php esc_attr_e( 'License key', 'fluentflow-bricks-bridge' ); ?>"
					>
				</div>
				<?php endif; ?>
			</div>

			<div class="ffbb-footer">
				<?php submit_button( __( 'Save Settings', 'fluentflow-bricks-bridge' ), 'primary', 'submit', false ); ?>
			</div>
		</form>
	</div>

	<div class="ffbb-tokens-panel">
		<div class="ffbb-tokens-header">
			<h2><?php esc_html_e( 'Available Dynamic Tokens & Shortcodes', 'fluentflow-bricks-bridge' ); ?></h2>
			<p><?php esc_html_e( 'Click any token or shortcode to copy it. Use in Bricks dynamic tag fields, page builder widgets, or directly in WordPress content.', 'fluentflow-bricks-bridge' ); ?></p>
		</div>

		<?php foreach ( $token_groups as $group_name => $group_tokens ) : ?>
		<div class="ffbb-token-group">
			<h3 class="ffbb-token-group-title"><?php echo esc_html( $group_name ); ?></h3>

			<?php foreach ( $group_tokens as $info ) :
				$shortcode_tag = str_replace( [ '{', '}' ], '', $info['token'] );
				$shortcode     = '[' . $shortcode_tag . ']';
			?>
			<div class="ffbb-token-row">
				<button type="button"
					class="ffbb-token-chip"
					data-clipboard="<?php echo esc_attr( $info['token'] ); ?>"
					title="<?php esc_attr_e( 'Click to copy token', 'fluentflow-bricks-bridge' ); ?>">
					<code class="ffbb-token-code"><?php echo esc_html( $info['token'] ); ?></code>
					<span class="ffbb-token-label"><?php echo esc_html( $info['label'] ); ?></span>
					<span class="ffbb-token-copy-icon">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
					</span>
				</button>

				<button type="button"
					class="ffbb-shortcode-chip"
					data-clipboard="<?php echo esc_attr( $shortcode ); ?>"
					title="<?php esc_attr_e( 'Click to copy shortcode', 'fluentflow-bricks-bridge' ); ?>">
					<code class="ffbb-shortcode-code"><?php echo esc_html( $shortcode ); ?></code>
					<span class="ffbb-token-copy-icon">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
					</span>
				</button>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>
</div>

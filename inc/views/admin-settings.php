<?php
/**
 * @var array $settings
 * @var \FluentFlow\Core_Registry $registry
 */
defined( 'ABSPATH' ) || exit;

function ffbb_module_is_available( string $id ): bool {
	return match ( $id ) {
		'bricks'    => ffbb_is_bricks_active(),
		'elementor' => ffbb_is_elementor_active(),
		'overrides' => ffbb_is_bricks_active(),
		default     => true,
	};
}

function ffbb_module_availability_label( string $id ): string {
	return match ( $id ) {
		'bricks'    => __( 'Bricks detected ✓', 'fluentflow-bricks-bridge' ),
		'elementor' => ffbb_is_elementor_active()
			? __( 'Elementor detected ✓', 'fluentflow-bricks-bridge' )
			: __( 'Elementor not installed', 'fluentflow-bricks-bridge' ),
		'overrides' => ffbb_is_bricks_active()
			? __( 'Bricks detected ✓', 'fluentflow-bricks-bridge' )
			: __( 'Bricks not installed', 'fluentflow-bricks-bridge' ),
		default     => '',
	};
}

/**
 * Build a select dropdown for template overrides.
 *
 * @param \FluentFlow\Module_Overrides|null $overrides_mod Module instance or null.
 * @param string                            $page_type     One of 'cart', 'checkout', 'customer_dashboard'.
 * @param array                             $saved         Saved overrides data.
 * @param bool                              $disabled      Whether to disable the input.
 */
function ffbb_render_template_dropdown( $overrides_mod, string $page_type, array $saved, bool $disabled ): void {
	$key      = $page_type . '_template_id';
	$selected = $saved[ $key ] ?? 0;
	$label    = match ( $page_type ) {
		'cart'                => __( 'Cart Template', 'fluentflow-bricks-bridge' ),
		'checkout'            => __( 'Checkout Template', 'fluentflow-bricks-bridge' ),
		'customer_dashboard'  => __( 'Customer Dashboard Template', 'fluentflow-bricks-bridge' ),
		default               => $page_type,
	};
	$options  = $overrides_mod ? $overrides_mod::get_bricks_templates() : [];
	?>
	<label class="ffbb-override-field">
		<span class="ffbb-override-label"><?php echo esc_html( $label ); ?></span>
		<select name="overrides[<?php echo esc_attr( $key ); ?>]" <?php disabled( $disabled ); ?>>
			<?php foreach ( $options as $val => $text ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected, $val ); ?>><?php echo esc_html( $text ); ?></option>
			<?php endforeach; ?>
		</select>
	</label>
	<?php
}
?>
<div class="ffbb-wrap">
	<div class="ffbb-glass-panel">
		<div class="ffbb-header">
			<h1><?php esc_html_e( 'FluentFlow Settings', 'fluentflow-bricks-bridge' ); ?>
				<span class="ffbb-version">v<?php echo esc_html( FFBB_VERSION ); ?></span></h1>
			<p><?php esc_html_e( 'Enable or disable builder integrations.', 'fluentflow-bricks-bridge' ); ?></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ffbb_save_settings">
			<?php wp_nonce_field( 'ffbb_save_settings', 'ffbb_nonce' ); ?>

			<div class="ffbb-modules-grid">
				<?php foreach ( $registry->get_modules() as $module ) :
					$module_id      = $module->get_id();
					$available      = ffbb_module_is_available( $module_id );
					$avail_label    = ffbb_module_availability_label( $module_id );
					$module_enabled = $settings['modules'][ $module_id ] ?? ( $module_id === 'bricks' ? '1' : '0' );
				?>
					<div class="ffbb-module-card <?php echo $available ? '' : 'ffbb-module-unavailable'; ?>">
						<div class="ffbb-module-card-header">
							<span class="ffbb-module-icon"><?php echo esc_html( $module->get_icon() ); ?></span>
							<h3><?php echo esc_html( $module->get_name() ); ?></h3>
						</div>
						<p class="ffbb-module-desc"><?php echo esc_html( $module->get_description() ); ?></p>
						<div class="ffbb-module-meta">
							<span class="ffbb-module-version">v<?php echo esc_html( $module->get_version() ); ?></span>
							<span class="ffbb-module-availability <?php echo $available ? 'ffbb-avail-yes' : 'ffbb-avail-no'; ?>">
								<?php echo esc_html( $avail_label ); ?>
							</span>
						</div>
						<label class="ffbb-toggle <?php echo $available ? '' : 'ffbb-toggle-disabled'; ?>">
							<input type="checkbox"
								name="modules[<?php echo esc_attr( $module_id ); ?>]"
								value="1"
								<?php checked( ! empty( $module_enabled ) && $available ); ?>
								<?php disabled( ! $available ); ?>
							>
							<span class="ffbb-toggle-slider"></span>
							<span class="ffbb-toggle-label">
								<?php if ( ! $available ) : ?>
									<?php esc_html_e( 'Unavailable', 'fluentflow-bricks-bridge' ); ?>
								<?php elseif ( empty( $module_enabled ) ) : ?>
									<?php esc_html_e( 'Disabled', 'fluentflow-bricks-bridge' ); ?>
								<?php else : ?>
									<?php esc_html_e( 'Enabled', 'fluentflow-bricks-bridge' ); ?>
								<?php endif; ?>
							</span>
						</label>
					</div>
				<?php endforeach; ?>
			</div>

			<?php
			$overrides_mod = $registry->get_module( 'overrides' );
			$module_settings = $settings['modules'] ?? [];
			$overrides_enabled = ! empty( $module_settings['overrides'] ?? '1' ) && ffbb_module_is_available( 'overrides' );
			$overrides_saved   = $overrides_mod ? $overrides_mod::get_overrides() : [];
			$bricks_available  = ffbb_is_bricks_active();
			?>
			<div class="ffbb-overrides-section <?php echo $bricks_available ? '' : 'ffbb-overrides-unavailable'; ?>">
				<div class="ffbb-overrides-header">
					<h2><?php esc_html_e( 'Template Overrides', 'fluentflow-bricks-bridge' ); ?></h2>
					<p><?php esc_html_e( 'Replace FluentCart pages with Bricks templates. The template content replaces the entire page output while preserving checkout scripts and AJAX functionality.', 'fluentflow-bricks-bridge' ); ?></p>
					<?php if ( ! $bricks_available ) : ?>
						<p class="ffbb-overrides-notice">⚠️ <?php esc_html_e( 'Bricks Builder is required for template overrides.', 'fluentflow-bricks-bridge' ); ?></p>
					<?php elseif ( ! $overrides_enabled ) : ?>
						<p class="ffbb-overrides-notice">⚠️ <?php esc_html_e( 'Enable the Template Overrides module above to apply these mappings on the frontend.', 'fluentflow-bricks-bridge' ); ?></p>
					<?php endif; ?>
				</div>
				<div class="ffbb-overrides-fields">
					<?php
					ffbb_render_template_dropdown( $overrides_mod, 'cart', $overrides_saved, ! $bricks_available );
					ffbb_render_template_dropdown( $overrides_mod, 'checkout', $overrides_saved, ! $bricks_available );
					ffbb_render_template_dropdown( $overrides_mod, 'customer_dashboard', $overrides_saved, ! $bricks_available );
					?>
				</div>
			</div>

			<div class="ffbb-footer">
				<?php submit_button( __( 'Save Settings', 'fluentflow-bricks-bridge' ), 'primary', 'submit', false ); ?>
			</div>
		</form>
	</div>
</div>

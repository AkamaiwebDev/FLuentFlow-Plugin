<?php
/**
 * @var array $token_groups
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="ffbb-wrap">
	<div class="ffbb-glass-panel">
		<div class="ffbb-header">
			<h1><?php esc_html_e( 'Tags Reference', 'fluentflow-bricks-bridge' ); ?>
				<span class="ffbb-version">v<?php echo esc_html( FFBB_VERSION ); ?></span></h1>
			<p><?php esc_html_e( 'Available tokens for Bricks / Elementor dynamic tags and WordPress shortcodes.', 'fluentflow-bricks-bridge' ); ?></p>
		</div>
	</div>

	<div class="ffbb-tokens-panel">
		<div class="ffbb-tokens-header">
			<h2><?php esc_html_e( 'Dynamic Tokens & Shortcodes', 'fluentflow-bricks-bridge' ); ?></h2>
			<p><?php esc_html_e( 'Click any token or shortcode to copy it. Use in Bricks / Elementor dynamic tag fields or directly in WordPress content.', 'fluentflow-bricks-bridge' ); ?></p>
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

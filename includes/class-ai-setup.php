<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AI_Setup {
	public function __construct() {
		add_action('admin_menu', array($this, 'register_ai_settings_page'));
	}

	public function register_ai_settings_page() {
		// Feltételezzük, hogy az ebook menü slug "ebook-sales"
		add_submenu_page(
			'ebook-sales',      // Parent slug
			'AI Setup',         // Page title
			'AI Setup',         // Menu title
			'manage_options',   // Capability
			'ai-setup',         // Menu slug
			array($this, 'display_settings_page')
		);
	}

	public function display_settings_page() {
		if ( isset($_POST['openai_api_key']) && check_admin_referer('save_openai_api_key') ) {
			$api_key = sanitize_text_field( $_POST['openai_api_key'] );
			update_option( 'openai_api_key', $api_key );
			echo '<div class="updated"><p>Beállítások elmentve.</p></div>';
		}
		$current_key = get_option('openai_api_key', '');
		?>
		<div class="wrap">
			<h1>OpenAI API Beállítások</h1>
			<form method="post" action="">
				<?php wp_nonce_field('save_openai_api_key'); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">API Kulcs</th>
						<td><input type="text" name="openai_api_key" value="<?php echo esc_attr($current_key); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button('Mentés'); ?>
			</form>
		</div>
		<?php
	}
}

// Inicializáljuk az AI setup-ot
new AI_Setup();

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
			if ( isset($_POST['openai_api_model']) ) {
				$model = sanitize_text_field( $_POST['openai_api_model'] );
				update_option( 'openai_api_model', $model );
			}
			echo '<div class="updated"><p>Beállítások elmentve.</p></div>';
		}
		$current_key   = get_option('openai_api_key', '');
		$current_model = get_option('openai_api_model', '');

		// Lekérjük az OpenAI modelleket, ha van API kulcs
		$models = array();
		if ( ! empty($current_key) ) {
			$response = wp_remote_get('https://api.openai.com/v1/models', array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $current_key,
				),
			));
			if ( ! is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200 ) {
				$body = wp_remote_retrieve_body($response);
				$data = json_decode($body);
				if ( isset($data->data) && is_array($data->data) ) {
					foreach ( $data->data as $model ) {
						$models[] = $model->id;
					}
				}
			}
		}
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
					<tr valign="top">
						<th scope="row">Model Tipusa</th>
						<td>
							<select name="openai_api_model">
								<?php foreach($models as $model): ?>
									<option value="<?php echo esc_attr($model); ?>" <?php selected($current_model, $model); ?>><?php echo esc_html($model); ?></option>
								<?php endforeach; ?>
								<?php if(empty($models)): ?>
									<option value="">Nincs elérhető modell</option>
								<?php endif; ?>
							</select>
						</td>
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

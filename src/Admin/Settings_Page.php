<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Admin;

class Settings_Page {

	public const VIMEO_TOKEN  = 'vimeo_access_token';
	public const WISTIA_TOKEN = 'wistia_api_key';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register settings page under "Settings → Tribe Embeds"
	 */
	public function add_menu(): void {
		add_options_page(
			esc_html__( 'Tribe Embeds', 'tribe-embeds' ),
			esc_html__( 'Tribe Embeds', 'tribe-embeds' ),
			'manage_options',
			'tribe-embeds-settings',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register Vimeo credentials.
	 */
	public function register_settings(): void {
		register_setting(
			'tribe_embeds_settings',
			'tribe_embeds_vimeo_access_token',
			[ 'sanitize_callback' => 'sanitize_text_field' ]
		);

		register_setting(
			'tribe_embeds_settings',
			'tribe_embeds_wistia_api_key',
			[ 'sanitize_callback' => 'sanitize_text_field' ]
		);
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tribe Embeds – Vimeo Auth', 'tribe-embeds' ); ?></h1>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'tribe_embeds_settings' );
				do_settings_sections( 'tribe_embeds_settings' );
				?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<label for="tribe_embeds_vimeo_access_token"><?php esc_html_e( 'Vimeo Access Token', 'tribe-embeds' ); ?></label>
						</th>
						<td>
							<input type="password"
								   id="tribe_embeds_vimeo_access_token"
								   name="tribe_embeds_vimeo_access_token"
								   value="<?php echo esc_attr( get_option( 'tribe_embeds_vimeo_access_token' ) ); ?>"
								   class="regular-text"
							/>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="tribe_embeds_wistia_api_key"><?php esc_html_e( 'Wistia API key', 'tribe-embeds' ); ?></label>
						</th>
						<td>
							<input type="password"
								   id="tribe_embeds_wistia_api_key"
								   name="tribe_embeds_wistia_api_key"
								   value="<?php echo esc_attr( get_option( 'tribe_embeds_wistia_api_key' ) ); ?>"
								   class="regular-text"
							/>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Helper to retrieve stored Vimeo creds.
	 */
	public static function get_stored_settings(): array {
		return [
			self::VIMEO_TOKEN  => get_option( 'tribe_embeds_vimeo_access_token', '' ),
			self::WISTIA_TOKEN => get_option( 'tribe_embeds_wistia_api_key', '' ),
		];
	}

}

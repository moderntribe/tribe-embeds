<?php declare(strict_types=1);

namespace Tribe\Tribe_Embed\Admin;

/**
 * Settings → Tribe Embeds: stores the API credentials.
 */
final class Settings_Page {

	private const OPTION_GROUP = 'tribe_embed_settings';
	private const PAGE_SLUG    = 'tribe-embeds-settings';

	public function __construct( private Credentials $credentials ) {
	}

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_menu(): void {
		add_options_page(
			esc_html__( 'Tribe Embeds', 'tribe-embeds' ),
			esc_html__( 'Tribe Embeds', 'tribe-embeds' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	public function register_settings(): void {
		foreach ( array_keys( $this->get_fields() ) as $option ) {
			register_setting(
				self::OPTION_GROUP,
				$option,
				[ 'sanitize_callback' => 'sanitize_text_field' ]
			);
		}
	}

	public function render_page(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Tribe Embeds Settings', 'tribe-embeds' ); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION_GROUP ); ?>
				<table class="form-table">
					<?php foreach ( $this->get_fields() as $option => $label ) : ?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $label ); ?></label>
							</th>
							<td>
								<input
									type="password"
									id="<?php echo esc_attr( $option ); ?>"
									name="<?php echo esc_attr( $option ); ?>"
									value="<?php echo esc_attr( $this->credentials->get_stored_value( $option ) ); ?>"
									class="regular-text"
								/>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Setting fields: option name => label.
	 *
	 * @return array<string,string>
	 */
	private function get_fields(): array {
		return [
			Credentials::VIMEO_OPTION  => __( 'Vimeo Access Token', 'tribe-embeds' ),
			Credentials::WISTIA_OPTION => __( 'Wistia API key', 'tribe-embeds' ),
		];
	}

}

<?php
/**
 * Plugin bootstrap.
 *
 * @package CF7AT
 */

namespace CF7AT;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the field types, the block styler and the shared assets together.
 */
final class Plugin {

	const VERSION = '3.0.0';

	/**
	 * The oldest Contact Form 7 release with the APIs this plugin relies on
	 * (form-tag generator version 2 and schema-woven validation).
	 */
	const MIN_CF7_VERSION = '6.0';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Registered field types.
	 *
	 * @var Fields\Field[]
	 */
	private $fields = [];

	/**
	 * Boots the plugin once Contact Form 7 has been loaded.
	 */
	public static function boot(): void {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}
	}

	/**
	 * Returns the absolute path of a file inside the plugin.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 */
	public static function path( string $relative_path = '' ): string {
		return plugin_dir_path( PLUGIN_FILE ) . ltrim( $relative_path, '/' );
	}

	/**
	 * Returns the URL of a file inside the plugin.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 */
	public static function url( string $relative_path = '' ): string {
		return plugins_url( ltrim( $relative_path, '/' ), PLUGIN_FILE );
	}

	/**
	 * Hooks everything up, or shows a notice if Contact Form 7 is too old.
	 */
	private function init(): void {
		if ( ! self::is_cf7_compatible() ) {
			add_action( 'admin_notices', [ $this, 'render_incompatible_notice' ] );
			return;
		}

		$this->fields = [
			new Fields\Range_Slider(),
			new Fields\Date_Picker(),
			new Fields\Time_Picker(),
		];

		foreach ( $this->fields as $field ) {
			$field->hook();
		}

		( new Styler\Block_Styler() )->hook();

		add_action( 'init', [ $this, 'load_translations' ], 1 );
		add_action( 'init', [ $this, 'register_assets' ], 5 );
	}

	/**
	 * Loads the translations bundled in /languages.
	 *
	 * Installs from WordPress.org get language packs from translate.wordpress.org
	 * automatically; this covers installs from GitHub or a ZIP file.
	 */
	public function load_translations(): void {
		load_plugin_textdomain( 'cf7-additional-types', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Whether the active Contact Form 7 version is supported.
	 */
	public static function is_cf7_compatible(): bool {
		return defined( 'WPCF7_VERSION' ) && version_compare( WPCF7_VERSION, self::MIN_CF7_VERSION, '>=' );
	}

	/**
	 * Registers the front-end stylesheet and the field scripts.
	 */
	public function register_assets(): void {
		wp_register_style(
			'cf7at-style',
			self::url( 'build/style.css' ),
			[],
			self::asset_version( 'build/style.css' )
		);

		$asset = self::asset_file( 'fields' );

		wp_register_script(
			'cf7at-fields',
			self::url( 'build/fields.js' ),
			$asset['dependencies'],
			$asset['version'],
			[
				'in_footer' => true,
				'strategy'  => 'defer',
			]
		);

		wp_add_inline_script(
			'cf7at-fields',
			'window.cf7atL10n = ' . wp_json_encode( self::script_strings() ) . ';',
			'before'
		);
	}

	/**
	 * Strings used by the front-end field scripts.
	 *
	 * They are translated here rather than in JavaScript, so the front end
	 * does not need to load the WordPress i18n packages.
	 *
	 * @return array<string, string>
	 */
	private static function script_strings(): array {
		return [
			'chooseDate'    => __( 'Choose date', 'cf7-additional-types' ),
			'chooseTime'    => __( 'Choose time', 'cf7-additional-types' ),
			'clear'         => __( 'Clear', 'cf7-additional-types' ),
			'previousMonth' => __( 'Previous month', 'cf7-additional-types' ),
			'nextMonth'     => __( 'Next month', 'cf7-additional-types' ),
			'month'         => __( 'Month', 'cf7-additional-types' ),
			'year'          => __( 'Year', 'cf7-additional-types' ),
		];
	}

	/**
	 * Reads the dependency and version file that wp-scripts generates for an entry point.
	 *
	 * @param string $entry Entry point name, e.g. "fields".
	 * @return array{dependencies: string[], version: string}
	 */
	public static function asset_file( string $entry ): array {
		$file = self::path( "build/{$entry}.asset.php" );

		$asset = is_readable( $file ) ? require $file : [];

		return [
			'dependencies' => $asset['dependencies'] ?? [],
			'version'      => $asset['version'] ?? self::VERSION,
		];
	}

	/**
	 * Cache-busting version for a built file.
	 *
	 * @param string $relative_path Path relative to the plugin root.
	 */
	private static function asset_version( string $relative_path ): string {
		$file = self::path( $relative_path );

		return is_readable( $file ) ? self::VERSION . '-' . filemtime( $file ) : self::VERSION;
	}

	/**
	 * Admin notice shown when Contact Form 7 is older than the supported minimum.
	 */
	public function render_incompatible_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: minimum Contact Form 7 version number. */
					__( 'CF7 Additional Types needs Contact Form 7 %s or newer. Please update Contact Form 7 to use its fields and form styles.', 'cf7-additional-types' ),
					self::MIN_CF7_VERSION
				)
			)
		);
	}
}

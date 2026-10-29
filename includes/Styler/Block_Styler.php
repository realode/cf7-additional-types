<?php
/**
 * Design settings for Contact Form 7's own block.
 *
 * @package CF7AT
 */

namespace CF7AT\Styler;

use CF7AT\Plugin;
use WP_HTML_Tag_Processor;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Extends the "Contact Form 7" block with design settings instead of adding a
 * block of its own, so every existing form gets them without being replaced.
 *
 * Settings live in one block attribute and are applied when the block is
 * rendered, as CSS custom properties and classes on the block wrapper. The
 * markup Contact Form 7 saves is never changed, so deactivating this plugin
 * leaves every form intact.
 */
final class Block_Styler {

	const BLOCK_NAME = 'contact-form-7/contact-form-selector';

	const ATTRIBUTE = 'cf7atStyle';

	/**
	 * Shortcode attribute that marks a form whose submit button should use the theme's button styles.
	 */
	const THEME_BUTTON_MARKER = 'cf7at_theme_button';

	/**
	 * Color settings and the CSS custom properties they set.
	 */
	const COLOR_PROPERTIES = [
		'accentColor'      => '--cf7at-accent',
		'labelColor'       => '--cf7at-label',
		'fieldText'        => '--cf7at-field-text',
		'fieldBackground'  => '--cf7at-field-bg',
		'fieldBorder'      => '--cf7at-field-border',
		'buttonText'       => '--cf7at-button-text',
		'buttonBackground' => '--cf7at-button-bg',
	];

	/**
	 * Size settings and the CSS custom properties they set.
	 */
	const LENGTH_PROPERTIES = [
		'radius'      => '--cf7at-radius',
		'borderWidth' => '--cf7at-border-width',
		'gap'         => '--cf7at-gap',
	];

	/**
	 * Registers the hooks.
	 */
	public function hook(): void {
		// Runs before Contact Form 7 registers its block on "init".
		add_filter( 'block_type_metadata', [ $this, 'extend_block_metadata' ] );

		add_action( 'init', [ $this, 'register_block_styles' ] );
		add_filter( 'render_block_' . self::BLOCK_NAME, [ $this, 'render_block' ], 10, 2 );
		add_filter( 'do_shortcode_tag', [ $this, 'apply_theme_button' ], 10, 3 );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Adds the design attribute and the stylesheet to the block's metadata.
	 *
	 * @param array $metadata Block metadata read from block.json.
	 */
	public function extend_block_metadata( array $metadata ): array {
		if ( self::BLOCK_NAME !== ( $metadata['name'] ?? '' ) ) {
			return $metadata;
		}

		$metadata['attributes'][ self::ATTRIBUTE ] = [
			'type'    => 'object',
			'default' => [],
		];

		$metadata['style'] = array_values(
			array_unique( array_merge( (array) ( $metadata['style'] ?? [] ), [ 'cf7at-style' ] ) )
		);

		return $metadata;
	}

	/**
	 * Registers the style presets, shown in the block's "Styles" panel.
	 */
	public function register_block_styles(): void {
		$styles = [
			'default'    => [ _x( 'Default', 'form style', 'cf7-additional-types' ), true ],
			'filled'     => [ _x( 'Filled', 'form style', 'cf7-additional-types' ), false ],
			'underlined' => [ _x( 'Underlined', 'form style', 'cf7-additional-types' ), false ],
			'unstyled'   => [ _x( 'Unstyled', 'form style', 'cf7-additional-types' ), false ],
		];

		foreach ( $styles as $name => list( $label, $is_default ) ) {
			register_block_style(
				self::BLOCK_NAME,
				[
					'name'       => $name,
					'label'      => $label,
					'is_default' => $is_default,
				]
			);
		}
	}

	/**
	 * Applies the design settings to the rendered block.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block.
	 */
	public function render_block( string $block_content, array $block ): string {
		$attributes = $block['attrs'] ?? [];
		$settings   = self::settings( $attributes );

		if ( self::is_unstyled( $attributes ) ) {
			return $block_content;
		}

		if ( $settings['themeButton'] ) {
			$block_content = self::mark_theme_button( $block_content );
		}

		$processor = new WP_HTML_Tag_Processor( $block_content );

		if ( ! $processor->next_tag() ) {
			return $block_content;
		}

		foreach ( self::wrapper_classes( $settings ) as $class_name ) {
			$processor->add_class( $class_name );
		}

		$style = self::wrapper_style( $settings );

		if ( '' !== $style ) {
			$existing = $processor->get_attribute( 'style' );
			$existing = is_string( $existing ) ? rtrim( trim( $existing ), ';' ) . ';' : '';

			$processor->set_attribute( 'style', ltrim( $existing . $style, ';' ) );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Gives the submit button the theme's button styles (Global Styles → Elements → Button).
	 *
	 * @param string       $output Shortcode output.
	 * @param string       $tag    Shortcode name.
	 * @param array|string $attr   Shortcode attributes.
	 */
	public function apply_theme_button( $output, $tag, $attr ) {
		if ( 'contact-form-7' !== $tag || ! is_string( $output ) || empty( $attr[ self::THEME_BUTTON_MARKER ] ) ) {
			return $output;
		}

		$processor = new WP_HTML_Tag_Processor( $output );

		while ( $processor->next_tag( [ 'class_name' => 'wpcf7-submit' ] ) ) {
			$processor->add_class( 'wp-element-button' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * Loads the block editor extension.
	 */
	public function enqueue_editor_assets(): void {
		$asset = Plugin::asset_file( 'editor' );

		wp_enqueue_script(
			'cf7at-editor',
			Plugin::url( 'build/editor.js' ),
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_add_inline_script(
			'cf7at-editor',
			'window.cf7atEditor = ' . wp_json_encode(
				[
					'formsUrl' => admin_url( 'admin.php?page=wpcf7' ),
					'canEdit'  => current_user_can( 'wpcf7_edit_contact_forms' ),
				]
			) . ';',
			'before'
		);

		wp_set_script_translations( 'cf7at-editor', 'cf7-additional-types', Plugin::path( 'languages' ) );
	}

	/**
	 * Registers the endpoint that renders a form for the editor preview.
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'cf7at/v1',
			'/form-preview',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'rest_form_preview' ],
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => [
					'id'          => [
						'type'     => 'string',
						'required' => true,
						'pattern'  => '^[0-9a-fA-F]+$',
					],
					'themeButton' => [
						'type'    => 'boolean',
						'default' => true,
					],
				],
			]
		);
	}

	/**
	 * Renders a contact form for the editor preview.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function rest_form_preview( WP_REST_Request $request ): WP_REST_Response {
		$shortcode = sprintf(
			'[contact-form-7 id="%1$s"%2$s]',
			esc_attr( $request['id'] ),
			$request['themeButton'] ? ' ' . self::THEME_BUTTON_MARKER . '="1"' : ''
		);

		return new WP_REST_Response( [ 'html' => do_shortcode( $shortcode ) ] );
	}

	/**
	 * Normalized design settings from the block attributes.
	 *
	 * @param array $attributes Block attributes.
	 * @return array{fieldSize: string, buttonWidth: string, themeButton: bool, colors: array<string, string>, lengths: array<string, string>}
	 */
	public static function settings( array $attributes ): array {
		$raw = isset( $attributes[ self::ATTRIBUTE ] ) && is_array( $attributes[ self::ATTRIBUTE ] )
			? $attributes[ self::ATTRIBUTE ]
			: [];

		$colors = [];

		foreach ( array_keys( self::COLOR_PROPERTIES ) as $key ) {
			$value = isset( $raw[ $key ] ) ? self::sanitize_color( (string) $raw[ $key ] ) : '';

			if ( '' !== $value ) {
				$colors[ $key ] = $value;
			}
		}

		$lengths = [];

		foreach ( array_keys( self::LENGTH_PROPERTIES ) as $key ) {
			$value = isset( $raw[ $key ] ) ? self::sanitize_length( (string) $raw[ $key ] ) : '';

			if ( '' !== $value ) {
				$lengths[ $key ] = $value;
			}
		}

		return [
			'fieldSize'   => in_array( $raw['fieldSize'] ?? '', [ 'small', 'large' ], true ) ? $raw['fieldSize'] : 'medium',
			'buttonWidth' => 'full' === ( $raw['buttonWidth'] ?? '' ) ? 'full' : 'auto',
			'themeButton' => false !== ( $raw['themeButton'] ?? true ),
			'colors'      => $colors,
			'lengths'     => $lengths,
		];
	}

	/**
	 * Whether the block uses the "Unstyled" preset.
	 *
	 * @param array $attributes Block attributes.
	 */
	private static function is_unstyled( array $attributes ): bool {
		$class_names = explode( ' ', (string) ( $attributes['className'] ?? '' ) );

		return in_array( 'is-style-unstyled', $class_names, true );
	}

	/**
	 * Classes for the block wrapper.
	 *
	 * @param array $settings Normalized settings.
	 * @return string[]
	 */
	private static function wrapper_classes( array $settings ): array {
		$classes = [ 'cf7at-form' ];

		if ( 'medium' !== $settings['fieldSize'] ) {
			$classes[] = 'cf7at-size-' . $settings['fieldSize'];
		}

		if ( 'full' === $settings['buttonWidth'] ) {
			$classes[] = 'cf7at-button-full';
		}

		if ( $settings['themeButton'] ) {
			$classes[] = 'cf7at-theme-button';
		}

		return $classes;
	}

	/**
	 * Inline CSS custom properties for the block wrapper.
	 *
	 * @param array $settings Normalized settings.
	 */
	private static function wrapper_style( array $settings ): string {
		$declarations = [];

		foreach ( $settings['colors'] as $key => $value ) {
			$declarations[] = self::COLOR_PROPERTIES[ $key ] . ':' . $value;
		}

		foreach ( $settings['lengths'] as $key => $value ) {
			$declarations[] = self::LENGTH_PROPERTIES[ $key ] . ':' . $value;
		}

		return implode( ';', $declarations );
	}

	/**
	 * Adds the theme button marker to the block's Contact Form 7 shortcode.
	 *
	 * @param string $block_content Block HTML with the shortcode.
	 */
	private static function mark_theme_button( string $block_content ): string {
		return (string) preg_replace(
			'/\[contact-form-7\b([^\]]*)\]/',
			'[contact-form-7$1 ' . self::THEME_BUTTON_MARKER . '="1"]',
			$block_content,
			1
		);
	}

	/**
	 * Accepts hex, rgb(), hsl(), theme palette variables and a few keywords.
	 *
	 * @param string $value Color value.
	 */
	public static function sanitize_color( string $value ): string {
		$value = trim( $value );

		$patterns = [
			'/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i',
			'/^(?:rgb|hsl)a?\(\s*[0-9.%\s,\/]+\)$/i',
			'/^var\(--wp--preset--color--[a-z0-9-]+\)$/',
			'/^(?:transparent|currentcolor)$/i',
		];

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Accepts CSS lengths and theme spacing variables.
	 *
	 * @param string $value Length value.
	 */
	public static function sanitize_length( string $value ): string {
		$value = trim( $value );

		if (
			preg_match( '/^(?:0|\d*\.?\d+(?:px|em|rem|%|vw|vh))$/', $value ) ||
			preg_match( '/^var\(--wp--preset--spacing--[a-z0-9-]+\)$/', $value )
		) {
			return $value;
		}

		return '';
	}
}

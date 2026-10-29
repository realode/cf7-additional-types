<?php
/**
 * Base class for the plugin's form-tag types.
 *
 * @package CF7AT
 */

namespace CF7AT\Fields;

use WPCF7_ContactForm;
use WPCF7_FormTag;
use WPCF7_TagGenerator;
use WPCF7_TagGeneratorGenerator;
use WPCF7_Validation;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a form-tag with Contact Form 7 and handles everything around it:
 * rendering, the tag generator, validation rules and the posted value.
 */
abstract class Field {

	/**
	 * Form-tag type without the required marker, e.g. "datepicker".
	 */
	abstract public function type(): string;

	/**
	 * Translated labels for the form editor.
	 *
	 * @return array{button: string, name: string, heading: string, description: string}
	 */
	abstract protected function labels(): array;

	/**
	 * Renders the field's HTML (without the wrapper).
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 */
	abstract protected function render( WPCF7_FormTag $tag ): string;

	/**
	 * Prints the field's tag generator panel content.
	 *
	 * @param WPCF7_TagGeneratorGenerator $tgg     CF7 helper for the standard generator parts.
	 * @param array                       $options Tag generator options.
	 */
	abstract protected function render_tag_generator_fields( WPCF7_TagGeneratorGenerator $tgg, array $options ): void;

	/**
	 * Whether the field can be marked as required with a trailing asterisk.
	 */
	public function supports_required(): bool {
		return true;
	}

	/**
	 * Adds field-specific validation rules to the form schema.
	 *
	 * @param object        $schema The form's validation schema (WPCF7_SWV_Schema).
	 * @param WPCF7_FormTag $tag    The form-tag.
	 */
	protected function add_rules( $schema, WPCF7_FormTag $tag ): void {}

	/**
	 * Validation messages the field adds to the form's "Messages" tab.
	 *
	 * @return array<string, array{description: string, default: string}>
	 */
	protected function messages(): array {
		return [];
	}

	/**
	 * Converts the submitted value into the value used in emails and storage.
	 *
	 * @param mixed         $value      Sanitized posted value.
	 * @param mixed         $value_orig Original posted value.
	 * @param WPCF7_FormTag $tag        The form-tag.
	 * @return mixed
	 */
	public function format_posted_value( $value, $value_orig, WPCF7_FormTag $tag ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Filter signature for subclasses.
		return $value;
	}

	/**
	 * Validation that cannot be expressed as schema rules.
	 *
	 * @param WPCF7_Validation $result Validation result.
	 * @param WPCF7_FormTag    $tag    The form-tag.
	 */
	public function validate( WPCF7_Validation $result, WPCF7_FormTag $tag ): WPCF7_Validation { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Filter signature for subclasses.
		return $result;
	}

	/**
	 * Registers the WordPress and Contact Form 7 hooks.
	 */
	public function hook(): void {
		add_action( 'wpcf7_init', [ $this, 'register_form_tag' ] );
		add_action( 'wpcf7_admin_init', [ $this, 'register_tag_generator' ], 30 );
		add_action( 'wpcf7_swv_create_schema', [ $this, 'create_schema' ], 10, 2 );
		add_filter( 'wpcf7_messages', [ $this, 'register_messages' ] );

		foreach ( $this->tag_types() as $tag_type ) {
			add_filter( "wpcf7_posted_data_{$tag_type}", [ $this, 'format_posted_value' ], 10, 3 );
			add_filter( "wpcf7_validate_{$tag_type}", [ $this, 'validate' ], 10, 2 );
		}
	}

	/**
	 * Form-tag types handled by this field, e.g. ["datepicker", "datepicker*"].
	 *
	 * @return string[]
	 */
	public function tag_types(): array {
		$types = [ $this->type() ];

		if ( $this->supports_required() ) {
			$types[] = $this->type() . '*';
		}

		return $types;
	}

	/**
	 * Registers the form-tag with Contact Form 7.
	 */
	public function register_form_tag(): void {
		wpcf7_add_form_tag(
			$this->tag_types(),
			[ $this, 'render_form_tag' ],
			[ 'name-attr' => true ]
		);
	}

	/**
	 * Form-tag handler: renders the field inside CF7's wrapper.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 */
	public function render_form_tag( WPCF7_FormTag $tag ): string {
		if ( empty( $tag->name ) ) {
			return '';
		}

		wp_enqueue_style( 'cf7at-style' );
		wp_enqueue_script( 'cf7at-fields' );

		return sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%1$s">%2$s%3$s</span>',
			esc_attr( $tag->name ),
			$this->render( $tag ),
			wpcf7_get_validation_error( $tag->name )
		);
	}

	/**
	 * Adds the tag generator button and panel to the form editor.
	 */
	public function register_tag_generator(): void {
		WPCF7_TagGenerator::get_instance()->add(
			$this->type(),
			$this->labels()['button'],
			[ $this, 'render_tag_generator' ],
			[ 'version' => '2' ]
		);
	}

	/**
	 * Tag generator callback.
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form being edited.
	 * @param array             $options      Tag generator options.
	 */
	public function render_tag_generator( WPCF7_ContactForm $contact_form, array $options ): void {
		$tgg    = new WPCF7_TagGeneratorGenerator( $options['content'] );
		$labels = $this->labels();

		printf(
			'<header class="description-box"><h3>%1$s</h3><p>%2$s</p></header>',
			esc_html( $labels['heading'] ),
			esc_html( $labels['description'] )
		);

		echo '<div class="control-box">';

		$tgg->print(
			'field_type',
			[
				'with_required'  => $this->supports_required(),
				'select_options' => [ $this->type() => $labels['name'] ],
			]
		);

		$tgg->print( 'field_name' );
		$this->render_tag_generator_fields( $tgg, $options );
		$tgg->print( 'class_attr' );

		echo '</div><footer class="insert-box">';

		$tgg->print( 'insert_box_content' );
		$tgg->print( 'mail_tag_tip' );

		echo '</footer>';
	}

	/**
	 * Builds the validation schema for every instance of this field in a form.
	 *
	 * @param object            $schema       The form's validation schema (WPCF7_SWV_Schema).
	 * @param WPCF7_ContactForm $contact_form The contact form.
	 */
	public function create_schema( $schema, WPCF7_ContactForm $contact_form ): void {
		$tags = $contact_form->scan_form_tags( [ 'basetype' => $this->type() ] );

		foreach ( $tags as $tag ) {
			if ( $tag->is_required() ) {
				$schema->add_rule(
					wpcf7_swv_create_rule(
						'required',
						[
							'field' => $tag->name,
							'error' => wpcf7_get_message( 'invalid_required' ),
						]
					)
				);
			}

			$this->add_rules( $schema, $tag );
		}
	}

	/**
	 * Adds the field's messages to Contact Form 7.
	 *
	 * @param array $messages Registered messages.
	 */
	public function register_messages( array $messages ): array {
		return array_merge( $messages, $this->messages() );
	}

	/**
	 * Attributes every field control shares: classes, id, tab index and ARIA state.
	 *
	 * @param WPCF7_FormTag $tag           The form-tag.
	 * @param string        $extra_classes Additional classes for the control.
	 * @return array<string, mixed>
	 */
	protected function control_atts( WPCF7_FormTag $tag, string $extra_classes = '' ): array {
		$validation_error = wpcf7_get_validation_error( $tag->name );

		$classes = wpcf7_form_controls_class( $tag->type, $extra_classes );

		if ( $validation_error ) {
			$classes .= ' wpcf7-not-valid';
		}

		$atts = [
			'class'        => $tag->get_class_option( $classes ),
			'id'           => $tag->get_id_option(),
			'tabindex'     => $tag->get_option( 'tabindex', 'signed_int', true ),
			'aria-invalid' => $validation_error ? 'true' : 'false',
		];

		if ( $validation_error ) {
			$atts['aria-describedby'] = wpcf7_get_validation_error_reference( $tag->name );
		}

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}

		return $atts;
	}

	/**
	 * Reads a text option whose spaces were written as underscores, e.g. "suffix:_km".
	 *
	 * @param WPCF7_FormTag $tag  The form-tag.
	 * @param string        $name Option name.
	 */
	protected function text_option( WPCF7_FormTag $tag, string $name ): string {
		$value = $tag->get_option( $name, '', true );

		return is_string( $value ) ? str_replace( '_', ' ', $value ) : '';
	}

	/**
	 * The site locale as a BCP 47 language tag for the browser's Intl APIs.
	 */
	protected function js_locale(): string {
		$locale = determine_locale();

		if ( preg_match( '/^([a-z]{2,3})(?:_([A-Z]{2}))?/', $locale, $matches ) ) {
			return isset( $matches[2] ) ? $matches[1] . '-' . $matches[2] : $matches[1];
		}

		return 'en';
	}

	/**
	 * Prints a text input fieldset that maps to a form-tag option.
	 *
	 * @param array $args {
	 *     Fieldset arguments.
	 *
	 *     @type string $legend      Fieldset legend.
	 *     @type string $option      Form-tag option, including the trailing colon.
	 *     @type string $type        Input type. Default "text".
	 *     @type string $description Optional help text.
	 *     @type array  $atts        Extra input attributes.
	 * }
	 */
	protected function print_option_input( array $args ): void {
		$args = wp_parse_args(
			$args,
			[
				'legend'      => '',
				'option'      => '',
				'type'        => 'text',
				'description' => '',
				'atts'        => [],
			]
		);

		$atts = array_merge(
			[
				'type'            => $args['type'],
				'data-tag-part'   => 'option',
				'data-tag-option' => $args['option'],
			],
			$args['atts']
		);

		echo '<fieldset><legend>' . esc_html( $args['legend'] ) . '</legend>';
		echo '<input ' . wpcf7_format_atts( $atts ) . ' />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wpcf7_format_atts() escapes attribute values.

		if ( $args['description'] ) {
			echo '<br /><small>' . esc_html( $args['description'] ) . '</small>';
		}

		echo '</fieldset>';
	}

	/**
	 * Prints a checkbox that adds a flag option to the form-tag.
	 *
	 * @param string $legend Fieldset legend.
	 * @param string $option Form-tag option.
	 * @param string $label  Checkbox label.
	 */
	protected function print_option_checkbox( string $legend, string $option, string $label ): void {
		printf(
			'<fieldset><legend>%1$s</legend><label><input type="checkbox" data-tag-part="option" data-tag-option="%2$s" /> %3$s</label></fieldset>',
			esc_html( $legend ),
			esc_attr( $option ),
			esc_html( $label )
		);
	}
}

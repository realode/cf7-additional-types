<?php
/**
 * Shared behaviour of the date and time pickers.
 *
 * @package CF7AT
 */

namespace CF7AT\Fields;

use WPCF7_FormTag;
use WPCF7_TagGeneratorGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * Pickers render a native input that works without JavaScript and submits a
 * machine-readable value (ISO date or 24-hour time). The front-end script
 * swaps it for a styled control with a popover, and emails receive the value
 * in the site's date or time format.
 */
abstract class Picker_Field extends Field {

	/**
	 * Native input type, "date" or "time".
	 */
	abstract protected function input_type(): string;

	/**
	 * Converts a form-tag value into the machine format, or '' if it is not valid.
	 *
	 * @param string $value Value from the form-tag or a prefill source.
	 */
	abstract protected function normalize( string $value ): string;

	/**
	 * Extra attributes for the native input, e.g. min, max and step.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return array<string, mixed>
	 */
	abstract protected function input_atts( WPCF7_FormTag $tag ): array;

	/**
	 * Data attributes that configure the enhanced control.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return array<string, mixed>
	 */
	abstract protected function picker_data( WPCF7_FormTag $tag ): array;

	/**
	 * {@inheritDoc}
	 */
	protected function render( WPCF7_FormTag $tag ): string {
		$atts = array_merge(
			$this->control_atts( $tag, "cf7at-picker__input cf7at-{$this->input_type()}__input" ),
			$this->input_atts( $tag )
		);

		$atts['type']         = $this->input_type();
		$atts['name']         = $tag->name;
		$atts['value']        = $this->normalize( (string) wpcf7_get_hangover( $tag->name, $this->default_value( $tag ) ) );
		$atts['readonly']     = $tag->has_option( 'readonly' );
		$atts['autocomplete'] = $tag->get_autocomplete_option();
		$atts['placeholder']  = $this->placeholder( $tag );

		$data = array_merge(
			[ 'data-cf7at' => $this->input_type() ],
			$this->picker_data( $tag )
		);

		return sprintf(
			'<span class="cf7at-picker cf7at-%1$s" %2$s><input %3$s /></span>',
			esc_attr( $this->input_type() ),
			wpcf7_format_atts( $data ),
			wpcf7_format_atts( $atts )
		);
	}

	/**
	 * The preselected value in machine format, or ''.
	 *
	 * Supports the form-tag value (`[datepicker d "2026-12-24"]`), the 2.x
	 * `default:` option and Contact Form 7's prefill sources such as `default:get`.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 */
	protected function default_value( WPCF7_FormTag $tag ): string {
		$value = '';

		if ( ! $tag->has_option( 'placeholder' ) && $tag->values ) {
			$value = (string) reset( $tag->values );
		}

		if ( '' === $value ) {
			$legacy = $tag->get_option( 'default', '', true );

			if ( is_string( $legacy ) && '' !== $this->normalize( $legacy ) ) {
				$value = $legacy;
			}
		}

		return $this->normalize( (string) $tag->get_default_option( $value ) );
	}

	/**
	 * Placeholder text, set with `[datepicker d placeholder "Pick a date"]`.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return string|null
	 */
	protected function placeholder( WPCF7_FormTag $tag ) {
		if ( $tag->has_option( 'placeholder' ) && $tag->values ) {
			return (string) reset( $tag->values );
		}

		return null;
	}

	/**
	 * Prints the default value and placeholder fieldset.
	 *
	 * @param WPCF7_TagGeneratorGenerator $tgg         CF7 tag generator helper.
	 * @param string                      $description Help text about accepted values.
	 */
	protected function print_default_value_field( WPCF7_TagGeneratorGenerator $tgg, string $description ): void {
		printf(
			'<fieldset><legend id="%1$s">%2$s</legend><input type="text" data-tag-part="value" aria-labelledby="%1$s" /><br /><label><input type="checkbox" data-tag-part="option" data-tag-option="placeholder" /> %3$s</label><br /><small>%4$s</small></fieldset>',
			esc_attr( $tgg->ref( 'value-legend' ) ),
			esc_html__( 'Default value', 'cf7-additional-types' ),
			esc_html__( 'Use this text as the placeholder.', 'cf7-additional-types' ),
			esc_html( $description )
		);
	}
}

<?php
/**
 * Time picker field: [timepicker] and [timepicker*].
 *
 * @package CF7AT
 */

namespace CF7AT\Fields;

use DateTimeImmutable;
use DateTimeZone;
use WPCF7_FormTag;
use WPCF7_TagGeneratorGenerator;
use WPCF7_Validation;

defined( 'ABSPATH' ) || exit;

/**
 * A list of times to choose from, in the site's 12- or 24-hour format.
 *
 * Options:
 * - `min:09:00` / `max:17:30` – earliest and latest time.
 * - `step:15` – minutes between the times in the list (default 30).
 * - A quoted value sets the default time, or the placeholder with `placeholder`.
 */
final class Time_Picker extends Picker_Field {

	const DEFAULT_STEP = 30;

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'timepicker';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_type(): string {
		return 'time';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function labels(): array {
		return [
			'button'      => __( 'time picker', 'cf7-additional-types' ),
			'name'        => __( 'Time picker', 'cf7-additional-types' ),
			'heading'     => __( 'Time picker form-tag generator', 'cf7-additional-types' ),
			'description' => __( 'Generates a form-tag for a time field with a list of times in the site’s time format.', 'cf7-additional-types' ),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function normalize( string $value ): string {
		$value = trim( str_replace( '_', ' ', $value ) );

		if ( preg_match( '/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $value, $m ) ) {
			return $m[1] . ':' . $m[2];
		}

		if ( '' === $value || ! preg_match( '/\d/', $value ) ) {
			return '';
		}

		$time = date_create_immutable( $value, wp_timezone() );

		return $time ? $time->format( 'H:i' ) : '';
	}

	/**
	 * Time limits and step.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return array{min: string, max: string, step: int}
	 */
	private function time_range( WPCF7_FormTag $tag ): array {
		$pattern = '[0-9]{1,2}:[0-9]{2}';
		$min     = $this->normalize( (string) $tag->get_option( 'min', $pattern, true ) );
		$max     = $this->normalize( (string) $tag->get_option( 'max', $pattern, true ) );
		$step    = (int) $tag->get_option( 'step', 'int', true );

		return [
			'min'  => $min,
			'max'  => $max,
			'step' => $step > 0 && $step <= 720 ? $step : self::DEFAULT_STEP,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_atts( WPCF7_FormTag $tag ): array {
		$range = $this->time_range( $tag );

		return [
			'min'  => $range['min'] ? $range['min'] : null,
			'max'  => $range['max'] ? $range['max'] : null,
			'step' => $range['step'] * MINUTE_IN_SECONDS,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function picker_data( WPCF7_FormTag $tag ): array {
		return [
			'data-locale' => $this->js_locale(),
			'data-hour12' => preg_match( '/(?<!\\\\)[aAgh]/', (string) get_option( 'time_format' ) ) ? 'true' : 'false',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function add_rules( $schema, WPCF7_FormTag $tag ): void {
		$schema->add_rule(
			wpcf7_swv_create_rule(
				'time',
				[
					'field' => $tag->name,
					'error' => wpcf7_get_message( 'cf7at_invalid_time' ),
				]
			)
		);
	}

	/**
	 * Checks the earliest and latest time, which have no schema rule in Contact Form 7.
	 *
	 * @param WPCF7_Validation $result Validation result.
	 * @param WPCF7_FormTag    $tag    The form-tag.
	 */
	public function validate( WPCF7_Validation $result, WPCF7_FormTag $tag ): WPCF7_Validation {
		$value = $this->normalize( (string) wpcf7_superglobal_post( $tag->name, '' ) );

		if ( '' === $value ) {
			return $result;
		}

		$range = $this->time_range( $tag );

		if ( $range['min'] && $value < $range['min'] ) {
			$result->invalidate( $tag, wpcf7_get_message( 'cf7at_time_too_early' ) );
		} elseif ( $range['max'] && $value > $range['max'] ) {
			$result->invalidate( $tag, wpcf7_get_message( 'cf7at_time_too_late' ) );
		}

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function messages(): array {
		return [
			'cf7at_invalid_time'   => [
				'description' => __( 'Time picker value is not a valid time', 'cf7-additional-types' ),
				'default'     => __( 'Please choose a valid time.', 'cf7-additional-types' ),
			],
			'cf7at_time_too_early' => [
				'description' => __( 'Time is earlier than the time picker allows', 'cf7-additional-types' ),
				'default'     => __( 'This time is too early.', 'cf7-additional-types' ),
			],
			'cf7at_time_too_late'  => [
				'description' => __( 'Time is later than the time picker allows', 'cf7-additional-types' ),
				'default'     => __( 'This time is too late.', 'cf7-additional-types' ),
			],
		];
	}

	/**
	 * Emails and stored submissions receive the time in the site's time format.
	 *
	 * @param mixed         $value      Sanitized posted value.
	 * @param mixed         $value_orig Original posted value.
	 * @param WPCF7_FormTag $tag        The form-tag.
	 * @return mixed
	 */
	public function format_posted_value( $value, $value_orig, WPCF7_FormTag $tag ) {
		$time = is_string( $value ) ? $this->normalize( $value ) : '';

		if ( '' === $time ) {
			return $value;
		}

		$utc  = new DateTimeZone( 'UTC' );
		$date = DateTimeImmutable::createFromFormat( '!H:i', $time, $utc );

		return $date ? wp_date( get_option( 'time_format' ), $date->getTimestamp(), $utc ) : $value;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render_tag_generator_fields( WPCF7_TagGeneratorGenerator $tgg, array $options ): void {
		echo '<fieldset><legend>' . esc_html__( 'Range', 'cf7-additional-types' ) . '</legend>';

		foreach (
			[
				'min:' => __( 'Earliest', 'cf7-additional-types' ),
				'max:' => __( 'Latest', 'cf7-additional-types' ),
			] as $option => $label
		) {
			printf(
				'<label>%1$s <input type="time" data-tag-part="option" data-tag-option="%2$s" /></label> ',
				esc_html( $label ),
				esc_attr( $option )
			);
		}

		echo '</fieldset>';

		$this->print_option_input(
			[
				'legend'      => __( 'Interval', 'cf7-additional-types' ),
				'option'      => 'step:',
				'type'        => 'number',
				'description' => __( 'Minutes between the times in the list. Default: 30.', 'cf7-additional-types' ),
				'atts'        => [
					'min' => 1,
					'max' => 720,
				],
			]
		);

		$this->print_default_value_field( $tgg, __( 'A time in 24-hour format, e.g. 14:30.', 'cf7-additional-types' ) );
	}
}

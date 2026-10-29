<?php
/**
 * Date picker field: [datepicker] and [datepicker*].
 *
 * @package CF7AT
 */

namespace CF7AT\Fields;

use DateTimeImmutable;
use WPCF7_FormTag;
use WPCF7_TagGeneratorGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * A calendar popover, localized with the site language and week start.
 *
 * Options:
 * - `min:` / `max:` – a date (2026-12-31) or a relative date (today, +1_week, -18_years).
 * - `years:80` or `years:1990-2010` – the 2.x year range, used when min/max are absent.
 * - `no_weekends` – Saturdays and Sundays cannot be chosen.
 * - A quoted value sets the default date, or the placeholder with `placeholder`.
 */
final class Date_Picker extends Picker_Field {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'datepicker';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_type(): string {
		return 'date';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function labels(): array {
		return [
			'button'      => __( 'date picker', 'cf7-additional-types' ),
			'name'        => __( 'Date picker', 'cf7-additional-types' ),
			'heading'     => __( 'Date picker form-tag generator', 'cf7-additional-types' ),
			'description' => __( 'Generates a form-tag for a date field with a calendar that follows the site language and week start.', 'cf7-additional-types' ),
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function normalize( string $value ): string {
		$value = trim( str_replace( '_', ' ', $value ) );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
			return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $value : '';
		}

		// Dates written in the site's date format, as the 2.x tag generator did.
		$date   = DateTimeImmutable::createFromFormat( '!' . get_option( 'date_format' ), $value, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();

		if ( ! $date || ( is_array( $errors ) && $errors['warning_count'] + $errors['error_count'] > 0 ) ) {
			$date = date_create_immutable( $value, wp_timezone() );
		}

		return $date ? $date->format( 'Y-m-d' ) : '';
	}

	/**
	 * Earliest and latest selectable dates as Y-m-d, or '' for no limit.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return array{min: string, max: string}
	 */
	private function date_range( WPCF7_FormTag $tag ): array {
		$min = $tag->get_date_option( 'min' );
		$max = $tag->get_date_option( 'max' );

		$years = $tag->get_option( 'years', '[0-9]+(?:-[0-9]+)?', true );

		if ( $years && ! $min && ! $max ) {
			$this_year = (int) wp_date( 'Y' );

			if ( false !== strpos( $years, '-' ) ) {
				list( $from, $to ) = array_map( 'intval', explode( '-', $years ) );
			} else {
				$from = $this_year - (int) $years;
				$to   = $this_year;
			}

			$min = sprintf( '%04d-01-01', min( $from, $to ) );
			$max = sprintf( '%04d-12-31', max( $from, $to ) );
		}

		return [
			'min' => $min ? $min : '',
			'max' => $max ? $max : '',
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function input_atts( WPCF7_FormTag $tag ): array {
		$range = $this->date_range( $tag );

		return [
			'min' => $range['min'] ? $range['min'] : null,
			'max' => $range['max'] ? $range['max'] : null,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	protected function picker_data( WPCF7_FormTag $tag ): array {
		return [
			'data-locale'    => $this->js_locale(),
			'data-first-day' => (int) get_option( 'start_of_week', 1 ),
			'data-format'    => wp_json_encode( $this->intl_format( (string) get_option( 'date_format' ) ) ),
			'data-weekdays'  => $tag->has_option( 'no_weekends' ) ? '[1,2,3,4,5]' : null,
		];
	}

	/**
	 * Translates a PHP date format into Intl.DateTimeFormat options.
	 *
	 * The browser decides the order of the parts for the site language, so
	 * "F j, Y" and "j. F Y" both become { day, month: "long", year }.
	 *
	 * @param string $php_format PHP date format.
	 * @return array<string, string>
	 */
	private function intl_format( string $php_format ): array {
		$map = [
			'd' => [ 'day', '2-digit' ],
			'j' => [ 'day', 'numeric' ],
			'D' => [ 'weekday', 'short' ],
			'l' => [ 'weekday', 'long' ],
			'F' => [ 'month', 'long' ],
			'M' => [ 'month', 'short' ],
			'm' => [ 'month', '2-digit' ],
			'n' => [ 'month', 'numeric' ],
			'Y' => [ 'year', 'numeric' ],
			'o' => [ 'year', 'numeric' ],
			'y' => [ 'year', '2-digit' ],
		];

		$options = [];
		$length  = strlen( $php_format );

		for ( $i = 0; $i < $length; $i++ ) {
			$char = $php_format[ $i ];

			if ( '\\' === $char ) {
				++$i;
				continue;
			}

			if ( isset( $map[ $char ] ) && ! isset( $options[ $map[ $char ][0] ] ) ) {
				$options[ $map[ $char ][0] ] = $map[ $char ][1];
			}
		}

		if ( ! isset( $options['day'], $options['month'], $options['year'] ) ) {
			return [
				'day'   => 'numeric',
				'month' => 'long',
				'year'  => 'numeric',
			];
		}

		return $options;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function add_rules( $schema, WPCF7_FormTag $tag ): void {
		$range = $this->date_range( $tag );

		$schema->add_rule(
			wpcf7_swv_create_rule(
				'date',
				[
					'field' => $tag->name,
					'error' => wpcf7_get_message( 'cf7at_invalid_date' ),
				]
			)
		);

		if ( $range['min'] ) {
			$schema->add_rule(
				wpcf7_swv_create_rule(
					'mindate',
					[
						'field'     => $tag->name,
						'threshold' => $range['min'],
						'error'     => wpcf7_get_message( 'date_too_early' ),
					]
				)
			);
		}

		if ( $range['max'] ) {
			$schema->add_rule(
				wpcf7_swv_create_rule(
					'maxdate',
					[
						'field'     => $tag->name,
						'threshold' => $range['max'],
						'error'     => wpcf7_get_message( 'date_too_late' ),
					]
				)
			);
		}

		if ( $tag->has_option( 'no_weekends' ) ) {
			$schema->add_rule(
				wpcf7_swv_create_rule(
					'dayofweek',
					[
						'field'  => $tag->name,
						'accept' => [ 1, 2, 3, 4, 5 ],
						'error'  => wpcf7_get_message( 'cf7at_unavailable_day' ),
					]
				)
			);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	protected function messages(): array {
		return [
			'cf7at_invalid_date'    => [
				'description' => __( 'Date picker value is not a valid date', 'cf7-additional-types' ),
				'default'     => __( 'Please choose a valid date.', 'cf7-additional-types' ),
			],
			'cf7at_unavailable_day' => [
				'description' => __( 'Date picker value falls on a day that cannot be chosen', 'cf7-additional-types' ),
				'default'     => __( 'This day is not available. Please choose another date.', 'cf7-additional-types' ),
			],
		];
	}

	/**
	 * Emails and stored submissions receive the date in the site's date format.
	 *
	 * @param mixed         $value      Sanitized posted value.
	 * @param mixed         $value_orig Original posted value.
	 * @param WPCF7_FormTag $tag        The form-tag.
	 * @return mixed
	 */
	public function format_posted_value( $value, $value_orig, WPCF7_FormTag $tag ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
			return $value;
		}

		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, wp_timezone() );

		return $date ? wp_date( get_option( 'date_format' ), $date->getTimestamp() ) : $value;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render_tag_generator_fields( WPCF7_TagGeneratorGenerator $tgg, array $options ): void {
		$date_help = __( 'A date (YYYY-MM-DD) or a relative date such as today, +1_week or -18_years.', 'cf7-additional-types' );

		$this->print_option_input(
			[
				'legend' => __( 'Earliest date', 'cf7-additional-types' ),
				'option' => 'min:',
				'atts'   => [
					'pattern'     => '\S*',
					'placeholder' => 'today',
				],
			]
		);

		$this->print_option_input(
			[
				'legend'      => __( 'Latest date', 'cf7-additional-types' ),
				'option'      => 'max:',
				'description' => $date_help,
				'atts'        => [
					'pattern'     => '\S*',
					'placeholder' => '+6_months',
				],
			]
		);

		$this->print_default_value_field( $tgg, __( 'A date or a relative date, as above.', 'cf7-additional-types' ) );

		$this->print_option_checkbox(
			__( 'Available days', 'cf7-additional-types' ),
			'no_weekends',
			__( 'Weekdays only (no Saturdays or Sundays).', 'cf7-additional-types' )
		);
	}
}

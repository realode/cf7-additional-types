<?php
/**
 * Range slider field: [rangeslider].
 *
 * @package CF7AT
 */

namespace CF7AT\Fields;

use WPCF7_FormTag;
use WPCF7_TagGeneratorGenerator;

defined( 'ABSPATH' ) || exit;

/**
 * A slider in one of two modes:
 *
 * - Labelled steps: `[rangeslider size default:2 "Small" "Medium" "Large"]`.
 *   The browser submits the step index; emails receive the step label.
 *   `default:` is the 1-based position of the preselected label, as in 2.x.
 * - Numeric: `[rangeslider budget min:500 max:10000 step:500 currency:EUR]`.
 *   `currency:` takes an ISO 4217 code and formats the number for the site
 *   language. `prefix:` and `suffix:` add plain text such as `suffix:%`
 *   (form-tag options only allow ASCII, so use `currency:` for € or £).
 *
 * The `ticks` option prints all labels (or the min and max) under the track.
 */
final class Range_Slider extends Field {

	/**
	 * {@inheritDoc}
	 */
	public function type(): string {
		return 'rangeslider';
	}

	/**
	 * {@inheritDoc}
	 */
	public function supports_required(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function labels(): array {
		return [
			'button'      => __( 'range slider', 'cf7-additional-types' ),
			'name'        => __( 'Range slider', 'cf7-additional-types' ),
			'heading'     => __( 'Range slider form-tag generator', 'cf7-additional-types' ),
			'description' => __( 'Generates a form-tag for a slider. Enter step labels for a slider with named steps, or leave them empty for a numeric slider.', 'cf7-additional-types' ),
		];
	}

	/**
	 * Resolves the slider configuration from the form-tag options.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return array{labels: string[], min: float, max: float, step: float, value: float, prefix: string, suffix: string, currency: string}
	 */
	private function settings( WPCF7_FormTag $tag ): array {
		$labels = $this->step_labels( $tag );

		if ( $labels ) {
			$position = (int) $tag->get_option( 'default', 'int', true );
			$index    = ( $position >= 1 && $position <= count( $labels ) ) ? $position - 1 : 0;

			return [
				'labels'   => $labels,
				'min'      => 0,
				'max'      => count( $labels ) - 1,
				'step'     => 1,
				'value'    => $index,
				'prefix'   => '',
				'suffix'   => '',
				'currency' => '',
			];
		}

		$min  = $this->number_option( $tag, 'min', 'signed_num', 0 );
		$max  = $this->number_option( $tag, 'max', 'signed_num', 100 );
		$step = $this->number_option( $tag, 'step', 'num', 1 );

		if ( $max <= $min ) {
			$max = $min + 100;
		}

		$default = $tag->get_option( 'default', 'signed_num', true );
		$value   = is_numeric( $default ) ? min( max( (float) $default, $min ), $max ) : $min;

		return [
			'labels'   => [],
			'min'      => $min,
			'max'      => $max,
			'step'     => $step > 0 ? $step : 1,
			'value'    => $value,
			'prefix'   => $this->text_option( $tag, 'prefix' ),
			'suffix'   => $this->text_option( $tag, 'suffix' ),
			'currency' => strtoupper( (string) $tag->get_option( 'currency', '[a-zA-Z]{3}', true ) ),
		];
	}

	/**
	 * Reads a numeric option.
	 *
	 * @param WPCF7_FormTag $tag      The form-tag.
	 * @param string        $name     Option name.
	 * @param string        $pattern  CF7 option pattern, "num" or "signed_num".
	 * @param float         $fallback Value when the option is missing.
	 */
	private function number_option( WPCF7_FormTag $tag, string $name, string $pattern, float $fallback ): float {
		$value = $tag->get_option( $name, $pattern, true );

		return is_numeric( $value ) ? (float) $value : $fallback;
	}

	/**
	 * Step labels from the form-tag values and the `data:` option.
	 *
	 * @param WPCF7_FormTag $tag The form-tag.
	 * @return string[]
	 */
	private function step_labels( WPCF7_FormTag $tag ): array {
		$values = (array) $tag->values;
		$data   = (array) $tag->get_data_option();

		if ( $data ) {
			$values = array_merge( $values, array_values( $data ) );
		}

		$values = array_map( 'strval', $values );

		return array_values(
			array_filter(
				$values,
				static function ( $value ) {
					return '' !== trim( $value );
				}
			)
		);
	}

	/**
	 * Formats a numeric value for display, using the site locale.
	 *
	 * @param float $value    The number.
	 * @param array $settings Slider settings.
	 */
	private function display_number( float $value, array $settings ): string {
		$decimals = 0;
		$step     = (string) $settings['step'];

		if ( false !== strpos( $step, '.' ) ) {
			$decimals = strlen( substr( $step, strpos( $step, '.' ) + 1 ) );
		}

		if ( '' !== $settings['currency'] ) {
			$number = $this->format_currency( $value, $settings['currency'], $decimals );
		} else {
			$number = number_format_i18n( $value, $decimals );
		}

		return $settings['prefix'] . $number . $settings['suffix'];
	}

	/**
	 * Formats an amount of money for the site locale.
	 *
	 * The front-end script reformats it with Intl.NumberFormat, so the
	 * fallback without the intl extension only matters without JavaScript.
	 *
	 * @param float  $value    The amount.
	 * @param string $currency ISO 4217 currency code.
	 * @param int    $decimals Fraction digits.
	 */
	private function format_currency( float $value, string $currency, int $decimals ): string {
		if ( class_exists( 'NumberFormatter' ) ) {
			$formatter = new \NumberFormatter( determine_locale(), \NumberFormatter::CURRENCY );
			$formatter->setAttribute( \NumberFormatter::MIN_FRACTION_DIGITS, $decimals );
			$formatter->setAttribute( \NumberFormatter::MAX_FRACTION_DIGITS, $decimals );

			$formatted = $formatter->formatCurrency( $value, $currency );

			if ( false !== $formatted ) {
				return $formatted;
			}
		}

		return number_format_i18n( $value, $decimals ) . ' ' . $currency;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render( WPCF7_FormTag $tag ): string {
		$settings = $this->settings( $tag );
		$atts     = $this->control_atts( $tag, 'cf7at-range__input' );

		if ( empty( $atts['id'] ) ) {
			$atts['id'] = wp_unique_id( 'cf7at-range-' );
		}

		$value = wpcf7_get_hangover( $tag->name, $settings['value'] );
		$value = is_numeric( $value ) ? (float) $value : $settings['value'];

		$atts['type']  = 'range';
		$atts['name']  = $tag->name;
		$atts['min']   = $settings['min'];
		$atts['max']   = $settings['max'];
		$atts['step']  = $settings['step'];
		$atts['value'] = $value;

		if ( $settings['labels'] ) {
			$index   = (int) $value;
			$display = $settings['labels'][ $index ] ?? $settings['labels'][0];

			$atts['data-labels'] = wp_json_encode( $settings['labels'] );
		} else {
			$display = $this->display_number( $value, $settings );

			$atts['data-prefix']   = '' !== $settings['prefix'] ? $settings['prefix'] : null;
			$atts['data-suffix']   = '' !== $settings['suffix'] ? $settings['suffix'] : null;
			$atts['data-currency'] = '' !== $settings['currency'] ? $settings['currency'] : null;
		}

		$atts['aria-valuetext'] = $display;

		$span     = $settings['max'] - $settings['min'];
		$progress = $span > 0 ? ( $value - $settings['min'] ) / $span : 0;

		return sprintf(
			'<span class="cf7at-range" data-cf7at="range" style="--cf7at-progress:%1$s"><span class="cf7at-range__track"><input %2$s /></span><output class="cf7at-range__output" for="%3$s" aria-hidden="true">%4$s</output>%5$s</span>',
			esc_attr( (string) round( $progress, 4 ) ),
			wpcf7_format_atts( $atts ),
			esc_attr( $atts['id'] ),
			esc_html( $display ),
			$tag->has_option( 'ticks' ) ? $this->render_ticks( $settings ) : ''
		);
	}

	/**
	 * Labels under the track: every step label, or the min and max value.
	 *
	 * @param array $settings Slider settings.
	 */
	private function render_ticks( array $settings ): string {
		if ( $settings['labels'] ) {
			$ticks = $settings['labels'];
		} else {
			$ticks = [
				$this->display_number( $settings['min'], $settings ),
				$this->display_number( $settings['max'], $settings ),
			];
		}

		$last  = max( count( $ticks ) - 1, 1 );
		$items = '';

		foreach ( $ticks as $i => $tick ) {
			$items .= sprintf(
				'<span class="cf7at-range__tick" style="--cf7at-tick:%1$s">%2$s</span>',
				esc_attr( (string) round( $i / $last, 4 ) ),
				esc_html( $tick )
			);
		}

		return '<span class="cf7at-range__ticks" aria-hidden="true">' . $items . '</span>';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function add_rules( $schema, WPCF7_FormTag $tag ): void {
		$settings = $this->settings( $tag );

		if ( $settings['labels'] ) {
			$schema->add_rule(
				wpcf7_swv_create_rule(
					'enum',
					[
						'field'  => $tag->name,
						'accept' => array_map( 'strval', array_keys( $settings['labels'] ) ),
						'error'  => wpcf7_get_message( 'cf7at_invalid_option' ),
					]
				)
			);

			return;
		}

		$schema->add_rule(
			wpcf7_swv_create_rule(
				'number',
				[
					'field' => $tag->name,
					'error' => wpcf7_get_message( 'invalid_number' ),
				]
			)
		);

		$schema->add_rule(
			wpcf7_swv_create_rule(
				'minnumber',
				[
					'field'     => $tag->name,
					'threshold' => $settings['min'],
					'error'     => wpcf7_get_message( 'number_too_small' ),
				]
			)
		);

		$schema->add_rule(
			wpcf7_swv_create_rule(
				'maxnumber',
				[
					'field'     => $tag->name,
					'threshold' => $settings['max'],
					'error'     => wpcf7_get_message( 'number_too_large' ),
				]
			)
		);

		$schema->add_rule(
			wpcf7_swv_create_rule(
				'stepnumber',
				[
					'field'    => $tag->name,
					'base'     => $settings['min'],
					'interval' => $settings['step'],
					'error'    => wpcf7_get_message( 'cf7at_invalid_option' ),
				]
			)
		);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function messages(): array {
		return [
			'cf7at_invalid_option' => [
				'description' => __( 'Range slider value is not one of its steps', 'cf7-additional-types' ),
				'default'     => __( 'Please choose one of the available options.', 'cf7-additional-types' ),
			],
		];
	}

	/**
	 * Emails and stored submissions receive the step label instead of its index.
	 *
	 * @param mixed         $value      Sanitized posted value.
	 * @param mixed         $value_orig Original posted value.
	 * @param WPCF7_FormTag $tag        The form-tag.
	 * @return mixed
	 */
	public function format_posted_value( $value, $value_orig, WPCF7_FormTag $tag ) {
		$labels = $this->step_labels( $tag );

		if ( ! $labels || ! is_scalar( $value ) || ! ctype_digit( (string) $value ) ) {
			return $value;
		}

		return $labels[ (int) $value ] ?? $value;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function render_tag_generator_fields( WPCF7_TagGeneratorGenerator $tgg, array $options ): void {
		printf(
			'<fieldset><legend id="%1$s">%2$s</legend><span id="%3$s">%4$s</span><br /><textarea data-tag-part="value" aria-labelledby="%1$s" aria-describedby="%3$s"></textarea></fieldset>',
			esc_attr( $tgg->ref( 'labels-legend' ) ),
			esc_html__( 'Step labels', 'cf7-additional-types' ),
			esc_attr( $tgg->ref( 'labels-description' ) ),
			esc_html__( 'One label per line. Leave empty for a numeric slider.', 'cf7-additional-types' )
		);

		$this->print_option_input(
			[
				'legend'      => __( 'Default', 'cf7-additional-types' ),
				'option'      => 'default:',
				'type'        => 'number',
				'description' => __( 'With labels: the position of the preselected label (1 = first). Without labels: the starting number.', 'cf7-additional-types' ),
				'atts'        => [ 'step' => 'any' ],
			]
		);

		echo '<fieldset><legend>' . esc_html__( 'Numeric range', 'cf7-additional-types' ) . '</legend>';

		foreach (
			[
				'min:'  => __( 'Min', 'cf7-additional-types' ),
				'max:'  => __( 'Max', 'cf7-additional-types' ),
				'step:' => __( 'Step', 'cf7-additional-types' ),
			] as $option => $label
		) {
			printf(
				'<label>%1$s <input type="number" step="any" data-tag-part="option" data-tag-option="%2$s" /></label> ',
				esc_html( $label ),
				esc_attr( $option )
			);
		}

		echo '<br /><small>' . esc_html__( 'Only used when there are no step labels.', 'cf7-additional-types' ) . '</small></fieldset>';

		echo '<fieldset><legend>' . esc_html__( 'Number display', 'cf7-additional-types' ) . '</legend>';

		printf(
			'<label>%1$s <input type="text" size="4" maxlength="3" pattern="[A-Za-z]{3}" placeholder="EUR" data-tag-part="option" data-tag-option="currency:" /></label> ',
			esc_html__( 'Currency', 'cf7-additional-types' )
		);

		// Form-tag options only allow these characters.
		$option_pattern = '[\-+*=0-9a-zA-Z:.!?#$&@_\/|%]*';

		foreach (
			[
				'prefix:' => __( 'Prefix', 'cf7-additional-types' ),
				'suffix:' => __( 'Suffix', 'cf7-additional-types' ),
			] as $option => $label
		) {
			printf(
				'<label>%1$s <input type="text" size="6" pattern="%2$s" data-tag-part="option" data-tag-option="%3$s" /></label> ',
				esc_html( $label ),
				esc_attr( $option_pattern ),
				esc_attr( $option )
			);
		}

		echo '<br /><small>' . esc_html__( 'Currency: a code such as EUR, USD or GBP, formatted for the site language. Prefix and suffix: plain text such as % or km, with an underscore for a space.', 'cf7-additional-types' ) . '</small></fieldset>';

		$this->print_option_checkbox(
			__( 'Labels', 'cf7-additional-types' ),
			'ticks',
			__( 'Show the labels (or the min and max) under the slider.', 'cf7-additional-types' )
		);
	}
}

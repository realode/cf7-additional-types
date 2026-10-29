<?php
/**
 * Creates a demo contact form that uses every field type, and a page that
 * shows it with the Contact Form 7 block in each style.
 *
 * Usage: npm run env run cli wp eval-file wp-content/plugins/cf7-additional-types/bin/demo-content.php
 *
 * @package CF7AT
 */

if ( ! defined( 'WP_CLI' ) || ! class_exists( 'WPCF7_ContactForm' ) ) {
	return;
}

$cf7at_form_markup = <<<'FORM'
<label> Your name
    [text* your-name autocomplete:name] </label>

<label> Your email
    [email* your-email autocomplete:email] </label>

<label> Topic
    [select topic "General question" "Project inquiry" "Support"] </label>

<label> Team size
    [rangeslider team-size default:2 ticks "Just me" "2–10" "11–50" "51+"] </label>

<label> Budget
    [rangeslider budget min:1000 max:20000 step:500 default:5000 currency:EUR ticks] </label>

<label> Preferred date
    [datepicker* preferred-date min:today max:+3_months no_weekends placeholder "Choose a date"] </label>

<label> Preferred time
    [timepicker* preferred-time min:09:00 max:17:00 step:30 placeholder "Choose a time"] </label>

<label> Date of birth
    [datepicker birthday years:100] </label>

<label> Your message
    [textarea your-message] </label>

[radio contact-by use_label_element default:1 "Email" "Phone"]

[acceptance consent] I agree to the privacy policy. [/acceptance]

[submit "Send request"]
FORM;

$cf7at_mail_body = <<<'MAIL'
From: [your-name] <[your-email]>
Topic: [topic]
Team size: [team-size]
Budget: [budget]
Preferred date: [preferred-date] at [preferred-time]
Date of birth: [birthday]
Contact by: [contact-by]

[your-message]
MAIL;

$cf7at_form = WPCF7_ContactForm::get_template( [ 'title' => 'CF7 Additional Types demo' ] );
$cf7at_mail = $cf7at_form->prop( 'mail' );

$cf7at_mail['body'] = $cf7at_mail_body;

$cf7at_form->set_properties(
	[
		'form' => $cf7at_form_markup,
		'mail' => $cf7at_mail,
	]
);

$cf7at_form_id = $cf7at_form->save();
$cf7at_form    = wpcf7_contact_form( $cf7at_form_id );
$cf7at_hash    = $cf7at_form->hash();

$cf7at_block = static function ( string $class_name = '', array $style = [] ) use ( $cf7at_form_id, $cf7at_hash ) {
	$attributes = [
		'id'    => $cf7at_form_id,
		'hash'  => $cf7at_hash,
		'title' => 'CF7 Additional Types demo',
	];

	if ( $class_name ) {
		$attributes['className'] = $class_name;
	}

	if ( $style ) {
		$attributes['cf7atStyle'] = $style;
	}

	$wrapper_class = trim( 'wp-block-contact-form-7-contact-form-selector ' . $class_name );

	return sprintf(
		"<!-- wp:contact-form-7/contact-form-selector %1\$s -->\n<div class=\"%2\$s\">[contact-form-7 id=\"%3\$s\" title=\"CF7 Additional Types demo\"]</div>\n<!-- /wp:contact-form-7/contact-form-selector -->",
		wp_json_encode( $attributes ),
		esc_attr( $wrapper_class ),
		esc_attr( $cf7at_hash )
	);
};

$cf7at_sections = [
	'Default style'          => $cf7at_block(),
	'Filled, large'          => $cf7at_block(
		'is-style-filled',
		[
			'fieldSize'   => 'large',
			'radius'      => '12px',
			'accentColor' => '#3858e9',
			'buttonWidth' => 'full',
		]
	),
	'Underlined'             => $cf7at_block( 'is-style-underlined', [ 'themeButton' => false ] ),
	'Unstyled (CF7 default)' => $cf7at_block( 'is-style-unstyled' ),
];

$cf7at_content = '';

foreach ( $cf7at_sections as $cf7at_heading => $cf7at_markup ) {
	$cf7at_content .= "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$cf7at_heading}</h2>\n<!-- /wp:heading -->\n\n{$cf7at_markup}\n\n";
}

$cf7at_page_id = wp_insert_post(
	[
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'CF7 Additional Types demo',
		'post_name'    => 'cf7at-demo',
		'post_content' => $cf7at_content,
	]
);

WP_CLI::success( sprintf( 'Created form %1$d and page %2$s', $cf7at_form_id, get_permalink( $cf7at_page_id ) ) );

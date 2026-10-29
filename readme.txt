=== CF7 Additional Types ===
Contributors: janisf
Tags: contact form 7, block editor, form design, date picker, range slider
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: contact-form-7
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Design Contact Form 7 forms in the block editor, and add a range slider, date picker and time picker that match your theme.

== Description ==

Contact Form 7 leaves the look of your forms to your theme, and the block editor shows nothing but a form picker. CF7 Additional Types turns the Contact Form 7 block into a designed, previewed part of your page, and adds the field types forms often need.

= Design forms where you build pages =

Select a Contact Form 7 block and open the **Styles** tab:

* **Live preview** of the form in the editor, exactly as visitors see it.
* **Styles:** Default, Filled, Underlined, or Unstyled to keep Contact Form 7's own markup untouched.
* **Color:** accent, labels, field text, background and border, button text and background, from your theme's palette or any custom color.
* **Border:** field corner radius and border width.
* **Dimensions:** space between fields, using your theme's spacing sizes.
* **Form fields:** small, medium or large fields, the theme's button style or an accent button, and a full-width button.

The settings live in the panels you already know from core blocks. They extend Contact Form 7's own block, so every form already on your site gets them, and nothing needs to be replaced.

= Matches your theme =

Without any settings, forms take their colors, fonts and buttons from your theme: borders and focus states follow the text and palette colors, and the submit button uses the button style from Global Styles. Themes and agencies can set defaults for every form in `theme.json` (see the FAQ).

= New field types =

* **Range slider** with named steps (Small, Medium, Large) or numbers, with currency formatting, a value bubble and optional labels under the track.
* **Date picker** with an accessible calendar in the site language and week start, earliest and latest dates (also relative, like "today" or "-18 years"), month and year menus for birth dates, and a weekdays-only option.
* **Time picker** with a list of times in your site's 12- or 24-hour format, earliest and latest time, and any interval.

Every field works without JavaScript, is fully keyboard accessible, and is validated on the server. Emails show dates, times and slider steps in your site's format, not as raw values.

= Lightweight =

No jQuery and no date libraries. Field scripts (about 12 KB gzipped) load only on pages with these fields, and the stylesheet is about 3 KB gzipped. The plugin makes no external requests and collects no data.

= Form-tag reference =

Use the **range slider**, **date picker** and **time picker** buttons in the Contact Form 7 form editor, or write the tags yourself:

`[rangeslider size default:2 ticks "Small" "Medium" "Large"]`
Named steps. `default:` is the position of the preselected step, `ticks` shows the labels under the slider. Emails receive the step's label.

`[rangeslider budget min:1000 max:20000 step:500 default:5000 currency:EUR]`
Numbers. `currency:` takes a code such as EUR, USD or GBP. `prefix:` and `suffix:` add plain text such as `suffix:%`, with an underscore for a space.

`[datepicker* arrival min:today max:+6_months no_weekends placeholder "Choose a date"]`
`min:` and `max:` take a date (2026-12-31) or a relative date, with underscores for spaces. A quoted value sets the default date, or the placeholder with `placeholder`.

`[timepicker* call-time min:09:00 max:17:00 step:15]`
`step:` is the number of minutes between times (default 30).

Add `*` after the tag name to make a date or time field required. The usual Contact Form 7 options also work, such as `id:`, `class:`, `readonly` and `default:get`.

== Installation ==

1. Install and activate Contact Form 7 6.0 or newer.
2. Install CF7 Additional Types from Plugins → Add New, or upload the plugin folder to `/wp-content/plugins/`, and activate it.
3. Add the new fields to a form under Contact → Contact Forms.
4. Insert the form with the Contact Form 7 block and design it in the block's Styles tab.

== Frequently Asked Questions ==

= Do my forms from version 2 still work? =

Yes. `[rangeslider]` and `[datepicker]` tags keep working, including their `default:` and `years:` options. The fields look different, since they no longer use the Ion.RangeSlider and Ion.Calendar styles, so check your forms after updating.

= Can I set defaults for all forms in my theme? =

Yes. In `theme.json`, add custom settings under `settings.custom.cf7at`:

`"settings": { "custom": { "cf7at": { "accent": "var(--wp--preset--color--primary)", "radius": "8px", "borderWidth": "1px", "gap": "1.5rem" } } }`

Available keys: `accent`, `accentText`, `label`, `fieldText`, `fieldBackground`, `fieldBorder`, `borderWidth`, `radius`, `gap` and `error`. Settings on a block override the theme defaults.

= Do the fields work without the block editor? =

Yes. The fields work wherever a Contact Form 7 form is shown, with the shortcode too. The design settings are part of the Contact Form 7 block.

= What happens if I deactivate the plugin? =

Your forms and pages stay as they are. The design settings are stored separately from the form markup, and the editor shows no block errors. The new fields need the plugin to be active.

= Which language and format does the date picker use? =

The site language, the week start from Settings → General, and a date format based on the site's date format. Each Contact Form 7 form can also have its own language.

== Screenshots ==

1. Design settings and live preview of a Contact Form 7 block in the editor.
2. Date picker, time picker and range sliders in a form with the Default style.
3. The same form with the Filled style, large fields and a custom accent color.
4. The tag generator for the new field types in the Contact Form 7 form editor.

== Changelog ==

= 3.0.0 =

* New: design settings and a live preview for the Contact Form 7 block, with Default, Filled, Underlined and Unstyled styles.
* New: time picker field.
* New: numeric range slider with currency, prefix and suffix.
* New: date picker with min and max dates, relative dates, month and year menus and a weekdays-only option.
* New: emails receive dates, times and slider steps in the site's format.
* New: complete German and Russian translations.
* Changed: rebuilt for Contact Form 7 6, which version 2 no longer works with.
* Changed: replaced Ion.RangeSlider, Ion.Calendar, Moment.js and jQuery with native inputs and the Cally calendar, and scripts now load only where needed.
* Changed: requires WordPress 6.7, PHP 7.4 and Contact Form 7 6.0.
* Fixed: translations were never loaded.
* Fixed: slider values with quotes broke the slider, and submitted values were not validated.

= 2.0.0 =

* Restructuring of the plugin code.
* Date picker implementation.
* Default parameter implementation for the range slider.

= 1.0.1 =

* Range slider implementation.
* Russian and German translations.

== Upgrade Notice ==

= 3.0.0 =

Rebuilt for Contact Form 7 6, with a design panel for the Contact Form 7 block and a new time picker. Requires WordPress 6.7, PHP 7.4 and Contact Form 7 6.0. Fields look different, so check your forms after updating.

== Credits ==

The date picker uses [Cally](https://github.com/WickyNilliams/cally) by Nick Williams (MIT license).

Bug reports are welcome on [GitHub](https://github.com/janisf/cf7-additional-types). Please note that GitHub is not a support forum.

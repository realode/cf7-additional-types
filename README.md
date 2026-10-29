# CF7 Additional Types

Design Contact Form 7 forms in the block editor, and add a range slider, date picker and time picker that match your theme.

This is the development repository. The plugin is published on [WordPress.org](https://wordpress.org/plugins/cf7-additional-types/), where [readme.txt](readme.txt) describes its features and form-tag syntax.

## Requirements

- WordPress 6.7+, PHP 7.4+, Contact Form 7 6.0+
- For development: Node.js 20+, Composer, Docker (for `wp-env`)

## Development

```sh
npm install
composer install

npm run env start   # WordPress with Contact Form 7 at http://localhost:8888 (admin / password)
npm start           # rebuild JavaScript and CSS on change
```

Create a demo form that uses every field, and a page that shows it in each style:

```sh
npm run env run cli wp eval-file wp-content/plugins/cf7-additional-types/bin/demo-content.php
```

| Command | What it does |
| --- | --- |
| `npm run build` | Production build into `build/` |
| `npm run lint:js`, `lint:css`, `lint:php` | WordPress coding standards for JavaScript, CSS and PHP |
| `npm run make-pot` | Regenerate `languages/cf7-additional-types.pot` (run after `npm run build`) |
| `npm run make-json` | Generate the editor script's JSON translations from the `.po` files |
| `npm run plugin-zip` | Build and package an installable ZIP |

## How it works

```text
cf7-additional-types.php   Plugin header and autoloader
includes/
  Plugin.php               Bootstrap: version check, assets, translations
  Fields/                  Form-tags: Range_Slider, Date_Picker, Time_Picker
  Styler/Block_Styler.php  Design settings for Contact Form 7's block
src/
  fields/                  Front-end enhancements for the fields
  editor/                  Block editor extension: design panels and preview
  styles/style.css         Form and field styles (Tailwind CSS)
```

**Fields.** Each field is a Contact Form 7 form-tag with a version 2 tag generator, schema-woven validation rules, and a `wpcf7_posted_data_{type}` filter that turns the submitted value (ISO date, 24-hour time, slider index) into the site's format for emails. Fields render as native inputs that work without JavaScript; `src/fields` upgrades them in the browser. The date picker uses the [Cally](https://github.com/WickyNilliams/cally) web components.

**Block styler.** Instead of a separate block, the plugin extends `contact-form-7/contact-form-selector`:

- `block_type_metadata` adds a `cf7atStyle` attribute and the stylesheet to the block.
- `editor.BlockEdit` adds a live preview (rendered by the `cf7at/v1/form-preview` REST route) and design controls in the block's Color, Border, Dimensions and Styles panels.
- `render_block` turns the settings into classes and CSS custom properties on the block wrapper. Contact Form 7's saved markup is never changed, so deactivating the plugin causes no block validation errors.

The design controls use some experimental block editor components, the same ones core's own panels are built from. They are listed in [eslint.config.cjs](eslint.config.cjs), so they are easy to review when WordPress changes.

**Styles.** `src/styles/style.css` imports Tailwind's theme as an inline reference: Tailwind's spacing, type and radius scales are compiled into plain, unlayered selectors, without Preflight or global variables. Every design token falls back from the block setting to `theme.json` custom settings (`--wp--custom--cf7at--*`), then to the theme palette and `currentColor`.

## Releasing

1. Update the version in `cf7-additional-types.php` (header), `includes/Plugin.php` (`VERSION`), `package.json`, and the stable tag and changelog in `readme.txt`.
2. Run `npm run build && npm run make-pot`, and update the translations.
3. Publish a GitHub release tagged with the version, e.g. `3.0.0`. The [deploy workflow](.github/workflows/deploy.yml) builds the plugin and commits it to the WordPress.org SVN repository, including the banners, icons and screenshots in `.wordpress-org/`. It needs the `SVN_USERNAME` and `SVN_PASSWORD` repository secrets.

## License

GPL-2.0-or-later. Copyright © 2016–2026 Janis Freimann, meostudio OÜ.

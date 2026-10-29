<?php
/**
 * Plugin Name:       CF7 Additional Types
 * Plugin URI:        https://github.com/meostudio/cf7-additional-types
 * Description:       Design Contact Form 7 forms right in the block editor, and add a range slider, date picker and time picker to your forms.
 * Version:           3.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Requires Plugins:  contact-form-7
 * Author:            Janis Freimann, meo.studio
 * Author URI:        https://janfrei.me/
 * Developer:         Janis Freimann
 * Developer E-Mail:  janis@meo.studio
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cf7-additional-types
 * Domain Path:       /languages
 *
 * @package CF7AT
 */

namespace CF7AT;

defined( 'ABSPATH' ) || exit;

const PLUGIN_FILE = __FILE__;

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_path = str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) );
		$file          = __DIR__ . '/includes/' . $relative_path . '.php';

		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

add_action( 'plugins_loaded', [ Plugin::class, 'boot' ] );

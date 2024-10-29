<?php
/**
 * Plugin Name:       Auto Post to Social Media - WP to Social Champ
 * Plugin URI:        https://wordpress.org/plugins/wp-social-champ/
 * Description:       It sends WordPress Pages, Posts or Custom Post Types to your SocialChamp (SocialChamp.io) account for scheduled publishing to social networks.
 * Version:           1.3.2
 * Author:            SocialChamp
 * Author URI:        https://www.socialchamp.io/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-socialchamp
 * Domain Path:       /languages
 *
 * @link              https://www.socialchamp.io/
 * @since             1.0.0
 * @package           Wp_Socialchamp
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_SOCIALCHAMP_VERSION', '1.3.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-socialchamp-activator.php
 */
function activate_wp_socialchamp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-socialchamp-activator.php';
	Wp_Socialchamp_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-socialchamp-deactivator.php
 */
function deactivate_wp_socialchamp() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-socialchamp-deactivator.php';
	Wp_Socialchamp_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_socialchamp' );
register_deactivation_hook( __FILE__, 'deactivate_wp_socialchamp' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-socialchamp.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_socialchamp() {

	$plugin = new Wp_Socialchamp();
	$plugin->run();

}
run_wp_socialchamp();

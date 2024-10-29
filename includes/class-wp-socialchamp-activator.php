<?php
/**
 * Fired during plugin activation
 *
 * @link       https://www.socialchamp.io/
 * @since      1.0.0
 *
 * @package    Wp_Socialchamp
 * @subpackage Wp_Socialchamp/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Socialchamp
 * @subpackage Wp_Socialchamp/includes
 * @author     SocialChamp <torontodigits@gmail.com>
 */
class Wp_Socialchamp_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		$settings = new WP_SocialChamp_Settings();
		$settings->set_option( 'log_level_error', true );
		$settings->set_option( 'log_preserve_days', 30 );
		$settings->set_option( 'log_enabled', true );

		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			' CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'wpsc_logs' . " (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `post_id` int(11) NOT NULL,
                `action` enum('publish','update','repost','bulk_publish') DEFAULT NULL,
                `request_sent` datetime NOT NULL,
                `profile_id` varchar(191) NOT NULL,
                `profile_name` varchar(191) NOT NULL DEFAULT '',
                `result` enum('success','test','pending','warning','error') NOT NULL DEFAULT 'success',
                `result_message` text,
                `status_text` text,
                `status_created_at` datetime DEFAULT NULL,
                `status_due_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `post_id` (`post_id`),
                KEY `action` (`action`),
                KEY `result` (`result`),
                KEY `profile_id` (`profile_id`)
            ) " . $wpdb->get_charset_collate() . ' AUTO_INCREMENT=1'
		);
	}
}

<?php

class WP_SocialChamp_Log {
	public $table = 'wpsc_logs';
	private $settings;

	public function __construct() {
		$this->settings = new WP_SocialChamp_Settings();
	}

	public function add( $post_id, $log ) {
		global $wpdb;

		$enabled = $this->settings->get_option( 'log_level_' . $log['result'], false );
		if ( ! $enabled ) {
			return;
		}

		$log['post_id'] = absint( $post_id );
		$result         = $wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prefix . $this->table,
			$log
		);
	}
}

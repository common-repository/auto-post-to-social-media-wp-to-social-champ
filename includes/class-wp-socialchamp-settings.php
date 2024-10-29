<?php

class WP_SocialChamp_Settings {

	const PREFIX = 'wpsc_';

	public function get_settings( $type ) {

		$settings = array();

		$actions = array( 'publish', 'update' );
		foreach ( $actions as $action ) {
			$enabled = $this->get_option( "{$type}_{$action}_enabled", false );
			$full    = $this->get_option( "{$type}_{$action}", array() );

			$settings['default'][ $action ]['enabled'] = $enabled;

			foreach ( $full as $single ) {
				$settings['default'][ $action ]['status'][] = array(
					'image'    => $single['image'],
					'message'  => $single['content'],
					'schedule' => $single['queue_bottom'],
				);
			}
		}

		$profiles = $this->get_option( "{$type}_profiles", array() );

		foreach ( $profiles as $profile => $val ) {
			$settings[ $profile ]['enabled'] = $val;
		}

		return $settings;

	}

	public function get_option( $option, $default = '' ) {

		$result = get_option( self::PREFIX . $option );
		if ( ! $result ) {
			return $default;
		}

		return $result;
	}

	public function set_option( $option, $value ) {
		update_option( self::PREFIX . $option, $value );
	}
}

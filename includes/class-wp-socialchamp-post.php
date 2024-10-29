<?php


class WP_SocialChamp_Post {

	public $excludedTypes = array(
		'attachment',
		'revision',
		'elementor_library',
	);

	public function getPostTypes() {
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		foreach ( $this->excludedTypes as $excluded_type ) {
			unset( $types[ $excluded_type ] );
		}

		$_types = array();
		foreach ( $types as $type ) {
			$_types[ $type->name ] = array(
				'icon'          => ! empty( $type->menu_icon ) ? $type->menu_icon : ' dashicons-admin-page',
				'title'         => $type->label,
				'singular_name' => $type->labels->singular_name,
			);
		}

		return $_types;
	}


}

<?php


class WP_SocialChamp_Publish {

	private $all_possible_searches_replacements = false;

	private $searches_replacements = false;

	const PREFIX = 'wpsc';

	/**
	 * @var WP_SocialChamp_Settings
	 */
	private $settings;

	private $wpsc_post;

	private $api;

	private $log;

	public function __construct() {
		$this->settings  = new WP_SocialChamp_Settings();
		$this->wpsc_post = new WP_SocialChamp_Post();
		$this->api       = new WP_SocialChamp_API();
		$this->log       = new WP_SocialChamp_Log();
		add_action( 'wp_loaded', array( $this, 'register_publish_hooks' ), 1 );
		add_action( self::PREFIX, array( $this, 'publish' ), 1, 2 );
	}

	public function register_publish_hooks() {

		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );

	}


	public function transition_post_status( $new_status, $old_status, $post ) {

		// Bail if the Post Type isn't public
		// This prevents the rest of this routine running on e.g. ACF Free, when saving Fields (which results in Field loss)
		$post_types = array_keys( $this->wpsc_post->getPostTypes() );
		if ( ! in_array( $post->post_type, $post_types ) ) {
			return;
		}

		// New Post Screen loading
		// Draft saved
		if ( $new_status == 'auto-draft' || $new_status == 'draft' || $new_status == 'inherit' || $new_status == 'trash' ) {
			return;
		}

		// Remove actions registered by this Plugin
		// This ensures that when Page Builders call publish or update events via AJAX, we don't run this multiple times
		remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );
		remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10 );
		remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );
		remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10 );

		/**
		 * = REST API =
		 * If this is a REST API Request, we can't use the wp_insert_post action, because the metadata
		 * is *not* included in the call to wp_insert_post().  Instead, we must use a late REST API action
		 * that gives the REST API time to save metadata.
		 * Note that the meta being supplied in the REST API Request must be registered with WordPress using
		 * register_meta()
		 *
		 * = Gutenberg =
		 * If Gutenberg is being used on the given Post Type, two requests are sent:
		 * - a REST API request, comprising of Post Data and Metadata registered in Gutenberg,
		 * - a standard request, comprising of Post Metadata registered outside of Gutenberg (i.e. add_meta_box() data)
		 * The second request will be seen by transition_post_status() as an update.
		 * Therefore, we set a meta flag on the first Gutenberg REST API request to defer publishing the status until
		 * the second, standard request - at which point, all Post metadata will be available to the Plugin.
		 *
		 * = Classic Editor =
		 * Metadata is included in the call to wp_insert_post(), meaning that it's saved to the Post before we use it.
		 */

		// Flag to determine if the current Post is a Gutenberg Post
		$is_gutenberg_post   = $this->is_gutenberg_post( $post );
		$is_rest_api_request = $this->is_rest_api_request();

		// If a previous request flagged that an 'update' request should be treated as a publish request (i.e.
		// we're using Gutenberg and request to post.php was made after the REST API), do this now.
		$needs_publishing = get_post_meta( $post->ID, self::PREFIX . '_needs_publishing', true );
		if ( $needs_publishing ) {

			// Run Publish Status Action now
			delete_post_meta( $post->ID, self::PREFIX . '_needs_publishing' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit
			return;
		}

		// If a previous request flagged that an update request be deferred (i.e.
		// we're using Gutenberg and request to post.php was made after the REST API), do this now.
		$needs_updating = get_post_meta( $post->ID, self::PREFIX . '_needs_updating', true );
		if ( $needs_updating ) {

			// Run Publish Status Action now
			delete_post_meta( $post->ID, self::PREFIX . '_needs_updating' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

			// Don't need to do anything else, so exit
			return;
		}

		// Publish
		if ( $new_status == 'publish' && $new_status != $old_status ) {
			/**
			 * Classic Editor
			 */
			if ( ! $is_rest_api_request ) {

				add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

				// Don't need to do anything else, so exit
				return;
			}

			/**
			 * Gutenberg Editor
			 * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
			 * as an 'update'.  Define a meta key that we'll check on the separate request later.
			 */
			if ( $is_gutenberg_post ) {

				update_post_meta( $post->ID, self::PREFIX . '_needs_publishing', 1 );

				// Don't need to do anything else, so exit
				return;
			}

			/**
			 * REST API
			 */
			add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10, 2 );

			// Don't need to do anything else, so exit
			return;
		}//end if

		// Update
		if ( $new_status == 'publish' && $old_status == 'publish' ) {
			/**
			 * Classic Editor
			 */
			if ( ! $is_rest_api_request ) {

				add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

				// Don't need to do anything else, so exit
				return;
			}

			/**
			 * Gutenberg Editor
			 * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
			 * as an 'update'.  Define a meta key that we'll check on the separate request later.
			 */
			if ( $is_gutenberg_post ) {

				update_post_meta( $post->ID, self::PREFIX . '_needs_updating', 1 );

				// Don't need to do anything else, so exit
				return;
			}

			/**
			 * REST API
			 */
			add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10, 2 );

			// Don't need to do anything else, so exit
			return;
		}//end if

	}


	private function is_rest_api_request() {

		if ( ! defined( 'REST_REQUEST' ) ) {
			return false;
		}

		if ( ! REST_REQUEST ) {
			return false;
		}

		return true;

	}


	private function is_gutenberg_post( $post ) {

		// If the Post's content contains Gutenberg block markup, we might be editing a Gutenberg Post
		if ( strpos( $post->post_content, '<!-- wp:' ) !== false ) {
			return true;
		}

		if ( ! post_type_exists( $post->post_type ) ) {
			return false;
		}

		if ( ! post_type_supports( $post->post_type, 'editor' ) ) {
			return false;
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( $post_type_object && ! $post_type_object->show_in_rest ) {
			return false;
		}

		return apply_filters( 'use_block_editor_for_post_type', true, $post->post_type );

	}

	private function is_gutenberg_post_content( $post ) {

		if ( strpos( $post->post_content, '<!-- wp:' ) !== false ) {
			return true;
		}

		return false;

	}

	public function rest_api_post_publish( $post, $request ) {

		$this->wp_insert_post_publish( $post->ID );

	}

	public function rest_api_post_update( $post, $request ) {

		$this->wp_insert_post_update( $post->ID );

	}


	public function wp_insert_post_publish( $post_id ) {

		// Get Test Mode Flag
		$test_mode = $this->settings->get_option( 'test_mode', false );

		// Call main function to publish status(es) to social media
		$results = $this->publish( $post_id, 'publish', $test_mode );

		// If no result, bail
		if ( ! isset( $results ) ) {
			return;
		}

		// If no errors, return
		if ( ! is_wp_error( $results ) ) {
			return;
		}

	}


	public function wp_insert_post_update( $post_id ) {

		// If a status was last sent within 5 seconds, don't send it again
		// Prevents Page Builders that trigger wp_update_post() multiple times on Publish or Update from
		// causing statuses to send multiple times
		$last_sent = get_post_meta( $post_id, '_' . self::PREFIX . '_last_sent', true );
		if ( ! empty( $last_sent ) ) {
			$difference = ( current_time( 'timestamp' ) - $last_sent ); //phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested)
			if ( $difference < 5 ) {
				return;
			}
		}

		// Get Test Mode Flag
		$test_mode = $this->settings->get_option( 'test_mode', false );

		// Call main function to publish status(es) to social media
		$results = $this->publish( $post_id, 'update', $test_mode );

		// If no result, bail
		if ( ! isset( $results ) ) {
			return;
		}

		// If no errors, return
		if ( ! is_wp_error( $results ) ) {
			return;
		}

	}


	public function publish( $post_id, $action, $test_mode = false ) {

		// Bail if the action isn't supported
		$supported_actions = array( 'publish', 'update' );
		if ( ! in_array( $action, $supported_actions ) ) {
			return;
		}

		// Clear any cached data that we have stored in this class
		$this->clear_search_replacements();

		// Get Post
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'no_post', sprintf( __( 'No WordPress Post could be found for Post ID %s', 'wp-socialchamp' ), $post_id ) );
		}

		// Bail if the Post Type isn't supported
		// This prevents non-public Post Types sending status(es) where Post Level Default = Post using Manual Settings
		// and this non-public Post Type has been created by copying metadata from a public Post Type that specifies
		// Post-specific status settings
		$supported_post_types = array_keys( $this->wpsc_post->getPostTypes() );
		if ( ! in_array( get_post_type( $post ), $supported_post_types ) ) {
			return false;
		}

		// Determine post type
		$post_type = $post->post_type;

		// Use Plugin Settings
		$settings = $this->settings->get_settings( $post_type );

		// Check settings exist
		// If not, this means the CPT or Post-level settings have not been configured, so we
		// don't need to do anything
		if ( ! $settings ) {
			return false;
		}

		// Check a valid access token exists
		$is_loggedIn = WP_SocialChamp_API::isLoggedIn();
		if ( ! $is_loggedIn ) {
			return new WP_Error( 'no_access_token', sprintf( __( 'The Plugin has not been authorized with Socialchamp! Go to Wp Socialchamp > Settings to setup the plugin.', 'wp-socialchamp' ) ) );
		}

		// Get Profiles
		$_profiles = $this->api->getProfiles();

		// Bail if the Profiles could not be fetched
		if ( is_wp_error( $_profiles ) ) {
			return $_profiles;
		}

		$profiles = array();
		foreach ( $_profiles as $_profile ) {
			if ( is_array( $_profile ) && isset( $_profile['id'] ) ) {
				$profiles[ $_profile['id'] ] = $_profile;
			}
		}

		// Array for storing statuses we'll send to the API
		$statuses = array();

		// Iterate through each social media profile
		foreach ( $settings as $profile_id => $profile_settings ) {

			// Skip some setting keys that aren't related to profiles
			if ( in_array( $profile_id, array( 'featured_image', 'additional_images', 'override' ) ) ) {
				continue;
			}

			// Skip if the Profile ID does not exist in the $profiles array, it's been removed from the API
			if ( $profile_id != 'default' && ! isset( $profiles[ $profile_id ] ) ) {
				continue;
			}

			// Get detailed settings from Post or Plugin
			// Use Plugin Settings
			$profile_enabled = isset( $profile_settings['enabled'] ) ? $profile_settings['enabled'] : $profile_settings[ $action ]['enabled'];
			// $profile_override = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][override]', 0 );

			// Either use override settings (or if Pinterest, always use override settings)
			// if ( $profile_override || ( isset( $profiles[ $profile_id ] ) && $profiles[ $profile_id ]['service'] == 'pinterest' ) ) {
			// $action_enabled = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][enabled]', 0 );
			// $status_settings = $this->base->get_class( 'settings' )->get_setting( $post_type, '[' . $profile_id . '][' . $action . '][status]', array() );
			// } else {
			$action_enabled  = $settings['default'][ $action ]['enabled'];
			$status_settings = $settings['default'][ $action ]['status'];
			// }

			// Check if this profile is enabled
			if ( ! $profile_enabled ) {
				continue;
			}

			// Check if this profile's action is enabled
			if ( ! $action_enabled ) {
				continue;
			}

			$service = '';
			// Determine which social media service this profile ID belongs to
			foreach ( $profiles as $profile ) {
				if ( $profile['id'] == $profile_id ) {
					$service = $profile['type'];
					break;
				}
			}

			// Iterate through each Status
			foreach ( $status_settings as $index => $status ) {
				$statuses[] = $this->build_args( $post, $profile_id, $service, $status, $action );
			}
		}//end foreach

		// Debugging
		// $this->base->get_class( 'log' )->add_to_debug_log( $statuses );

		// Check if any statuses exist
		// If not, exit
		if ( count( $statuses ) == 0 ) {
			// Fetch Post Type object and Settings URL
			$post_type_object = get_post_type_object( $post->post_type );
			// $plugin_url = admin_url( 'admin.php?page=' . $this->base->plugin->name . '-settings&tab=post&type=' . $post->post_type );
			$post_url = admin_url( 'post.php?post=' . $post_id );

			// Return an error, depending on why no statuses were found

			return new WP_Error(
				self::PREFIX . '_no_statuses_enabled',
				sprintf(
					__( 'No Plugin Settings are defined for WP SocialChamp' )
				)
			);
		}

		// Send status messages to the API
		$results = $this->send( $statuses, $post_id, $action, $profiles, $test_mode );

		// If no results, we're finished
		if ( empty( $results ) || count( $results ) == 0 ) {
			return false;
		}

		// If here, all OK
		return $results;

	}


	private function build_args( $post, $profile_id, $service, $status, $action ) {

		// Build each API argument
		// Profile ID
		$args = array(
			'profile_ids' => array( $profile_id ),
		);

		$character_limits = array(
			'TW'          => 280,
			'PINIT_PAGE'  => 500,
			'IG'          => 2000,
			'IG_BUSINESS' => 2000,
			'FB_PAGE'     => 5000,
			'FB_GROUP'    => 5000,
			'IN'          => 700,
			'IN_PAGE'     => 700,
			'G_BUSINESS'  => 1500,
		);

		$character_limit = 0;

		if ( isset( $character_limits[ $service ] ) ) {
			$character_limit = absint( $character_limits[ $service ] );
		}

		// Text
		$args['text'] = $this->parse_text( $post, $status['message'], $character_limit );

		// Shorten URLs
		$args['shorten'] = true;

		// Schedule
		switch ( $status['schedule'] ) {

			case 'now':
				$args['postType'] = 'NOW';
				break;

			case 'queue_top':
				$args['postType'] = 'NEXT';
				break;

			case 'queue_bottom':
				$args['postType'] = 'LAST';
				break;

		}

		// Change the Image setting if it's an invalid value for the service
		// This happens when e.g. Defaults are set, but per-service settings aren't
		switch ( $service ) {
			/**
			 * Twitter
			 * - Force Use Feat. Image, not Linked to Post if Use Feat. Image, Linked to Post chosen
			 */
			case 'TW':
				if ( $status['image'] == 1 ) {
					$status['image'] = 2;
				}
				break;

			/**
			 * Pinterest, Instagram
			 * - Always force Use Feat. Image, not Linked to Post or Use Text to Image, not Linked to Post
			 */
			case 'PINIT_PAGE':
			case 'IG_BUSINESS':
			case 'IG':
				// Set OpenGraph and Use Feat. Image, Linked to Post = Use Feat. Image, not Linked to Post
				if ( $status['image'] == - 1 || $status['image'] == 0 || $status['image'] == 1 ) {
					$status['image'] = 2;
				}

				// Set Use Text to Image, Linked to Post = Use Text to Image, not Linked to Post
				if ( $status['image'] == 3 ) {
					$status['image'] = 4;
				}
				break;

		}//end switch

		// Get Image
		$image = $this->get_post_image( $post, $service );

		// If we have a Featured Image, add it to the Status is required
		if ( $image != false ) {
			switch ( $status['image'] ) {

				/**
				 * No Image
				 */
				case - 1:
					$args['attachment'] = 'false';
					break;

				/**
				 * Use OpenGraph Settings
				 * - Don't specify Media, as the service will scrape the URL for OpenGraph image tags
				 */
				case 0:
				case '':
					break;

				/**
				 * Use Feat. Image, Linked to Post
				 * - Facebook, LinkedIn
				 */
				case 1:
					// Fetch Title and Excerpt
					$title   = $this->get_title( $post );
					$excerpt = $this->get_excerpt( $post );

					$args['media'] = array(
						'link'        => $this->get_permalink( $post ),
						'description' => $excerpt,
						'title'       => $title,
						'picture'     => $image['image'],

						// Dashboard Thumbnail
						// Not supplied, as may results in cURL timeouts
					);
					break;

				/**
				 * Use Feat. Image, not Linked to Post
				 * - Facebook, LinkedIn, Twitter, Instagram, Pinterest
				 */
				case 2:
					// Fetch Title and Excerpt
					$title   = $this->get_title( $post );
					$excerpt = $this->get_excerpt( $post );

					$args['media'] = array(
						'description' => $excerpt,
						'title'       => $title,
						'picture'     => $image['image'],

						// Dashboard Thumbnail
						// Supplied, as required when specifying media with no link
						// Using the smallest possible image to avoid cURL timeouts
						'thumbnail'   => $image['thumbnail'],
					);
					break;

			}//end switch
		}//end if

		// Return args
		return $args;

	}


	private function get_post_image( $post, $service ) {

		// Featured Image
		$image_id = get_post_thumbnail_id( $post->ID );
		if ( $image_id > 0 ) {
			return $this->get_image_sources( $image_id, 'featured_image' );
		}

		// If here, no Featured Image was found and Text to Image isn't enabled for this status
		// Don't attempt to fetch an image from the Post Content if the Social Media Service doesn't require an image
		if ( $service != 'IG_BUSINESS' && $service != 'IG' && $service != 'PINIT_PAGE' ) {
			return false;
		}

		// Content's First Image
		$images = preg_match_all( '/<img.+?src=[\'"]([^\'"]+)[\'"].*?>/i', $post->post_content, $matches );
		if ( $images ) {
			return array(
				'image'     => strtok( $matches[1][0], '?' ),
				'thumbnail' => strtok( $matches[1][0], '?' ),
				'source'    => 'post_content',
			);
		}

		// If here, no image was found in the Post
		return false;

	}

	private function get_image_sources( $image_id, $source ) {

		// Get image sources
		$image     = wp_get_attachment_image_src( $image_id, 'large' );
		$thumbnail = wp_get_attachment_image_src( $image_id, 'thumbnail' );

		// Return URLs only
		return array(
			'image'     => strtok( $image[0], '?' ),
			'thumbnail' => strtok( $thumbnail[0], '?' ),
			'source'    => $source,
		);

	}


	public function parse_text( $post, $message, $character_limit = 0 ) {

		// Get Author
		$author = get_user_by( 'id', $post->post_author );

		// If we haven't yet populated the searches and replacements for this Post, do so now
		if ( ! $this->all_possible_searches_replacements ) {
			$this->all_possible_searches_replacements = $this->register_all_possible_searches_replacements( $post, $author );
		}

		// If no searches and replacements are defined, we can't parse anything
		if ( ! $this->all_possible_searches_replacements || count( $this->all_possible_searches_replacements ) == 0 ) {
			return $message;
		}

		// Extract all of the tags in the message
		preg_match_all( '|{(.+?)}|', $message, $matches );

		// If no tags exist in the message, there's nothing to parse
		if ( ! is_array( $matches ) ) {
			return $message;
		}
		if ( count( $matches[0] ) == 0 ) {
			return $message;
		}

		// Define return text
		$text = $message;

		// Iterate through extracted tags to build the search / replacement array
		foreach ( $matches[1] as $index => $tag ) {
			// Define some default attributes for this tag
			$tag_params = array(
				'tag_with_braces'      => $matches[0][ $index ],
				'tag_without_braces'   => $tag,
				'tag'                  => $tag,
				'character_limit'      => false,
				'word_limit'           => false,
				'taxonomy'             => false,
				'taxonomy_term_limit'  => false,
				'taxonomy_term_format' => false,
			);

			// If we already have a replacement for this exact tag (i.e. from a previous status message),
			// we don't need to define the replacement again.
			if ( isset( $this->searches_replacements[ $tag_params['tag_with_braces'] ] ) ) {
				continue;
			}

			// If a word or character limit is defined, fetch it now
			if ( preg_match( '/(.*?)\((.*?)_words\)/', $tag, $word_limit_matches ) ) {
				$tag_params['tag']        = $word_limit_matches[1];
				$tag_params['word_limit'] = absint( $word_limit_matches[2] );
			} elseif ( preg_match( '/(.*?)\((.*?)\)/', $tag, $character_limit_matches ) ) {
				$tag_params['tag']             = $character_limit_matches[1];
				$tag_params['character_limit'] = absint( $character_limit_matches[2] );
			}

			// If this Tag is a Taxonomy Tag, fetch some parameters that may be included in the tag
			if ( preg_match( '/^taxonomy_(.*?)$/', $tag, $taxonomy_matches ) ) {
				// Taxonomy with Hashtag Format
				$tag_params['taxonomy'] = str_replace( 'taxonomy_', '', $tag );
			}

			// Fetch possible tag replacement value
			$replacement = ( isset( $this->all_possible_searches_replacements[ $tag_params['tag'] ] ) ? $this->all_possible_searches_replacements[ $tag_params['tag'] ] : '' );

			// If this is a taxonomy replacement, replace according to the tag parameters
			if ( $tag_params['taxonomy'] != false ) {
				// Define a string to hold the list of terms
				$term_names = '';

				// Iterate through terms, building string
				foreach ( $replacement as $term_index => $term ) {

					// Lowercase and decode HTML
					$term_name = strtolower( str_replace( ' ', '', html_entity_decode( $term->name ) ) );

					// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
					// when posted to social media and not broken by e.g. a full stop
					$term_name = '#' . preg_replace( '/[^[:alnum:]_]/u', '', $term_name );

					// Add term to term names string
					$term_names .= $term_name . ' ';
				}

				// Finally, replace the array of terms with the string of formatted terms
				$replacement = trim( $term_names );
			}//end if

			// If a word or character limit is defined, apply it now, provided it's not a tag that prevents character limiting
			$can_apply_limit_to_tag = $this->can_apply_character_limit_to_tag( $tag_params['tag'] );
			if ( $can_apply_limit_to_tag ) {
				if ( $tag_params['word_limit'] > 0 ) {
					$replacement = $this->apply_word_limit( $replacement, $tag_params['word_limit'] );
				} elseif ( $tag_params['character_limit'] > 0 ) {
					$replacement = $this->apply_character_limit( $replacement, $tag_params['character_limit'] );
				}
			}

			// Add the search and replacement to the array
			$this->searches_replacements[ $tag_params['tag_with_braces'] ] = trim( $replacement );

		} //end foreach

		// Search and Replace
		$text = str_replace( array_keys( $this->searches_replacements ), $this->searches_replacements, $text );

		// Execute any shortcodes in the text now
		$text = do_shortcode( $text );

		// Remove double spaces, but retain newlines and accented characters
		$text = preg_replace( '/[ ]{2,}/', ' ', $text );

		// Return text
		return $text;

	}

	private function register_all_possible_searches_replacements( $post, $author ) {

		// Start with no searches or replacements
		$searches_replacements = array();

		// Register Post Tags and Replacements
		$searches_replacements = $this->register_post_searches_replacements( $searches_replacements, $post );

		// Register Taxonomy Tags and Replacements
		// Add Taxonomies
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		if ( count( $taxonomies ) > 0 ) {
			$searches_replacements = $this->register_taxonomy_searches_replacements( $searches_replacements, $post, $taxonomies );
		}

		// Return filtered results
		return $searches_replacements;

	}

	private function register_post_searches_replacements( $searches_replacements, $post ) {

		$searches_replacements['sitename'] = get_bloginfo( 'name' );
		$searches_replacements['title']    = $this->get_title( $post );
		$searches_replacements['excerpt']  = $this->get_excerpt( $post );
		$searches_replacements['content']  = $this->get_content( $post );
		$searches_replacements['date']     = date( 'dS F Y', strtotime( $post->post_date ) );
		$searches_replacements['url']      = $this->get_permalink( $post );
		$searches_replacements['id']       = absint( $post->ID );

		// Return filtered results
		return $searches_replacements;

	}

	private function register_taxonomy_searches_replacements( $searches_replacements, $post, $taxonomies ) {

		foreach ( $taxonomies as $taxonomy ) {
			$searches_replacements[ 'taxonomy_' . $taxonomy ] = wp_get_post_terms( $post->ID, $taxonomy );
		}

		// Return filtered results
		return $searches_replacements;

	}

	private function get_title( $post ) {

		// Define title
		$title = html_entity_decode( strip_tags( strip_shortcodes( get_the_title( $post ) ) ) );

		// Return
		return $title;

	}

	private function get_excerpt( $post ) {

		// Fetch excerpt
		if ( empty( $post->post_excerpt ) ) {
			$excerpt = $post->post_content;
		} else {
			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );
		}

		// Strip shortcodes
		$excerpt = strip_shortcodes( $excerpt );

		// Strip HTML Tags
		$excerpt = strip_tags( $excerpt );

		// Decode excerpt to avoid encoding issues on status output
		$excerpt = html_entity_decode( $excerpt );

		// Finally, trim the output
		$excerpt = trim( $excerpt );

		// Return
		return $excerpt;

	}

	private function get_content( $post ) {

		// Fetch content
		// get_the_content() only works for WordPress 5.2+, which added the $post param
		$content = $post->post_content;

		// Strip shortcodes
		$content = strip_shortcodes( $content );

		// Apply filters to get true output
		$content = apply_filters( 'the_content', $content );

		// If the content originates from Gutenberg, remove double newlines and convert breaklines
		// into newlines
		$is_gutenberg_post_content = $this->is_gutenberg_post_content( $post );
		if ( $is_gutenberg_post_content ) {
			// Remove double newlines, which may occur due to using Gutenberg blocks
			// (blocks are separated with HTML comments, stripped using apply_filters( 'the_content' ), which results in double, or even triple, breaklines)
			$content = preg_replace( '/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $content );

			// Convert <br> and <br /> into newlines
			$content = preg_replace( '/<br(\s+)?\/?>/i', "\n", $content );
		}

		// Strip HTML Tags
		$content = strip_tags( $content );

		// Decode content to avoid encoding issues on status output
		$content = html_entity_decode( $content );

		// Finally, trim the output
		$content = trim( $content );

		// Return
		return $content;

	}

	private function get_permalink( $post ) {

		$force_trailing_forwardslash = $this->settings->get_option( 'force_trailing_forwardslash', false );

		// Define the URL, depending on whether it should end with a forwardslash or not
		// This is by design; more users complain that they get 301 redirects from site.com/post/ to site.com/post
		// than from site.com/post to site.com/post/
		// We can't control misconfigured WordPress installs, so this option gives them the choice
		if ( $force_trailing_forwardslash ) {
			$url = get_permalink( $post->ID );

			// If the Permalink doesn't have a forwardslash at the end of it, add it now
			if ( substr( $url, - 1 ) !== '/' ) {
				$url .= '/';
			}
		} else {
			$url = rtrim( get_permalink( $post->ID ), '/' );
		}

		// Return
		return $url;

	}


	private function can_apply_character_limit_to_tag( $tag ) {

		// Get Tags
		$tags = array(
			'date',
			'url',
			'id',
			'author_user_email',
			'author_user_url',
		);

		// If the tag is in the array of tags excluded from character limits, we
		// cannot apply a character limit to this tag
		if ( in_array( $tag, $tags ) ) {
			return false;
		}

		// Can apply character limit to tag
		return true;

	}


	private function apply_word_limit( $text, $word_limit = 0 ) {

		// Bail if the word limit is zero or false
		if ( ! $word_limit || $word_limit == 0 ) {
			return $text;
		}

		// Limit text
		$text = wp_trim_words( $text, $word_limit, '' );

		// Return
		return $text;

	}


	private function apply_character_limit( $text, $character_limit = 0 ) {

		// Bail if the character limit is zero or false
		if ( ! $character_limit || $character_limit == 0 ) {
			return $text;
		}

		// Bail if the content isn't longer than the character limit
		if ( strlen( $text ) <= $character_limit ) {
			return $text;
		}

		// Limit text
		$text = substr( $text, 0, $character_limit );

		// Return
		return $text;

	}

	public function send( $statuses, $post_id, $action, $profiles, $test_mode = false ) {

		// Assume no errors
		$errors = false;

		// Setup logging
		$logs        = array();
		$log_error   = array();
		$log_enabled = $this->settings->get_option( 'log_enabled', false );

		foreach ( $statuses as $index => $status ) {

			if ( $status['profile_ids'][0] == 'default' ) {
				continue;
			}

			$image = isset( $status['media'] ) ? $status['media']['picture'] : '';

			// If no image specified for Insgatram & Pinterest, add warning and bail
			if ( empty( $image ) && in_array(
				$profiles[ $status['profile_ids'][0] ]['type'],
				array(
					'IG',
					'IG_BUSINESS',
					'PINIT_PAGE',
				)
			) ) {
				$logs[] = array(
					'action'         => $action,
					'request_sent'   => date( 'Y-m-d H:i:s' ),
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['type'] . ': ' . $profiles[ $status['profile_ids'][0] ]['name'],
					'result'         => 'warning',
					'result_message' => 'Image required for this profile',
					'status_text'    => $status['text'],
				);
				continue;
			}

			// If this is a test, add to the log array only
			if ( $test_mode ) {
				$logs[] = array(
					'action'         => $action,
					'request_sent'   => date( 'Y-m-d H:i:s' ),
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['type'] . ': ' . $profiles[ $status['profile_ids'][0] ]['name'],
					'result'         => 'test',
					'result_message' => '',
					'status_text'    => $status['text'],
				);

				continue;
			}

			$posts = array(
				array(
					'postType'  => $status['postType'],
					'post'      => $status['text'],
					'profileId' => $status['profile_ids'][0],
				),
			);

			if ( ! empty( $image ) ) {
				$posts[0]['image'] = $image;
			}

			// Send request
			$result = $this->api->sendPost( $posts );

			// Store result in log array
			if ( $result['result'] == 'error' ) {
				// Error
				$errors      = true;
				$logs[]      = array(
					'action'         => $action,
					'request_sent'   => date( 'Y-m-d H:i:s' ),
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['type'] . ': ' . $profiles[ $status['profile_ids'][0] ]['name'],
					'result'         => 'error',
					'result_message' => $result['result_message'],
					'status_text'    => $status['text'],
				);
				$log_error[] = ( $profiles[ $status['profile_ids'][0] ]['type'] . ': ' . $profiles[ $status['profile_ids'][0] ]['name'] . ': ' . $result['result_message'] );
			} else {
				// OK
				$logs[] = array(
					'action'            => $action,
					'request_sent'      => date( 'Y-m-d H:i:s' ),
					'profile_id'        => $status['profile_ids'][0],
					'profile_name'      => $profiles[ $status['profile_ids'][0] ]['type'] . ': ' . $profiles[ $status['profile_ids'][0] ]['name'],
					'result'            => 'success',
					'result_message'    => $result['result_message'],
					'status_text'       => $status['text'],
					'status_created_at' => date( 'Y-m-d H:i:s' ),
					'status_due_at'     => date( 'Y-m-d H:i:s' ),
				// 'status_created_at' => date( 'Y-m-d H:i:s', $result['status_created_at'] ),
				// 'status_due_at'     => date( 'Y-m-d H:i:s', $result['due_at'] ),
				);
			}//end if
		}//end foreach

		// Set the last sent timestamp, which we may use to prevent duplicate statuses
		update_post_meta( $post_id, '_' . self::PREFIX . '_last_sent', current_time( 'timestamp' ) );

		// If we're reposting, update the last reposted date against the Post
		// We do this here to ensure the Post isn't reposting again where e.g. one profile status worked + one profile status failed,
		// which would be deemed a failure
		if ( $action == 'repost' && ! $test_mode ) {
			// $this->base->get_class( 'repost' )->update_last_reposted_date( $post_id );
		}

		// If no errors were reported, set a meta key to show a success message
		// This triggers admin_notices() to tell the user what happened
		if ( ! $errors ) {
			// Only set a success message if test mode is disabled
			if ( ! $test_mode ) {
				update_post_meta( $post_id, '_' . self::PREFIX . '_success', 1 );
			}
			delete_post_meta( $post_id, '_' . self::PREFIX . '_error' );
			delete_post_meta( $post_id, '_' . self::PREFIX . '_errors' );

		} else {
			update_post_meta( $post_id, '_' . self::PREFIX . '_success', 0 );
			update_post_meta( $post_id, '_' . self::PREFIX . '_error', 1 );
			update_post_meta( $post_id, '_' . self::PREFIX . '_errors', $log_error );
		}

		// Save the log, if logging is enabled
		if ( $log_enabled ) {
			foreach ( $logs as $log ) {
				$this->log->add( $post_id, $log );
			}
		}

		// Return log results
		return $logs;

	}


	private function clear_search_replacements() {

		$this->all_possible_searches_replacements = false;
		$this->searches_replacements              = false;

	}

}

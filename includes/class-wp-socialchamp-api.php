<?php


require_once 'class-wp-socialchamp-auth.php';

class WP_SocialChamp_API {

	public $redirectUrl = '';

	/**
	 * @var WP_SocialChamp_Auth
	 */
	public $provider;

	public $baseUrl = 'https://www.socialchamp.io';

	/**
	 * WP_SocialChamp_API constructor.
	 */
	public function __construct() {
		$this->provider = new WP_SocialChamp_Auth(
			array(
				'clientId'                => 'ZAxH8oxsW8',
				'clientSecret'            => 'BUhUxDP5T1SXepEXAOj7U5CKRiCfJXMBY6AUiipAma9VhGptSM',
				'redirectUri'             => $this->baseUrl . '/oauth2/wpRedirect/ZAxH8oxsW8',
				'urlAuthorize'            => $this->baseUrl . '/oauth2/authorize',
				'urlAccessToken'          => $this->baseUrl . '/oauth2/token',
				'urlResourceOwnerDetails' => $this->baseUrl . '/secure/api/v1/profile',
				'scopes'                  => 'read_profile,manage_post',
			)
		);

		$this->redirectUrl = admin_url( 'admin.php?page=wp-socialchamp-settings' );
	}

	public function getAuthUrl() {
		return $this->provider->getAuthorizationUrl( array( 'state' => $this->redirectUrl ) );
	}

	public function getDisconnectUrl() {
		return $this->redirectUrl . '&disconnect=1';
	}

	public function getProfilesUrl() {
		return $this->redirectUrl . '&refresh-profiles=1';
	}

	public function handleDisconnect() {
		if ( isset( $_GET['disconnect'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$auth = get_option( 'wpsc_auth' );
			if ( $auth ) {
				foreach ( $auth as $key => $value ) {
					delete_option( 'wpsc_' . $key );
				}
				delete_option( 'wpsc_auth' );
				delete_option( 'wpsc_profiles' );
				wp_safe_redirect( $this->redirectUrl );
			}
		}
	}

	public function handleProfileRefresh() {
		if ( isset( $_GET['refresh-profiles'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->getProfiles( true );
		}
	}

	public function handleAuthCode() {
		if ( isset( $_GET['code'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			try {
				$_code       = sanitize_text_field( wp_unslash( $_GET['code'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$accessToken = $this->provider->getAccessToken(
					'authorization_code',
					array(
						'code' => $_code,
					)
				);

				$auth = array(
					'access_token'         => $accessToken->getToken(),
					'refresh_token'        => $accessToken->getRefreshToken(),
					'access_token_expiry'  => $accessToken->getValues()['access_token_expiry'],
					'refresh_token_expiry' => $accessToken->getValues()['refresh_token_expiry'],
					'auth_name'            => $accessToken->getValues()['name'],
					'auth_email'           => $accessToken->getValues()['email'],
				);

				update_option( 'wpsc_' . 'auth', $auth );
				foreach ( $auth as $key => $value ) {
					update_option( 'wpsc_' . $key, $value );
				}

				$this->getProfiles( true );
			} catch ( Exception $e ) {

			} finally {
				wp_safe_redirect( $this->redirectUrl );
				exit;
			}//end try
		}//end if
	}

	public function getProfiles( $force = false ) {
		$profiles = get_option( 'wpsc_profiles' );
		if ( ! $force && $profiles ) {
			return $profiles;
		}

		$profiles = $this->get( 'profile' );
		if ( $profiles ) {
			update_option( 'wpsc_profiles', $profiles );

			return $profiles;
		}

		return array();

	}

	public function sendPost( $data ) {

		$params = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array( 'posts' => $data ) ),
		);

		$result = $this->post( 'post', $params );

		if ( ! $result || ! isset( $result['isScheduled'] ) ) {
			return array(
				'result'         => 'error',
				'result_message' => ! empty( $result['error'] ) ? $result['error'] : 'API Error occurred.'
			);
		} else {
			if ( count( $result['unverifiedUsers'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'Your account is unverified. Please verify your account.',
				);
			}

			if ( count( $result['noPermUsers'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'You do not have permission to post on this social media account. Please re-connect this social profile.',
				);
			}

			if ( count( $result['limitErrorUsers'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'You have exceeded your posts limit.',
				);
			}

			if ( count( $result['duplicateContent'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'This is a duplicate post.',
				);
			}

			if ( count( $result['invalidPostUsers'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'This post does not go with the format set by the social media network. For e.g. missing image, character limit, etc.',
				);
			}

			if ( count( $result['noTableUsers'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'There is no queue present for scheduling. Please create a queue first.',
				);
			}

			if ( count( $result['notFoundUsers'] ) > 0 ) {
				return array(
					'result'         => 'error',
					'result_message' => 'The account might have been removed.',
				);
			}

			return array(
				'result'         => 'success',
				'result_message' => 'Post published',
			);

		}//end if

	}

	public function get( $endpoint ) {
		return $this->request( $endpoint );
	}

	public function post( $endpoint, $data ) {
		return $this->request( $endpoint, $data, 'POST' );
	}

	public function request( $endpoint, $data = array(), $type = 'GET' ) {
		try {
			$url = $this->baseUrl . '/secure/api/v1/' . $endpoint;
			// if ( ! self::isLoggedIn() ) {
			// 	return false;
			// }
			$request = $this->provider->getAuthenticatedRequest(
				$type,
				$url,
				$this->getAccessToken(),
				$data
			);

			return $this->provider->getParsedResponse( $request );
		} catch ( Exception $e ) {
			return false;
		}
	}

	public static function isLoggedIn() {
		$auth = get_option( 'wpsc_auth' );
		if ( $auth ) {
			if ( strtotime( $auth['refresh_token_expiry'] ) > time() ) {
				return true;
			}
		}

		return false;
	}

	public function getAccessToken() {
		return get_option( 'wpsc_access_token', '' );
	}

}

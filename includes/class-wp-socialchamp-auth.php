<?php

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'vendor/autoload.php';

class WP_SocialChamp_Auth extends \League\OAuth2\Client\Provider\GenericProvider {

	/**
	 * Requests an access token using a specified grant and option set.
	 *
	 * @param mixed $grant
	 * @param array $options
	 *
	 * @return AccessTokenInterface
	 * @throws IdentityProviderException
	 */
	public function getAccessToken( $grant, array $options = array() ) {
		$grant = $this->verifyGrant( $grant );

		$params = array(
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret,
			'redirect_uri'  => $this->redirectUri,
		);

		$params   = $grant->prepareRequestParameters( $params, $options );
		$request  = $this->getAccessTokenRequest( $params );
		$response = $this->getParsedResponse( $request );
		if ( false === is_array( $response ) ) {
			throw new UnexpectedValueException(
				'Invalid response received from Authorization Server. Expected JSON.'
			);
		}

		$response['expires_in'] = strtotime( $response['access_token_expiry'] ) - time();

		$prepared = $this->prepareAccessTokenResponse( $response );
		$token    = $this->createAccessToken( $prepared, $grant );

		return $token;
	}

}

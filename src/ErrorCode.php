<?php

namespace Nebkam\ZohoOAuth;

enum ErrorCode: string
	{
	/**
	 * - You have passed an invalid Client ID or secret. Specify the correct client ID and secret.
	 * - There is a domain mismatch. You have registered the client and generated the grant token in a certain domain (US), but generating the tokens from a different domain (EU). Ensure that you generate the grant, access, and refresh tokens from the same domain using the same domain URL or Enable Multi-DC for your client to generate tokens from any domain.
	 * - You have passed the wrong client secret when multi-DC is enabled. Each DC holds a unique client secret. Ensure to pass the right client secret for that DC.
	 */
	case INVALID_CLIENT = 'invalid_client';

	/**
	 * - The grant token has expired. Generate the access and refresh tokens before the grant token expires.
	 * - You have already used the grant token. You can use the grant token only once.
	 * - The refresh token to generate a new access token is wrong or revoked. Specify the correct refresh token value while refreshing an access token.
	 */
	case INVALID_CODE = 'invalid_code';
	}

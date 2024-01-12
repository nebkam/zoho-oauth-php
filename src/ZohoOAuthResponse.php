<?php

namespace Nebkam\ZohoOAuth;

use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @see https://www.zoho.com/crm/developer/docs/api/v2.1/access-refresh.html
 */
class ZohoOAuthResponse
	{
	public ?ErrorCode $error = null;

	#[SerializedName("access_token")]
	public ?string $accessToken;

	#[SerializedName("refresh_token")]
	public ?string $refreshToken;

	#[SerializedName("expires_in")]
	public ?int $expiresInSeconds;

	#[SerializedName("api_domain")]
	public ?string $apiDomain;

	#[SerializedName("token_type")]
	public ?string $tokenType;
	}

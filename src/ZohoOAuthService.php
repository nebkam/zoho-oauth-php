<?php

namespace Nebkam\ZohoOAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Serializer\SerializerInterface;

class ZohoOAuthService
	{
	private const ENDPOINT = 'https://accounts.zoho.eu/oauth/v2/';
	/** @var Client $client  */
	private $client;
	/** @var SerializerInterface $serializer */
	private $serializer;
	/** @var string|null $clientId */
	private $clientId;
	/** @var string|null $clientSecret */
	private $clientSecret;
	/** @var string|null $credentialsPath */
	private $credentialsPath;

	public function __construct(
		Client $client,
		SerializerInterface $serializer,
		?string $clientId,
		?string $clientSecret,
		?string $credentialsPath
	)
		{
		$this->client          = $client;
		$this->serializer      = $serializer;
		$this->clientId        = $clientId;
		$this->clientSecret    = $clientSecret;
		$this->credentialsPath = $credentialsPath;
		}

	/**
	 * Generates and saves the credentials (access token and refresh token) to filesystem (credentialsPath)
	 * Should be done only once per deploy, because the refresh token is used to get more access tokens afterwards.
	 * Remember, one grant token can be used only once.
	 *
	 * @param string $grantToken
	 * @return ZohoOAuthResponse
	 * @throws ZohoOAuthException
	 */
	public function generateCredentials(string $grantToken): ZohoOAuthResponse
		{
		$credentials = $this->fetchCredentials([
			'response_type' => 'code',
			'grant_type'    => 'authorization_code',
			'access_type'   => 'offline',
			'code'          => $grantToken
		], 'Couldn\'t generate refresh token: %s');
		$this->persistCredentials($credentials);

		return $credentials;
		}

	/**
	 * @return ZohoOAuthResponse
	 * @throws ZohoOAuthException
	 */
	public function refreshAccessToken(): ZohoOAuthResponse
		{
		$credentials               = $this->readCredentials();
		$updated                   = $this->fetchCredentials([
			'grant_type'    => 'refresh_token',
			'refresh_token' => $credentials->refresh_token,
		], 'Couldn\'t refresh access token: %s');
		$credentials->access_token = $updated->access_token;
		$this->persistCredentials($credentials);

		return $credentials;
		}

	/**
	 * @return ZohoOAuthResponse
	 * @throws ZohoOAuthException
	 */
	public function getCredentials(): ZohoOAuthResponse
		{
		return $this->readCredentials();
		}

	/**
	 * @param array $params
	 * @param string $exceptionMessage
	 * @return ZohoOAuthResponse
	 * @throws ZohoOAuthException
	 */
	private function fetchCredentials(array $params, string $exceptionMessage): ZohoOAuthResponse
		{
		$params = array_merge([
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret
		], $params);
		try
			{
			$response = $this->client->request('POST', self::ENDPOINT . 'token', [
				RequestOptions::FORM_PARAMS => $params
			]);
			/** @var ZohoOAuthResponse $data */
			$data = $this->serializer->deserialize((string) $response->getBody(), ZohoOAuthResponse::class, 'json');
			if ($data->error)
				{
				throw new ZohoOAuthException(sprintf($exceptionMessage, $data->error));
				}

			return $data;
			}
		catch (GuzzleException $exception)
			{
			throw new ZohoOAuthException(sprintf($exceptionMessage, $exception->getMessage()), $exception);
			}
		}

	/**
	 * @throws ZohoOAuthException
	 */
	private function persistCredentials(ZohoOAuthResponse $response): void
		{
		$saved = file_put_contents($this->credentialsPath, $this->serializer->serialize($response, 'json'));
		if ($saved === false)
			{
			throw new ZohoOAuthException(sprintf('Could not save credentials at `%s`. Check the file permissions', $this->credentialsPath));
			}
		}

	/**
	 * @return ZohoOAuthResponse
	 * @throws ZohoOAuthException
	 */
	private function readCredentials(): ZohoOAuthResponse
		{
		if (file_exists($this->credentialsPath))
			{
			/** @var ZohoOAuthResponse $data */
			/** @noinspection PhpIncompatibleReturnTypeInspection */
			return $this->serializer->deserialize(file_get_contents($this->credentialsPath), ZohoOAuthResponse::class, 'json');
			}

		throw new ZohoOAuthException(sprintf('Could not read credentials at `%s`. Try generating credentials again', $this->credentialsPath));
		}
	}

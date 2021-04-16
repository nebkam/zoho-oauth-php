<?php

namespace Nebkam\ZohoOAuth;

use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZohoOAuthService
	{
	private const ENDPOINT = 'https://accounts.zoho.eu/oauth/v2/';
	private HttpClientInterface $client;
	private SerializerInterface $serializer;
	private ?string $clientId;
	private ?string $clientSecret;
	private ?string $credentialsPath;

	public function __construct(
		HttpClientInterface $client,
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
		$credentials              = $this->readCredentials();
		$updated                  = $this->fetchCredentials([
			'grant_type'    => 'refresh_token',
			'refresh_token' => $credentials->refreshToken,
		], 'Couldn\'t refresh access token: %s');
		$credentials->accessToken = $updated->accessToken;
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
				'body' => $params
			]);
			/** @var ZohoOAuthResponse $data */
			$data = $this->serializer->deserialize($response->getContent(), ZohoOAuthResponse::class, 'json');
			if ($data->error)
				{
				throw new ZohoOAuthException(sprintf($exceptionMessage, $data->error));
				}

			return $data;
			}
		catch (TransportExceptionInterface | RedirectionExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $exception)
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
			return $this->serializer->deserialize(file_get_contents($this->credentialsPath), ZohoOAuthResponse::class, 'json');
			}

		throw new ZohoOAuthException(sprintf('Could not read credentials at `%s`. Try generating credentials again', $this->credentialsPath));
		}
	}

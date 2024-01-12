<?php

namespace Nebkam\ZohoOAuth;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZohoOAuthService
	{
	private const ENDPOINT = 'https://accounts.zoho.eu/oauth/v2/';

	public function __construct(
		private readonly HttpClientInterface $client,
		private readonly SerializerInterface $serializer,
		private readonly ?string             $clientId,
		private readonly ?string             $clientSecret,
		private readonly ?string             $credentialsPath
	)
		{
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
	public function getCredentials(): ZohoOAuthResponse
		{
		return $this->readCredentials();
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

			$data = $this->serializer->deserialize($response->getContent(), ZohoOAuthResponse::class, JsonEncoder::FORMAT);
			if ($data->error)
				{
				throw new ZohoOAuthException(sprintf($exceptionMessage, $data->error));
				}

			return $data;
			}
		catch (TransportExceptionInterface|RedirectionExceptionInterface|ClientExceptionInterface|ServerExceptionInterface $exception)
			{
			throw new ZohoOAuthException(sprintf($exceptionMessage, $exception->getMessage()), $exception);
			}
		}

	/**
	 * @throws ZohoOAuthException
	 */
	private function persistCredentials(ZohoOAuthResponse $response): void
		{
		$saved = file_put_contents($this->credentialsPath, $this->serializer->serialize($response, JsonEncoder::FORMAT));
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
			return $this->serializer->deserialize(file_get_contents($this->credentialsPath), ZohoOAuthResponse::class, JsonEncoder::FORMAT);
			}

		throw new ZohoOAuthException(sprintf('Could not read credentials at `%s`. Try generating credentials again', $this->credentialsPath));
		}
	}

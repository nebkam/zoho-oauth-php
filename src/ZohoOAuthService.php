<?php

namespace Nebkam\ZohoOAuth;

use InvalidArgumentException;
use LogicException;
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
	 * Generates and saves the refresh token to filesystem.
	 * Should be done only once per deploy, because the refresh token is used to get more access tokens afterwards.
	 * Remember, one grant token can be used only once.
	 *
	 * @param string $grantToken
	 * @return ZohoOAuthResponse
	 * @throws InvalidArgumentException
	 * @throws ZohoOAuthException
	 */
	public function generateRefreshToken(string $grantToken): ZohoOAuthResponse
		{
		try
			{
			$response = $this->client->request('POST', self::ENDPOINT . 'token', [
				'body' => [
					'response_type' => 'code',
					'grant_type'    => 'authorization_code',
					'access_type'   => 'offline',
					'client_id'     => $this->clientId,
					'client_secret' => $this->clientSecret,
					'code'          => $grantToken
				]
			]);
			/** @var ZohoOAuthResponse $data */
			$data = $this->serializer->deserialize($response->getContent(), ZohoOAuthResponse::class, 'json');
			if ($data->error)
				{
				throw new ZohoOAuthException(sprintf('Couldn\'t generate refresh token: %s', $data->error));
				}
			$this->persistCredentials($data);

			return $data;
			}
		catch (TransportExceptionInterface | RedirectionExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $exception)
			{
			throw new ZohoOAuthException(sprintf('Couldn\'t generate refresh token: %s', $exception->getMessage()), $exception);
			}
		}

	private function persistCredentials(ZohoOAuthResponse $response): void
		{
		file_put_contents($this->credentialsPath, $this->serializer->serialize($response, 'json'));
		}

	/**
	 * @return ZohoOAuthResponse
	 * @throws LogicException
	 */
	private function readCredentials(): ZohoOAuthResponse
		{
		if (file_exists($this->credentialsPath))
			{
			/** @var ZohoOAuthResponse $data */
			return $this->serializer->deserialize(file_get_contents($this->credentialsPath), ZohoOAuthResponse::class, 'json');
			}

		throw new LogicException('Could not read credentials at ' . $this->credentialsPath . '. Try generating Refresh Token again');
		}
	}

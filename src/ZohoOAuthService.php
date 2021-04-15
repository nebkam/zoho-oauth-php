<?php

namespace Nebkam\ZohoOAuth;

use InvalidArgumentException;
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

	public function __construct(HttpClientInterface $client, SerializerInterface $serializer, ?string $clientId, ?string $clientSecret)
		{
		$this->client       = $client;
		$this->serializer   = $serializer;
		$this->clientId     = $clientId;
		$this->clientSecret = $clientSecret;
		}

	/**
	 * @param string $product
	 * @param string $grantToken
	 * @throws InvalidArgumentException
	 * @throws ZohoOAuthException
	 */
	public function generateRefreshToken(string $product, string $grantToken): void
		{
		try
			{
			$response = $this->client->request('POST', self::ENDPOINT.'token', [
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
			}
		catch (TransportExceptionInterface | RedirectionExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $exception)
			{
			throw new ZohoOAuthException(sprintf('Couldn\'t generate refresh token: %s', $exception->getMessage()), $exception);
			}
		}
	}
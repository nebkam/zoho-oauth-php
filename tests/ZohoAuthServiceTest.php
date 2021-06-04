<?php

namespace Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\Client;
use Nebkam\ZohoOAuth\ZohoOAuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ZohoAuthServiceTest extends TestCase
	{
	private static function createSerializer(): SerializerInterface
		{
		$objectNormalizer = new ObjectNormalizer(
			new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader())),
			new CamelCaseToSnakeCaseNameConverter(),
			null,
			new PhpDocExtractor()
		);

		return new Serializer(
			[$objectNormalizer],
			[new JsonEncoder()]
		);
		}

	/**
	 * @return ZohoOAuthService
	 */
	public function testInit(): ZohoOAuthService
		{
		$service = new ZohoOAuthService(
			new Client(),
			self::createSerializer(),
			getenv('CLIENT_ID'),
			getenv('CLIENT_SECRET'),
			getenv('CREDENTIALS_PATH')
		);
		$this->assertNotNull($service);

		return $service;
		}

	/**
	 * @depends testInit
	 * @param ZohoOAuthService $service
	 */
	public function testReadCredentials(ZohoOAuthService $service): void
		{
		$credentials = $service->getCredentials();
		$this->assertNotEmpty($credentials->accessToken);
		$this->assertNotEmpty($credentials->refreshToken);
		}

	/**
	 * @depends testInit
	 * @param ZohoOAuthService $service
	 */
	public function testRefreshCredentials(ZohoOAuthService $service): void
		{
		$credentials = $service->refreshAccessToken();
		$this->assertNotEmpty($credentials->accessToken);
		$this->assertNotEmpty($credentials->refreshToken);
		}
	}

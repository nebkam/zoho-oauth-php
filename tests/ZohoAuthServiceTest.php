<?php

namespace Tests;

use DateTimeZone;
use Doctrine\Common\Annotations\AnnotationReader;
use Nebkam\ZohoOAuth\ZohoOAuthService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\NativeHttpClient;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ZohoAuthServiceTest extends TestCase
	{
	private static function createSerializer(): SerializerInterface
		{
		$classMetadataFactory   = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
		$snakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
		$objectNormalizer       = new ObjectNormalizer(
			$classMetadataFactory,
			$snakeCaseNameConverter,
			null,
			new PhpDocExtractor()
		);
		$dateTimeNormalizer     = new DateTimeNormalizer([
			DateTimeNormalizer::FORMAT_KEY   => 'd.m.Y. H:i',
			DateTimeNormalizer::TIMEZONE_KEY => new DateTimeZone('Europe/Belgrade')
		]);

		return new Serializer(
			[$dateTimeNormalizer, $objectNormalizer, new ArrayDenormalizer()],
			[new NotNullJsonEncoder()]
		);
		}

	/**
	 * @return ZohoOAuthService
	 */
	public function testInit(): ZohoOAuthService
		{
		$auth = new ZohoOAuthService(
			new NativeHttpClient(),
			self::createSerializer(),
			getenv('CLIENT_ID'),
			getenv('CLIENT_SECRET'),
			getenv('CREDENTIALS_PATH')
		);
		$auth->refreshAccessToken();
		$this->assertNotNull($auth);

		return $auth;
		}
	}

<?php

namespace Tests;

use Symfony\Component\Serializer\Encoder\JsonEncode;

class NotNullJsonEncode extends JsonEncode
	{
	public function encode($data, $format, array $context = array())
		{
		$data = Helper::filterNullRecursive($data);

		return parent::encode($data, $format, $context);
		}
	}

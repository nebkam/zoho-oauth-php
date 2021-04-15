<?php

namespace Nebkam\ZohoOAuth;

use RuntimeException;
use Throwable;

class ZohoOAuthException extends RuntimeException
	{
	public function __construct($message = "", Throwable $previous = null)
		{
		parent::__construct($message, 500, $previous);
		}
	}

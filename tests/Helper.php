<?php

namespace Tests;

class Helper
	{
	/**
	 * @param mixed $array
	 * @return mixed
	 */
	public static function filterNullRecursive($array)
		{
		if (is_array($array) && !empty($array))
			{
			foreach ($array as $key => $value)
				{
				if ($value === null)
					{
					unset($array[$key]);
					}
				elseif (is_array($value) && !empty($value))
					{
					$array[$key] = self::filterNullRecursive($value);
					}
				}
			}

		return $array;
		}
	}

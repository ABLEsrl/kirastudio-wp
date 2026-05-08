<?php

declare(strict_types=1);

namespace KiraStudio\Traits;

trait Singleton
{
	private static $instance;

	public static function instance()
	{
		if (null === static::$instance) {
			static::$instance = new static();
		}

		return static::$instance;
	}
}

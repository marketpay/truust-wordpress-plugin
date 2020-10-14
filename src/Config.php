<?php

namespace Truust;

defined( 'ABSPATH' ) || exit;

use Illuminate\Config\Repository;

class Config extends Repository
{
	public function __construct($path)
	{
		$config = require $path;

		foreach ($config as $key => $value) {
			$this->set($key, $value);
		}
	}
}

<?php declare(strict_types = 1);

namespace Orisai\Installer\Schemas;

use Nette\Schema\Elements\Structure;

interface Schema
{

	public const VERSIONS = [
		self::VERSION_1_0,
	];

	public const VERSION_1_0 = 1;

	public function getStructure(): Structure;

}

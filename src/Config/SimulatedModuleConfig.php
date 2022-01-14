<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Orisai\Installer\Schema\MonorepoSubpackageSchema;

/**
 * @internal
 */
final class SimulatedModuleConfig
{

	private string $name;

	private string $path;

	private bool $optional;

	public function __construct(MonorepoSubpackageSchema $schema)
	{
		$this->name = $schema->getName();
		$this->path = $schema->getPath();
		$this->optional = $schema->isOptional();
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function isOptional(): bool
	{
		return $this->optional;
	}

}

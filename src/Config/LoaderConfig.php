<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

use Orisai\Installer\Schema\LoaderSchema;

/**
 * @internal
 */
final class LoaderConfig
{

	private string $file;

	private string $class;

	public function __construct(LoaderSchema $schema)
	{
		$this->file = $schema->getFile();
		$this->class = $schema->getClass();
	}

	public function getFile(): string
	{
		return $this->file;
	}

	public function getClass(): string
	{
		return $this->class;
	}

}

<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

final class MonorepoSubpackageSchema
{

	private string $name;

	private string $path;

	private bool $optional = false;

	/**
	 * @internal
	 */
	public function __construct(string $name, string $path)
	{
		$this->name = $name;
		$this->path = $path;
	}

	public function setOptional(bool $optional = true): void
	{
		$this->optional = $optional;
	}

	/**
	 * @internal
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @internal
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @internal
	 */
	public function isOptional(): bool
	{
		return $this->optional;
	}

}

<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

final class LoaderSchema
{

	private string $file;

	private string $class;

	/**
	 * @internal
	 */
	public function __construct(string $file, string $class)
	{
		$this->file = $file;
		$this->class = $class;
	}

	/**
	 * @internal
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @internal
	 */
	public function getClass(): string
	{
		return $this->class;
	}

}

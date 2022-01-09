<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

/**
 * @internal
 */
final class LoaderConfig
{

	public const
		FILE_OPTION = 'file',
		CLASS_OPTION = 'class';

	private string $file;

	private string $class;

	/**
	 * @param array<mixed> $config
	 */
	public function __construct(array $config)
	{
		$this->file = $config[self::FILE_OPTION];
		$this->class = $config[self::CLASS_OPTION];
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

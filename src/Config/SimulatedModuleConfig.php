<?php declare(strict_types = 1);

namespace Orisai\Installer\Config;

/**
 * @internal
 */
final class SimulatedModuleConfig
{

	public const
		NAME_OPTION = 'name',
		PATH_OPTION = 'path';

	public const OPTIONAL_OPTION = 'optional';

	public const OPTIONAL_DEFAULT = false;

	private string $name;

	private string $path;

	private bool $optional;

	/**
	 * @param array<mixed> $config
	 */
	public function __construct(array $config)
	{
		$this->name = $config[self::NAME_OPTION];
		$this->path = $config[self::PATH_OPTION];
		$this->optional = $config[self::OPTIONAL_OPTION];
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

<?php declare(strict_types = 1);

namespace Orisai\Installer\Resolving;

/**
 * @internal
 */
final class Link
{

	private string $source;

	private string $target;

	public function __construct(string $source, string $target)
	{
		$this->source = $source;
		$this->target = $target;
	}

	public function getSource(): string
	{
		return $this->source;
	}

	public function getTarget(): string
	{
		return $this->target;
	}

}

<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

use ValueError;
use function array_key_exists;

final class ConfigFilePriority
{

	private const Normal = 1,
		High = 2,
		Low = 3;

	private const ValuesAndNames = [
		self::Normal => 'normal',
		self::High => 'high',
		self::Low => 'low',
	];

	/** @readonly */
	public string $name;

	/** @readonly */
	public int $value;

	private function __construct(string $name, int $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	public static function normal(): self
	{
		return self::from(self::Normal);
	}

	public static function high(): self
	{
		return self::from(self::High);
	}

	public static function low(): self
	{
		return self::from(self::Low);
	}

	public static function tryFrom(int $value): ?self
	{
		if (!array_key_exists($value, self::ValuesAndNames)) {
			return null;
		}

		return new self(self::ValuesAndNames[$value], $value);
	}

	public static function from(int $value): self
	{
		$self = self::tryFrom($value);

		if ($self === null) {
			throw new ValueError();
		}

		return $self;
	}

	/**
	 * @return array<self>
	 */
	public static function cases(): array
	{
		$cases = [];
		foreach (self::ValuesAndNames as $value => $name) {
			$cases[] = self::from($value);
		}

		return $cases;
	}

}

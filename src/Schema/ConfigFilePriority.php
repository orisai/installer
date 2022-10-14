<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

use ValueError;

final class ConfigFilePriority
{

	private const Normal = 1,
		First = 2,
		Last = 3;

	private const ValuesAndNames = [
		self::Normal => 'normal',
		self::First => 'first',
		self::Last => 'last',
	];

	/** @readonly */
	public string $name;

	/** @readonly */
	public int $value;

	/** @var array<string, self> */
	private static array $instances = [];

	private function __construct(string $name, int $value)
	{
		$this->name = $name;
		$this->value = $value;
	}

	public static function normal(): self
	{
		return self::from(self::Normal);
	}

	public static function first(): self
	{
		return self::from(self::First);
	}

	public static function last(): self
	{
		return self::from(self::Last);
	}

	public static function tryFrom(int $value): ?self
	{
		$key = self::ValuesAndNames[$value] ?? null;

		if ($key === null) {
			return null;
		}

		return self::$instances[$key] ??= new self($key, $value);
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

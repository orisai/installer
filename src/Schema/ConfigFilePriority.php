<?php declare(strict_types = 1);

namespace Orisai\Installer\Schema;

use ValueError;
use function array_key_exists;

final class ConfigFilePriority
{

	private const NORMAL = 1,
		HIGH = 2,
		LOW = 3;

	private const VALUES_AND_NAMES = [
		self::NORMAL => 'normal',
		self::HIGH => 'high',
		self::LOW => 'low',
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
		return self::from(self::NORMAL);
	}

	public static function high(): self
	{
		return self::from(self::HIGH);
	}

	public static function low(): self
	{
		return self::from(self::LOW);
	}

	public static function tryFrom(int $value): ?self
	{
		if (!array_key_exists($value, self::VALUES_AND_NAMES)) {
			return null;
		}

		return new self(self::VALUES_AND_NAMES[$value], $value);
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
		foreach (self::VALUES_AND_NAMES as $value => $name) {
			$cases[] = self::from($value);
		}

		return $cases;
	}

}

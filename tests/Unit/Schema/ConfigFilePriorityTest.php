<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\ConfigFilePriority;
use PHPUnit\Framework\TestCase;
use ValueError;

final class ConfigFilePriorityTest extends TestCase
{

	public function test(): void
	{
		self::assertSame(1, ConfigFilePriority::normal()->value);
		self::assertSame('normal', ConfigFilePriority::normal()->name);
		self::assertSame(2, ConfigFilePriority::first()->value);
		self::assertSame('first', ConfigFilePriority::first()->name);
		self::assertSame(3, ConfigFilePriority::last()->value);
		self::assertSame('last', ConfigFilePriority::last()->name);

		self::assertSame(
			[
				ConfigFilePriority::normal(),
				ConfigFilePriority::first(),
				ConfigFilePriority::last(),
			],
			ConfigFilePriority::cases(),
		);

		self::assertSame(ConfigFilePriority::normal(), ConfigFilePriority::from(1));
		self::assertSame(ConfigFilePriority::normal(), ConfigFilePriority::tryFrom(1));

		self::assertNull(ConfigFilePriority::tryFrom(4));
		$this->expectException(ValueError::class);
		ConfigFilePriority::from(4);
	}

}

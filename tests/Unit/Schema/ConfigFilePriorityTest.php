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
		self::assertSame(2, ConfigFilePriority::high()->value);
		self::assertSame('high', ConfigFilePriority::high()->name);
		self::assertSame(3, ConfigFilePriority::low()->value);
		self::assertSame('low', ConfigFilePriority::low()->name);

		self::assertEquals(
			[
				ConfigFilePriority::normal(),
				ConfigFilePriority::high(),
				ConfigFilePriority::low(),
			],
			ConfigFilePriority::cases(),
		);

		self::assertEquals(ConfigFilePriority::normal(), ConfigFilePriority::from(1));
		self::assertEquals(ConfigFilePriority::normal(), ConfigFilePriority::tryFrom(1));

		self::assertNull(ConfigFilePriority::tryFrom(4));
		$this->expectException(ValueError::class);
		ConfigFilePriority::from(4);
	}

}

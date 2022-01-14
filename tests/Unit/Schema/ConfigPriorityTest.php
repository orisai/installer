<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\ConfigPriority;
use PHPUnit\Framework\TestCase;
use ValueError;

final class ConfigPriorityTest extends TestCase
{

	public function test(): void
	{
		self::assertSame(1, ConfigPriority::normal()->value);
		self::assertSame('normal', ConfigPriority::normal()->name);
		self::assertSame(2, ConfigPriority::high()->value);
		self::assertSame('high', ConfigPriority::high()->name);
		self::assertSame(3, ConfigPriority::low()->value);
		self::assertSame('low', ConfigPriority::low()->name);

		self::assertEquals(
			[
				ConfigPriority::normal(),
				ConfigPriority::high(),
				ConfigPriority::low(),
			],
			ConfigPriority::cases(),
		);

		self::assertEquals(ConfigPriority::normal(), ConfigPriority::from(1));
		self::assertEquals(ConfigPriority::normal(), ConfigPriority::tryFrom(1));

		self::assertNull(ConfigPriority::tryFrom(4));
		$this->expectException(ValueError::class);
		ConfigPriority::from(4);
	}

}

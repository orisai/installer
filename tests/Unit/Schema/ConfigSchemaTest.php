<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\ConfigPriority;
use Orisai\Installer\Schema\ConfigSchema;
use PHPUnit\Framework\TestCase;

final class ConfigSchemaTest extends TestCase
{

	public function test(): void
	{
		$schema = new ConfigSchema($file = __DIR__ . '/wiring.neon');
		self::assertSame($file, $schema->getFile());

		self::assertEquals(ConfigPriority::normal(), $schema->getPriority());
		$schema->setPriority($priority = ConfigPriority::low());
		self::assertSame($priority, $schema->getPriority());

		self::assertSame([], $schema->getRequiredPackages());
		$schema->addRequiredPackage('example/a');
		$schema->addRequiredPackage('example/b');
		self::assertSame(
			[
				'example/a',
				'example/b',
			],
			$schema->getRequiredPackages(),
		);

		self::assertSame([], $schema->getRequiredSwitchValues());
		$schema->setRequiredSwitchValue('a', false);
		$schema->setRequiredSwitchValue('b', true);
		self::assertSame(
			[
				'a' => false,
				'b' => true,
			],
			$schema->getRequiredSwitchValues(),
		);
	}

}

<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\ConfigFilePriority;
use Orisai\Installer\Schema\ConfigFileSchema;
use PHPUnit\Framework\TestCase;

final class ConfigFileSchemaTest extends TestCase
{

	public function test(): void
	{
		$schema = new ConfigFileSchema($absolutePath = __DIR__ . '/wiring.neon');
		self::assertSame($absolutePath, $schema->getAbsolutePath());

		self::assertEquals(ConfigFilePriority::normal(), $schema->getPriority());
		$schema->setPriority($priority = ConfigFilePriority::low());
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

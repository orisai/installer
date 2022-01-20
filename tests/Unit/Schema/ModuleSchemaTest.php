<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\ModuleSchema;
use PHPUnit\Framework\TestCase;

final class ModuleSchemaTest extends TestCase
{

	public function test(): void
	{
		$schema = new ModuleSchema();

		self::assertNull($schema->getLoader());
		$schema->setLoader(
			$loaderFile = __DIR__ . '/ModuleSchemaTest.php',
			$loaderClass = self::class,
		);
		self::assertSame($loaderFile, $schema->getLoader()->getFile());
		self::assertSame($loaderClass, $schema->getLoader()->getClass());

		self::assertSame([], $schema->getConfigFiles());
		$config1 = $schema->addConfigFile(__DIR__ . '/1.neon');
		$config2 = $schema->addConfigFile(__DIR__ . '/2.neon');
		self::assertSame(
			[
				$config1,
				$config2,
			],
			$schema->getConfigFiles(),
		);

		self::assertSame([], $schema->getSwitches());
		$schema->addSwitch('a', true);
		$schema->addSwitch('b', false);
		self::assertSame(
			[
				'a' => true,
				'b' => false,
			],
			$schema->getSwitches(),
		);

		self::assertSame([], $schema->getMonorepoSubmodules());
		$submodule1 = $schema->addSubmodule('vendor/foo', __DIR__ . '/packages/foo');
		$submodule2 = $schema->addSubmodule('vendor/bar', __DIR__ . '/packages/bar');
		self::assertSame(
			[
				$submodule1,
				$submodule2,
			],
			$schema->getMonorepoSubmodules(),
		);
	}

}

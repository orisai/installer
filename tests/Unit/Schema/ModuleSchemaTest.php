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
		$config1 = $schema->addConfigFile('/foo/1.neon');
		$config2 = $schema->addConfigFile('/foo/../foo/2.neon');
		self::assertSame(
			[
				'/foo/1.neon' => $config1,
				'/foo/2.neon' => $config2,
			],
			$schema->getConfigFiles(),
		);
		self::assertSame('/foo/1.neon', $config1->getAbsolutePath());
		self::assertSame('/foo/2.neon', $config2->getAbsolutePath());

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
	}

}

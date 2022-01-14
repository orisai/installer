<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\PackageSchema;
use PHPUnit\Framework\TestCase;

final class PackageSchemaTest extends TestCase
{

	public function test(): void
	{
		$schema = new PackageSchema();

		self::assertNull($schema->getLoader());
		$schema->setLoader(
			$loaderFile = __DIR__ . '/PackageSchemaTest.php',
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

		self::assertSame([], $schema->getIgnorePackageConfigs());
		$schema->ignoreConfigFrom('example/a');
		$schema->ignoreConfigFrom('example/b');
		self::assertSame(
			[
				'example/a',
				'example/b',
			],
			$schema->getIgnorePackageConfigs(),
		);

		self::assertSame([], $schema->getMonorepoPackages());
		$subpackage1 = $schema->addMonorepoPackage('vendor/foo', __DIR__ . '/packages/foo');
		$subpackage2 = $schema->addMonorepoPackage('vendor/bar', __DIR__ . '/packages/bar');
		self::assertSame(
			[
				$subpackage1,
				$subpackage2,
			],
			$schema->getMonorepoPackages(),
		);
	}

}

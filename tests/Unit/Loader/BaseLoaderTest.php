<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Loader;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Loader\DynamicLoader;
use PHPUnit\Framework\TestCase;

final class BaseLoaderTest extends TestCase
{

	public function testBase(): void
	{
		$loader = new DynamicLoader(
			[
				['file' => 'vendor/orisai/cmf/src/wiring.neon'],
				['file' => 'src/wiring.neon'],
				['file' => 'config/common.neon'],
				['file' => 'config/local.neon'],
			],
			[],
			[
				'orisai/cmf' => ['dir' => 'vendor/orisai/cmf'],
				'example/app' => ['dir' => ''],
				'__root__' => ['dir' => ''],
			],
		);

		$files = $loader->loadConfigFiles('/root');
		self::assertSame(
			[
				'/root/vendor/orisai/cmf/src/wiring.neon',
				'/root/src/wiring.neon',
				'/root/config/common.neon',
				'/root/config/local.neon',
			],
			$files,
		);

		$meta = $loader->loadModulesMeta('/root');
		self::assertSame(
			[
				'orisai/cmf' => [
					'dir' => '/root/vendor/orisai/cmf',
				],
				'example/app' => [
					'dir' => '/root',
				],
				'__root__' => [
					'dir' => '/root',
				],
			],
			$meta,
		);
	}

	public function testConfigSwitchesFilter(): void
	{
		$loader = new DynamicLoader(
			[
				[
					'file' => 'a.neon',
					'switches' => [
						'defaultTrue' => true,
					],
				],
				[
					'file' => 'b.neon',
					'switches' => [
						'changedToFalse' => false,
					],
				],
				[
					'file' => 'c.neon',
					'switches' => [
						'defaultFalse' => false,
						'changedToTrue' => false,
					],
				],
			],
			[
				'defaultTrue' => true,
				'defaultFalse' => false,
				'changedToFalse' => true,
				'changedToTrue' => false,
			],
			[],
		);

		$loader->configureSwitch('changedToFalse', false);
		$loader->configureSwitch('changedToTrue', true);

		self::assertSame(
			[
				'/root/a.neon',
				'/root/b.neon',
			],
			$loader->loadConfigFiles('/root'),
		);
	}

	public function testUndefinedSwitch(): void
	{
		$loader = new DynamicLoader(
			[],
			['bar' => false, 'baz' => false],
			[],
		);

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage(<<<'MSG'
Context: Trying to set value of switch 'foo'.
Problem: Switch is not defined by any of loaded 'Orisai.php'.
Solution: Do not configure switch or choose one of available: 'bar, baz'.
MSG);

		$loader->configureSwitch('foo', false);
	}

}

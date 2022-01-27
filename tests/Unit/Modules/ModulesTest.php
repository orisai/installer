<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Modules;

use Orisai\Installer\Modules\Module;
use Orisai\Installer\Modules\Modules;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use PHPUnit\Framework\TestCase;

final class ModulesTest extends TestCase
{

	public function test(): void
	{
		$root = $this->createModule('__root__');
		$m1 = $this->createModule('foo/bar');
		$m2 = $this->createModule('lorem/ipsum');
		$modules = new Modules($root, $list = [
			$m1->getData()->getName() => $m1,
			$m2->getData()->getName() => $m2,
		]);

		self::assertSame($root, $modules->getRootModule());
		self::assertSame($list, $modules->getModules());
		self::assertSame($m1, $modules->getModule($m1->getData()->getName()));
		self::assertNull($modules->getModule('missing'));
	}

	private function createModule(string $name): Module
	{
		return new Module(
			new ModuleSchema(),
			new PackageData($name, [], [], [], __DIR__ . '/' . $name, $name),
		);
	}

}

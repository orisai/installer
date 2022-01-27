<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Modules;

use Error;
use Orisai\Installer\Modules\Module;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use PHPUnit\Framework\TestCase;

final class ModuleTest extends TestCase
{

	public function test(): void
	{
		$schema = new ModuleSchema();
		$data = new PackageData('foo/bar', [], [], [], __DIR__, __DIR__ . '/foo');
		$module = new Module($schema, $data);

		self::assertSame($schema, $module->getSchema());
		self::assertSame($data, $module->getData());

		$d1 = $this->createModule('dep/one');
		$d2 = $this->createModule('dep/two');
		$module->setDependents($deps = [
			$d1->getData()->getName() => $d1,
			$d2->getData()->getName() => $d2,
		]);
		self::assertSame($module->getDependents(), $deps);
	}

	public function testNotInitDeps(): void
	{
		$module = $this->createModule('foo/bar');

		$this->expectException(Error::class);
		$module->getDependents();
	}

	private function createModule(string $name): Module
	{
		return new Module(
			new ModuleSchema(),
			new PackageData($name, [], [], [], __DIR__ . '/' . $name, $name),
		);
	}

}

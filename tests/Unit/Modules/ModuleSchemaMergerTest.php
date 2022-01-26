<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Modules;

use Orisai\Installer\Modules\ModuleSchemaMerger;
use Orisai\Installer\Schema\ConfigFilePriority;
use Orisai\Installer\Schema\ModuleSchema;
use PHPUnit\Framework\TestCase;

final class ModuleSchemaMergerTest extends TestCase
{

	private ModuleSchemaMerger $merger;

	protected function setUp(): void
	{
		parent::setUp();
		$this->merger = new ModuleSchemaMerger();
	}

	public function testParentLoaderIsNotUsed(): void
	{
		$parent = new ModuleSchema();
		$child = new ModuleSchema();

		$parent->setLoader('/parent', 'Parent\ClassName');

		$merged = $this->merger->merge($parent, $child);
		self::assertNull($merged->getLoader());
	}

	public function testLoaderIsKept(): void
	{
		$parent = new ModuleSchema();
		$child = new ModuleSchema();

		$parent->setLoader('/parent', 'Parent\ClassName');
		$child->setLoader('/child', 'Child\ClassName');

		$merged = $this->merger->merge($parent, $child);
		$loader = $merged->getLoader();
		self::assertNotNull($loader);
		self::assertSame('/child', $loader->getFile());
		self::assertSame('Child\ClassName', $loader->getClass());
	}

	public function testSwitchesMerge(): void
	{
		$parent = new ModuleSchema();
		$child = new ModuleSchema();

		$parent->addSwitch('parent', true);
		$parent->addSwitch('overridden', true);
		$child->addSwitch('overridden', false);
		$child->addSwitch('child', false);

		$merged = $this->merger->merge($parent, $child);
		self::assertSame(
			[
				'parent' => true,
				'overridden' => false,
				'child' => false,
			],
			$merged->getSwitches(),
		);
	}

	public function testConfigsMerge(): void
	{
		$parent = new ModuleSchema();
		$child = new ModuleSchema();

		$a = $parent->addConfigFile('/parent.neon');

		$b = $parent->addConfigFile('/overridden.neon');
		$b->setPriority(ConfigFilePriority::low());
		$b->setRequiredSwitchValue('switch', true);
		$b->addRequiredPackage('vendor/required');
		$b2 = $child->addConfigFile('/overridden.neon');

		$c = $child->addConfigFile('/child.neon');
		$c->setPriority(ConfigFilePriority::low());
		$c->setRequiredSwitchValue('switch', true);
		$c->addRequiredPackage('vendor/required');

		$merged = $this->merger->merge($parent, $child);

		// parent options are discarded, config is merged without any settings
		$b3 = $merged->getConfigFiles()['/overridden.neon'];
		self::assertEquals($b3, $b2);
		self::assertNotEquals($b3, $b);
		self::assertNotEquals($b3->getPriority(), $b->getPriority());
		self::assertSame([], $b3->getRequiredSwitchValues());
		self::assertSame([], $b3->getRequiredPackages());

		self::assertEquals(
			[
				$a->getAbsolutePath() => $a,
				$b2->getAbsolutePath() => $b2,
				$c->getAbsolutePath() => $c,
			],
			$merged->getConfigFiles(),
		);
	}

	public function testSubmodulesMerge(): void
	{
		$parent = new ModuleSchema();
		$child = new ModuleSchema();

		$a = $parent->addSubmodule('a', '/parent/a');
		$b = $parent->addSubmodule('b', '/parent/b');
		$b->setOptional();
		$b2 = $child->addSubmodule('b', '/child/b');
		$c = $child->addSubmodule('c', '/child/c');
		$c->setOptional();

		$merged = $this->merger->merge($parent, $child);

		// parent options are discarded, submodule is merged without any settings
		$b3 = $merged->getMonorepoSubmodules()['b'];
		self::assertEquals($b3, $b2);
		self::assertNotEquals($b3, $b);
		self::assertFalse($b3->isOptional());

		self::assertEquals(
			[
				$a->getName() => $a,
				$b2->getName() => $b2,
				$c->getName() => $c,
			],
			$merged->getMonorepoSubmodules(),
		);
	}

}

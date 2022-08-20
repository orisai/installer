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
		$b->setPriority(ConfigFilePriority::last());
		$b->addRequiredSwitch('switch');
		$b->addRequiredPackage('vendor/required');
		$b2 = $child->addConfigFile('/overridden.neon');

		$c = $child->addConfigFile('/child.neon');
		$c->setPriority(ConfigFilePriority::last());
		$c->addRequiredSwitch('switch');
		$c->addRequiredPackage('vendor/required');

		$merged = $this->merger->merge($parent, $child);

		// parent options are discarded, config is merged without any settings
		$b3 = $merged->getConfigFiles()['/overridden.neon'];
		self::assertEquals($b3, $b2);
		self::assertNotEquals($b3, $b);
		self::assertNotEquals($b3->getPriority(), $b->getPriority());
		self::assertSame([], $b3->getSwitches());
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

}

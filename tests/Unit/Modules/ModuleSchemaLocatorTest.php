<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Modules;

use Generator;
use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Modules\ModuleSchemaLocator;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use Orisai\Installer\SchemaName;
use PHPUnit\Framework\TestCase;

final class ModuleSchemaLocatorTest extends TestCase
{

	private ModuleSchemaLocator $locator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->locator = new ModuleSchemaLocator();
	}

	/**
	 * @dataProvider provideValidSchema
	 */
	public function testValidSchema(string $path): void
	{
		$package = $this->createPackage('example/valid', $path);

		$paths = [];
		$schema = $this->locator->locate($package, null, $paths);

		self::assertInstanceOf(ModuleSchema::class, $schema);
		self::assertSame(SchemaName::FILE_LOCATIONS, $paths);

		$this->locator->locateOrThrow($package);
	}

	/**
	 * @return Generator<array<mixed>>
	 */
	public function provideValidSchema(): Generator
	{
		yield ['Valid'];

		yield ['ValidApp'];

		yield ['ValidSrc'];
	}

	public function testGivenSchema(): void
	{
		$package = $this->createPackage('example/custom', 'Custom');
		$schemaName = 'Custom.php';

		$paths = [];
		$schema = $this->locator->locate($package, $schemaName, $paths);

		self::assertInstanceOf(ModuleSchema::class, $schema);
		self::assertSame([$schemaName], $paths);

		$this->locator->locateOrThrow($package, $schemaName);
	}

	public function testNoSchema(): void
	{
		$package = $this->createPackage('example/custom', 'Custom');

		$paths = [];
		$schema = $this->locator->locate($package, null, $paths);

		self::assertNull($schema);
		self::assertSame(SchemaName::FILE_LOCATIONS, $paths);

		$this->expectException(InvalidState::class);
		$this->expectExceptionMessage(
			"Schema file is missing in 'example/custom' (one of Orisai.php, src/Orisai.php, app/Orisai.php).",
		);

		$this->locator->locateOrThrow($package);
	}

	public function testInvalidSchema(): void
	{
		$package = $this->createPackage('example/invalid', 'Invalid');

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage(
			"Schema file 'Orisai.php' of package 'example/invalid' should return " .
			"'Orisai\Installer\Schema\ModuleSchema', 'stdClass' returned.",
		);

		$this->locator->locate($package);
	}

	public function testMultipleSchemas(): void
	{
		$package = $this->createPackage('example/multiple', 'Multiple');

		$this->expectException(InvalidState::class);
		$this->expectExceptionMessage(
			"Multiple schema files (Orisai.php, src/Orisai.php) found in 'example/multiple', only one can exist.",
		);

		$this->locator->locate($package);
	}

	public function testMissingSchema(): void
	{
		$package = $this->createPackage('example/custom', 'Custom');
		$schemaName = 'Missing.php';

		$paths = [];
		$schema = $this->locator->locate($package, $schemaName, $paths);

		self::assertNull($schema);
		self::assertSame([$schemaName], $paths);

		$this->expectException(InvalidState::class);
		$this->expectExceptionMessage("Schema file is missing in 'example/custom' (one of $schemaName).");

		$this->locator->locateOrThrow($package, $schemaName);
	}

	private function createPackage(string $name, string $path): PackageData
	{
		return new PackageData($name, [], [], [], __DIR__ . '/../../Doubles/Locator/' . $path, $path);
	}

}

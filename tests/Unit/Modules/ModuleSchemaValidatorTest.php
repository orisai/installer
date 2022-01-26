<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Modules;

use Orisai\Exceptions\Logic\InvalidArgument;
use Orisai\Installer\Modules\ModuleSchemaValidator;
use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Schema\ModuleSchema;
use PHPUnit\Framework\TestCase;

final class ModuleSchemaValidatorTest extends TestCase
{

	private ModuleSchemaValidator $validator;

	protected function setUp(): void
	{
		parent::setUp();
		$this->validator = new ModuleSchemaValidator();
	}

	public function testConfigExists(): void
	{
		$data = new PackageData('foo/bar', [], [], [], __DIR__, '');
		$schema = new ModuleSchema();
		$schema->addConfigFile($config = __DIR__ . '/../../Doubles/Validator/config.neon');

		self::assertFileExists($config);
		$this->validator->validate($schema, $data);
	}

	public function testConfigFileNotFound(): void
	{
		$data = new PackageData('foo/bar', [], [], [], __DIR__, '');
		$schema = new ModuleSchema();
		$schema->addConfigFile(__DIR__ . '/src/wiring.neon');

		$this->expectException(InvalidArgument::class);
		$this->expectExceptionMessage("Config file 'src/wiring.neon' not found in package 'foo/bar'.");

		$this->validator->validate($schema, $data);
	}

}

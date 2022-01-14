<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\MonorepoSubpackageSchema;
use PHPUnit\Framework\TestCase;

final class MonorepoSubpackageSchemaTest extends TestCase
{

	public function test(): void
	{
		$schema = new MonorepoSubpackageSchema(
			$name = 'foo/bar',
			$path = __DIR__ . '/foo',
		);
		self::assertSame($name, $schema->getName());
		self::assertSame($path, $schema->getPath());

		self::assertFalse($schema->isOptional());
		$schema->setOptional();
		self::assertTrue($schema->isOptional());
		$schema->setOptional(false);
		self::assertFalse($schema->isOptional());
	}

}

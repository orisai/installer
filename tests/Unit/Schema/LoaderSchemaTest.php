<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Schema;

use Orisai\Installer\Schema\LoaderSchema;
use PHPUnit\Framework\TestCase;

final class LoaderSchemaTest extends TestCase
{

	public function test(): void
	{
		$schema = new LoaderSchema(
			$file = __DIR__ . '/LoaderSchemaTest.php',
			$class = self::class,
		);

		self::assertSame($file, $schema->getFile());
		self::assertSame($class, $schema->getClass());
	}

}

<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Packages;

use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackageLink;
use PHPUnit\Framework\TestCase;

final class PackageDataTest extends TestCase
{

	public function test(): void
	{
		$package = new PackageData(
			$name = 'package/name',
			$requires = [
				new PackageLink($name, 'require/one'),
				new PackageLink($name, 'require/two'),
			],
			$devRequires = [
				new PackageLink($name, 'dev/one'),
				new PackageLink($name, 'dev/two'),
			],
			$replaces = [
				new PackageLink($name, 'replaces/one'),
				new PackageLink($name, 'replaces/two'),
			],
			$absolutePath = __DIR__ . '/foo/bar',
			$relativePath = '/foo/bar',
		);

		self::assertSame($name, $package->getName());
		self::assertSame($requires, $package->getRequires());
		self::assertSame($devRequires, $package->getDevRequires());
		self::assertSame($replaces, $package->getReplaces());
		self::assertSame($absolutePath, $package->getAbsolutePath());
		self::assertSame($relativePath, $package->getRelativePath());
	}

}

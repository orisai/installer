<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Packages;

use Orisai\Installer\Packages\PackageData;
use Orisai\Installer\Packages\PackagesData;
use PHPUnit\Framework\TestCase;

final class PackagesDataTest extends TestCase
{

	public function test(): void
	{
		$rootPackage = new PackageData('__root__', [], [], [], __DIR__, '');
		$data = new PackagesData($rootPackage);

		self::assertSame($rootPackage, $data->getRootPackage());
		self::assertSame($rootPackage, $data->getPackage($rootPackage->getName()));
		self::assertSame(
			[
				$rootPackage->getName() => $rootPackage,
			],
			$data->getPackages(),
		);

		self::assertNull($data->getPackage('foo/bar'));
		$fooBar = new PackageData('foo/bar', [], [], [], __DIR__ . '/foo/bar', 'foo/bar');
		$data->addPackage($fooBar);
		self::assertSame($fooBar, $data->getPackage($fooBar->getName()));
		self::assertSame(
			[
				$rootPackage->getName() => $rootPackage,
				$fooBar->getName() => $fooBar,
			],
			$data->getPackages(),
		);
	}

}

<?php declare(strict_types = 1);

namespace Tests\Orisai\Installer\Unit\Packages;

use Orisai\Installer\Packages\PackageLink;
use PHPUnit\Framework\TestCase;

final class PackageLinkTest extends TestCase
{

	public function test(): void
	{
		$link = new PackageLink('link/source', 'link/target');
		self::assertSame(
			'link/source',
			$link->getSource(),
		);
		self::assertSame(
			'link/target',
			$link->getTarget(),
		);
	}

}

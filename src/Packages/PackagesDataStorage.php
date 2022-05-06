<?php declare(strict_types = 1);

namespace Orisai\Installer\Packages;

use Nette\Utils\FileSystem;
use function assert;
use function serialize;
use function unserialize;

/**
 * @internal
 */
final class PackagesDataStorage
{

	private const File = __DIR__ . '/../_generated/installer.dat';

	public static function save(PackagesData $data): void
	{
		FileSystem::write(self::File, serialize($data));
	}

	public static function load(): PackagesData
	{
		$data = unserialize(
			FileSystem::read(self::File),
			[
				'allowed_classes' => [
					PackagesData::class,
					PackageData::class,
					PackageLink::class,
				],
			],
		);
		assert($data instanceof PackagesData);

		return $data;
	}

}

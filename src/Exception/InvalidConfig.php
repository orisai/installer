<?php declare(strict_types = 1);

namespace Orisai\Installer\Exception;

use Composer\Package\PackageInterface;
use Orisai\Exceptions\LogicalException;
use function sprintf;

final class InvalidConfig extends LogicalException
{

	public static function from(PackageInterface $package, string $file, string $message): self
	{
		return self::create()
			->withMessage(sprintf(
				'Package %s have invalid %s: %s',
				$package->getName(),
				$file,
				$message
			));
	}

}

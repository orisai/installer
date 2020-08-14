<?php declare(strict_types = 1);

namespace Orisai\Installer\Exception;

use Composer\Package\PackageInterface;
use Orisai\Exceptions\LogicalException;
use Orisai\Exceptions\Message;
use function sprintf;

final class InvalidConfig extends LogicalException
{

	public static function create(string $problem, string $file, PackageInterface $package): self
	{
		$message = Message::create()
			->withContext(sprintf(
				'Validation of config file %s of package %s failed',
				$file,
				$package->getName(),
			))
			->withProblem($problem);

		return new self((string) $message);
	}

}

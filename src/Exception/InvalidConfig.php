<?php declare(strict_types = 1);

namespace Orisai\Installer\Exception;

use Composer\Package\PackageInterface;
use Orisai\Exceptions\LogicalException;
use Orisai\Exceptions\Message;
use function sprintf;

final class InvalidConfig extends LogicalException
{

	public function __construct(string $problem, string $file, PackageInterface $package)
	{
		parent::__construct();
		$message = Message::create()
			->withContext(sprintf(
				'Validation of config file %s of package %s failed',
				$file,
				$package->getName(),
			))
			->withProblem($problem);
		$this->withMessage((string) $message);
	}

	public static function create(string $problem, string $file, PackageInterface $package): self
	{
		return new self($problem, $file, $package);
	}

}

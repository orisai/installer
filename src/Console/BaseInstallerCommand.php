<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use Composer\Command\BaseCommand as ComposerBaseCommand;
use Orisai\Installer\SchemaName;
use Symfony\Component\Console\Input\InputOption;
use function sprintf;

/**
 * @internal
 */
abstract class BaseInstallerCommand extends ComposerBaseCommand
{

	protected const OPTION_FILE = 'file';

	protected function configure(): void
	{
		$this->addOption(
			self::OPTION_FILE,
			'f',
			InputOption::VALUE_REQUIRED,
			sprintf('Use different config file than %s (for tests)', SchemaName::DEFAULT_NAME),
		);
	}

}

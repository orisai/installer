<?php declare(strict_types = 1);

namespace Orisai\Installer\Command;

use Composer\Command\BaseCommand as ComposerBaseCommand;
use Orisai\Installer\Plugin;
use Symfony\Component\Console\Input\InputOption;
use function sprintf;

abstract class BaseCommand extends ComposerBaseCommand
{

	protected const OPTION_FILE = 'file';

	protected function configure(): void
	{
		$this->addOption(
			self::OPTION_FILE,
			'f',
			InputOption::VALUE_REQUIRED,
			sprintf('Use different config file than %s (for tests)', Plugin::DEFAULT_FILE_NAME),
			Plugin::DEFAULT_FILE_NAME,
		);
	}

}

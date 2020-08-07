<?php declare(strict_types = 1);

namespace Orisai\Installer\Command;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

final class CommandProvider implements CommandProviderCapability
{

	/**
	 * @return array<BaseCommand>
	 */
	public function getCommands(): array
	{
		return [
			new LoaderGenerateCommand(),
			new ModuleValidateCommand(),
		];
	}

}

<?php declare(strict_types = 1);

namespace Orisai\Installer\Command;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * @internal
 */
final class CommandProvider implements CommandProviderCapability
{

	/**
	 * @return array<BaseInstallerCommand>
	 */
	public function getCommands(): array
	{
		return [
			new LoaderGenerateCommand(),
			new ModuleValidateCommand(),
		];
	}

}

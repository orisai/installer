<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use Composer\Command\BaseCommand;
use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;

/**
 * @internal
 */
final class CommandProvider implements CommandProviderCapability
{

	/**
	 * @return array<BaseCommand>
	 */
	public function getCommands(): array
	{
		return [
			new GenerateLoaderCommand(),
		];
	}

}

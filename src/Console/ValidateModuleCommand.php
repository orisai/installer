<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use LogicException;
use Orisai\Installer\Modules\ModuleSchemaLocator;
use Orisai\Installer\Modules\ModuleSchemaValidator;
use Orisai\Installer\Packages\PackagesDataStorage;
use Orisai\Installer\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function assert;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class ValidateModuleCommand extends BaseInstallerCommand
{

	private const OPTION_PACKAGE = 'package';

	public static function getDefaultName(): string
	{
		return 'orisai:module:validate';
	}

	protected function configure(): void
	{
		parent::configure();

		$this->setName(self::getDefaultName());
		$this->setDescription(sprintf('Validate %s', Plugin::DEFAULT_FILE_NAME));

		$this->addOption(
			self::OPTION_PACKAGE,
			'p',
			InputOption::VALUE_REQUIRED,
			'Package which is validated (current package is validated if not specified)',
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$schemaRelativeName = $input->getOption(self::OPTION_FILE);
		assert(is_string($schemaRelativeName));

		$data = PackagesDataStorage::load();

		$packageName = $input->getOption(self::OPTION_PACKAGE);
		assert(is_string($packageName) || $packageName === null);
		if ($packageName === null) {
			$package = $data->getRootPackage();
		} else {
			$package = $data->getPackage($packageName);

			if ($package === null) {
				throw new LogicException(sprintf('Package \'%s\' does not exists', $packageName));
			}
		}

		$locator = new ModuleSchemaLocator();
		$schema = $locator->locateOrThrow($package, $schemaRelativeName);

		$validator = new ModuleSchemaValidator();
		$validator->validate($schema);

		$io = new SymfonyStyle($input, $output);
		$io->success(sprintf('%s successfully validated', $schemaRelativeName));

		return 0;
	}

}

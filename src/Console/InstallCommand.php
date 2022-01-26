<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use Composer\Command\BaseCommand;
use Orisai\Installer\Loader\LoaderGenerator;
use Orisai\Installer\Modules\ModuleSchemaLocator;
use Orisai\Installer\Modules\ModulesGenerator;
use Orisai\Installer\Packages\PackagesDataStorage;
use Orisai\Installer\SchemaName;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function is_string;
use function sprintf;

/**
 * @internal
 */
final class InstallCommand extends BaseCommand
{

	private const OPTION_FILE = 'file';

	public static function getDefaultName(): string
	{
		return 'orisai:install';
	}

	public static function getDefaultDescription(): string
	{
		return 'Generate modules loader';
	}

	protected function configure(): void
	{
		parent::configure();

		$this->setName(self::getDefaultName());
		$this->setDescription(self::getDefaultDescription());

		$this->addOption(
			self::OPTION_FILE,
			'f',
			InputOption::VALUE_REQUIRED,
			sprintf('Use different config file than %s (for tests)', SchemaName::DEFAULT_NAME),
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$schemaRelativeName = $input->getOption(self::OPTION_FILE);
		assert(is_string($schemaRelativeName) || $schemaRelativeName === null);

		$data = PackagesDataStorage::load();
		$rootPackage = $data->getRootPackage();

		$locator = new ModuleSchemaLocator();
		$schema = $locator->locateOrThrow($rootPackage, $schemaRelativeName);

		$modulesGenerator = new ModulesGenerator();
		$modules = $modulesGenerator->generate($data, $schema);

		$loaderGenerator = new LoaderGenerator($modules);
		$loader = $loaderGenerator->generateAndSave();

		$output->writeln("Generated loader <info>{$loader->getClass()}</info>.");

		return 0;
	}

}

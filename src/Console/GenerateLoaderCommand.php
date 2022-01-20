<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use Orisai\Installer\Loader\LoaderGenerator;
use Orisai\Installer\Modules\ModulesGenerator;
use Orisai\Installer\Packages\PackagesDataStorage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function assert;
use function file_exists;
use function is_string;

/**
 * @internal
 */
final class GenerateLoaderCommand extends BaseInstallerCommand
{

	public static function getDefaultName(): string
	{
		return 'orisai:loader:generate';
	}

	protected function configure(): void
	{
		parent::configure();

		$this->setName(self::getDefaultName());
		$this->setDescription('Generate modules loader');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$schemaRelativeName = $input->getOption(self::OPTION_FILE);
		assert(is_string($schemaRelativeName));

		$data = PackagesDataStorage::load();
		$rootPackage = $data->getRootPackage();

		$schemaFqn = "{$rootPackage->getAbsolutePath()}/$schemaRelativeName";
		if (!file_exists($schemaFqn)) {
			$message = Message::create()
				->withContext("Trying to generate module loader for package {$rootPackage->getName()}.")
				->withProblem("File $schemaRelativeName does not exist in this package.")
				->withSolution('Use name of an existing file.');

			throw InvalidState::create()
				->withMessage($message);
		}

		$modulesGenerator = new ModulesGenerator();
		$modules = $modulesGenerator->generate($data, $schemaRelativeName);

		$loaderGenerator = new LoaderGenerator($modules);
		$loaderGenerator->generateAndSave();

		$io = new SymfonyStyle($input, $output);
		$io->success('Modules loader successfully generated');

		return 0;
	}

}

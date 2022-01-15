<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Data\InstallerDataGenerator;
use Orisai\Installer\Loading\LoaderGenerator;
use Orisai\Installer\Utils\PathResolver;
use Orisai\Installer\Utils\PluginActivator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function assert;
use function is_string;
use function sprintf;

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
		$composer = $this->getComposer();
		assert($composer !== null);

		$fileName = $input->getOption(self::OPTION_FILE);
		assert(is_string($fileName));

		$pathResolver = new PathResolver($composer);
		$validator = new ConfigValidator($pathResolver);
		$rootPackage = $composer->getPackage();
		$activator = new PluginActivator(
			$rootPackage,
			$validator,
			$pathResolver,
			$fileName,
		);

		if (!$activator->isEnabled()) {
			$message = Message::create()
				->withContext('Trying to generate module loader.')
				->withProblem(sprintf('`%s` option `loader` is not configured.', $fileName))
				->withSolution(sprintf('Add `loader` option to `%s`', $fileName));

			throw InvalidState::create()
				->withMessage($message);
		}

		$dataGenerator = new InstallerDataGenerator(
			$composer->getRepositoryManager()->getLocalRepository(),
			$validator,
			$pathResolver,
		);

		$rootConfig = $activator->getRootPackageConfiguration();

		$loaderGenerator = new LoaderGenerator(
			$dataGenerator->generate($rootPackage, $rootConfig),
		);

		$loaderGenerator->generateLoader();
		$io = new SymfonyStyle($input, $output);
		$io->success('Modules loader successfully generated');

		return 0;
	}

}

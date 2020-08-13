<?php declare(strict_types = 1);

namespace Orisai\Installer\Command;

use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Files\NeonReader;
use Orisai\Installer\Files\Writer;
use Orisai\Installer\Loading\LoaderGenerator;
use Orisai\Installer\Utils\PathResolver;
use Orisai\Installer\Utils\PluginActivator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function assert;
use function is_string;
use function sprintf;

final class LoaderGenerateCommand extends BaseCommand
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

		$fileName = $input->getOption(self::OPTION_FILE);
		assert(is_string($fileName));

		$pathResolver = new PathResolver($composer);
		$validator = new ConfigValidator(new NeonReader(), $pathResolver);
		$activator = new PluginActivator(
			$composer->getPackage(),
			$validator,
			$pathResolver,
			$fileName,
		);

		if (!$activator->isEnabled()) {
			throw new InvalidState(sprintf(
				'Cannot generate module loader, \'%s\' with \'loader\' option must be configured.',
				$fileName,
			));
		}

		$io = new SymfonyStyle($input, $output);
		$loaderGenerator = new LoaderGenerator(
			$composer->getRepositoryManager()->getLocalRepository(),
			new Writer(),
			$pathResolver,
			$validator,
			$activator->getRootPackageConfiguration(),
		);

		$loaderGenerator->generateLoader();
		$io->success('Modules loader successfully generated');

		return 0;
	}

}

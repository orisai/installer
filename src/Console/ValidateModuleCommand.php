<?php declare(strict_types = 1);

namespace Orisai\Installer\Console;

use Composer\Semver\Constraint\MatchAllConstraint;
use LogicException;
use Orisai\Installer\Config\ConfigValidator;
use Orisai\Installer\Files\NeonReader;
use Orisai\Installer\Plugin;
use Orisai\Installer\Utils\PathResolver;
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
		$composer = $this->getComposer();
		assert($composer !== null);

		$fileName = $input->getOption(self::OPTION_FILE);
		assert(is_string($fileName));

		$pathResolver = new PathResolver($composer);
		$validator = new ConfigValidator(new NeonReader(), $pathResolver);
		$io = new SymfonyStyle($input, $output);

		if (($packageName = $input->getOption(self::OPTION_PACKAGE)) !== null) {
			assert(is_string($packageName));
			$package = $composer->getRepositoryManager()->getLocalRepository()->findPackage(
				$packageName,
				new MatchAllConstraint(),
			);

			if ($package === null) {
				throw new LogicException(sprintf('Package \'%s\' does not exists', $packageName));
			}
		} else {
			$package = $composer->getPackage();
		}

		$validator->validateConfiguration($package, $fileName);
		$io->success(sprintf('%s successfully validated', $fileName));

		return 0;
	}

}

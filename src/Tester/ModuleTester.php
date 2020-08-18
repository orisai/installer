<?php declare(strict_types = 1);

namespace Orisai\Installer\Tester;

use Composer\Composer;
use Composer\Console\Application;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Exceptions\Message;
use Orisai\Installer\Command\LoaderGenerateCommand;
use Orisai\Installer\Command\ModuleValidateCommand;
use Symfony\Component\Console\Tester\CommandTester;
use function class_exists;

final class ModuleTester
{

	private Application $application;

	public function __construct()
	{
		$this->checkComposerAvailability();
		$this->application = new Application();
		$this->application->add(new LoaderGenerateCommand());
		$this->application->add(new ModuleValidateCommand());
	}

	private function checkComposerAvailability(): void
	{
		if (!class_exists(Composer::class)) {
			$message = Message::create()
				->withContext('Trying to use installer tests utility.')
				->withProblem('Cannot found Composer installation.')
				->withSolution('Install Composer via `composer require --dev composer/composer`.');

			throw InvalidState::create()
				->withMessage((string) $message);
		}
	}

	public function generateLoader(?string $file = null): int
	{
		$input = [];

		if ($file !== null) {
			$input['--file'] = $file;
		}

		$tester = new CommandTester(
			$this->application->get(LoaderGenerateCommand::getDefaultName()),
		);

		return $tester->execute($input);
	}

	public function validateModule(?string $file = null, ?string $package = null): int
	{
		$input = [];

		if ($file !== null) {
			$input['--file'] = $file;
		}

		if ($package !== null) {
			$input['--package'] = $package;
		}

		$tester = new CommandTester(
			$this->application->get(ModuleValidateCommand::getDefaultName()),
		);

		return $tester->execute($input);
	}

}

<?php declare(strict_types = 1);

namespace Orisai\Installer\Tests;

use Composer\Composer;
use Composer\Console\Application;
use Composer\Factory;
use Composer\IO\BufferIO;
use Orisai\Exceptions\Logic\InvalidState;
use Orisai\Installer\Command\LoaderGenerateCommand;
use Orisai\Installer\Command\ModuleValidateCommand;
use Orisai\Installer\Plugin;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use function class_exists;
use function sprintf;

final class PluginTestsHelper
{

	private static function checkComposerAvailability(): void
	{
		if (!class_exists(Composer::class)) {
			throw new InvalidState(sprintf(
				'Install Composer via \'composer require --dev composer/composer\' to use \'%s\'',
				self::class,
			));
		}
	}

	/**
	 * @return array<object>
	 * @phpstan-return array{
	 *		Plugin,
	 * 		Composer,
	 * 		BufferIO
	 * }
	 */
	private static function initializePlugin(): array
	{
		self::checkComposerAvailability();

		$io = new BufferIO('', OutputInterface::VERBOSITY_VERBOSE);
		$composer = Factory::create($io);
		$plugin = new Plugin();

		$plugin->activate($composer, $io);

		return [$plugin, $composer, $io];
	}

	public static function generateLoader(?string $file = null): int
	{
		self::initializePlugin();

		$command = new LoaderGenerateCommand();

		$application = new Application();
		$application->add($command);

		$input = [];

		if ($file !== null) {
			$input['--file'] = $file;
		}

		$tester = new CommandTester($command);

		return $tester->execute($input);
	}

	public static function validateModule(?string $file = null, ?string $package = null): int
	{
		self::initializePlugin();

		$command = new ModuleValidateCommand();

		$application = new Application();
		$application->add($command);

		$input = [];

		if ($file !== null) {
			$input['--file'] = $file;
		}

		if ($package !== null) {
			$input['--package'] = $package;
		}

		$tester = new CommandTester($command);

		return $tester->execute($input);
	}

}
